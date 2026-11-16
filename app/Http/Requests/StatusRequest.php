<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->input('lifecycle_status') === 'disposed' ? 'dispose' : ($this->input('lifecycle_status') === 'archived' ? 'archive' : 'update'), $this->route('asset'));
    }

    public function rules(): array
    {
        return ['lifecycle_status' => ['required', Rule::in(array_keys(config('inventory.statuses')))], 'reason' => 'required|string|min:5|max:5000', 'disposal_reference' => 'required_if:lifecycle_status,disposed|nullable|string|max:255'];
    }
}
