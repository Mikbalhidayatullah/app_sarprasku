<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assets.view');
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:150', 'room_id' => 'nullable|integer|exists:rooms,id', 'location_id' => 'nullable|integer|exists:locations,id',
            'person_id' => 'nullable|integer|exists:people,id', 'classification_id' => 'nullable|integer|exists:classifications,id',
            'condition' => ['nullable', Rule::in(array_keys(config('inventory.conditions')))], 'lifecycle_status' => ['nullable', Rule::in(array_keys(config('inventory.statuses')))],
            'year' => 'nullable|integer|min:1900|max:2100', 'incomplete' => 'nullable|boolean', 'type' => ['nullable', Rule::in(['inventory', 'incomplete', 'transfers', 'procurements', 'maintenance'])],
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'ids' => 'nullable|array|max:100', 'ids.*' => 'uuid|distinct', 'format' => 'nullable|in:pdf,preview', 'page' => 'nullable|integer|min:1',
        ];
    }
}
