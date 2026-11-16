<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['q' => 'nullable|string|max:150', 'status' => 'nullable|string|max:30', 'page' => 'nullable|integer|min:1'];
    }
}
