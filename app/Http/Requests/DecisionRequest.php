<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->routeIs('transfers.cancel') ? 'cancel' : 'approve', $this->route('transfer'));
    }

    public function rules(): array
    {
        return ['decision_note' => 'required|string|min:5|max:5000'];
    }
}
