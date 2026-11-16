<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('asset'));
    }

    public function rules(): array
    {
        return ['condition' => ['required', Rule::in(array_keys(config('inventory.conditions')))], 'inspected_on' => 'required|date_format:Y-m-d|in:'.School::today(), 'inspector' => 'required|string|max:255', 'reason' => 'required|string|min:5|max:5000'];
    }
}
