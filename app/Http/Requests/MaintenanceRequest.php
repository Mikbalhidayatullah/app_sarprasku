<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('maintenance.manage');
    }

    public function rules(): array
    {
        return ['asset_id' => 'required|uuid|exists:assets,id', 'performed_on' => 'required|date_format:Y-m-d|in:'.School::today(), 'complaint' => 'required|string|max:5000', 'action' => 'required|string|max:5000', 'vendor' => 'required|string|max:255', 'cost' => 'nullable|decimal:0,2|min:0|max:9999999999999.99', 'result' => 'required|string|max:5000', 'condition_after' => ['required', Rule::in(array_keys(config('inventory.conditions')))]];
    }
}
