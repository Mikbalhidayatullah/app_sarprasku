<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transfers.create');
    }

    public function rules(): array
    {
        return ['asset_id' => 'required|uuid|exists:assets,id', 'to_room_id' => ['required', Rule::exists('rooms', 'id')->where('is_active', true)], 'to_person_id' => ['nullable', Rule::exists('people', 'id')->where('is_active', true)], 'effective_on' => 'required|date_format:Y-m-d|in:'.School::today(), 'reason' => 'required|string|min:5|max:5000', 'correction_of_id' => 'nullable|exists:transfers,id'];
    }
}
