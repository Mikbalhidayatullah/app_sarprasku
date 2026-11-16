<?php

namespace App\Http\Requests;

use App\Models\School;
use App\Support\ActiveReference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('acquired_on') && ! $this->filled('acquisition_year') && is_string($this->input('acquired_on'))) {
            $this->merge(['acquisition_year' => (int) substr($this->input('acquired_on'), 0, 4)]);
        }
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $validator->errors()->has('acquired_on') && $this->filled('acquired_on') && (int) $this->input('acquisition_year') !== (int) substr($this->input('acquired_on'), 0, 4)) {
                $validator->errors()->add('acquisition_year', 'Tahun perolehan harus sesuai dengan tanggal perolehan.');
            }
        }];
    }

    public function authorize(): bool
    {
        return $this->route('asset') ? $this->user()->can('update', $this->route('asset')) : $this->user()->can('assets.create');
    }

    public function rules(): array
    {
        $a = $this->route('asset');
        $rules = [
            'inventory_number' => ['required', 'string', 'max:150', Rule::unique('assets')->where('school_id', School::first()->id)->ignore($a)],
            'name' => 'required|string|max:255', 'official_register' => 'nullable|string|max:255', 'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255', 'serial_number' => 'nullable|string|max:255', 'specifications' => 'nullable|string|max:10000',
            'acquired_on' => 'nullable|date_format:Y-m-d|before_or_equal:'.School::today(),
            'acquisition_year' => 'nullable|integer|min:1900|max:'.now()->year,
            'acquisition_value' => 'nullable|decimal:0,2|min:0|max:9999999999999.99',
            'acquisition_method' => ['required', Rule::in(array_keys(config('inventory.methods')))],
            'classification_id' => ['nullable', ActiveReference::rule('classifications', $a?->classification_id)], 'ownership_id' => ['required', ActiveReference::rule('ownerships', $a?->ownership_id)],
            'notes' => 'nullable|string|max:10000', 'verified' => 'sometimes|boolean',
        ];
        if (! $a) {
            $rules += ['room_id' => ['nullable', ActiveReference::rule('rooms')], 'person_id' => ['nullable', ActiveReference::rule('people')], 'condition' => ['required', Rule::in(array_keys(config('inventory.conditions')))]];
        }

        return $rules;
    }
}
