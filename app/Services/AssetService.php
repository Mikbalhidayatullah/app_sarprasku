<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssetService
{
    public function create(array $data, User $actor): Asset
    {
        Gate::forUser($actor)->authorize('assets.create');
        try {
            return DB::transaction(function () use ($data, $actor) {
                if (! empty($data['acquired_on'])) {
                    $data['acquisition_year'] = (int) substr($data['acquired_on'], 0, 4);
                }
                $asset = Asset::create(array_merge($data, ['school_id' => School::firstOrFail()->id, 'created_by' => $actor->id, 'lifecycle_status' => 'active']));
                History::event($asset, $actor, 'asset.initialized', ['inventory_number' => $asset->inventory_number, 'name' => $asset->name,
                    'origin' => $asset->procurement_item_id ? 'Pengadaan tercatat' : 'Data awal; histori sebelum pencatatan belum diverifikasi',
                    'room' => $asset->room?->name, 'person' => $asset->person?->name, 'condition' => $asset->condition,
                    'acquisition_value' => $asset->acquisition_value, 'acquired_on' => $asset->acquired_on?->toDateString()], School::today());
                History::audit($actor, 'asset.created', $asset, ['inventory_number' => $asset->inventory_number]);

                return $asset;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['inventory_number' => 'Nomor inventaris sudah digunakan.']);
        }
    }
}
