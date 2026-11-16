<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class AttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attach', $this->route('asset'));
    }

    public function rules(): array
    {
        return ['file' => ['required', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max(5120), 'extensions:jpg,jpeg,png,pdf'], 'transfer_id' => 'nullable|integer|exists:transfers,id', 'maintenance_id' => 'nullable|integer|exists:maintenances,id'];
    }
}
