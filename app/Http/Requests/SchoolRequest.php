<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('masters.manage');
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:255', 'institution_code' => 'nullable|string|max:255', 'address' => 'nullable|string|max:5000', 'timezone' => 'required|timezone', 'label_width_mm' => 'required|integer|min:70|max:95', 'label_height_mm' => 'required|integer|min:40|max:80'];
    }
}
