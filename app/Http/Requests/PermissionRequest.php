<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('permissions') === null || $this->input('permissions') === '') {
            $this->merge(['permissions' => []]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('permissions.manage');
    }

    public function rules(): array
    {
        return ['permissions' => 'present|array', 'permissions.*' => ['string', Rule::in(config('inventory.permissions'))]];
    }
}
