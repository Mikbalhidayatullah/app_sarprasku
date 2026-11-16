<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;

class AssetQuery
{
    public static function build(array $filters): Builder
    {
        $q = Asset::where('school_id', School::query()->value('id'));
        if ($search = $filters['q'] ?? null) {
            $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('inventory_number', 'like', '%'.$search.'%')->orWhere('official_register', 'like', '%'.$search.'%')->orWhere('serial_number', 'like', '%'.$search.'%'));
        }
        foreach (['room_id', 'person_id', 'classification_id', 'condition', 'lifecycle_status'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $q->where($field, $filters[$field]);
            }
        }
        if ($filters['year'] ?? null) {
            $q->where('acquisition_year', $filters['year']);
        }
        if ($filters['location_id'] ?? null) {
            $q->whereHas('room', fn ($q) => $q->where('location_id', $filters['location_id']));
        }
        if (($filters['incomplete'] ?? false) || ($filters['type'] ?? '') === 'incomplete') {
            $q->where(fn ($q) => $q->where('verified', false)->orWhereNull('classification_id')->orWhereNull('acquisition_value')->orWhereNull('room_id')->orWhereNull('person_id')->orWhereNull('acquisition_year')->orWhere('condition', 'unknown')->orWhereHas('ownership', fn ($q) => $q->where('code', 'UNVERIFIED')));
        }

        return $q;
    }
}
