<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('users.manage');
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:255', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))],
            'role_id' => 'required|exists:roles,id', 'is_active' => 'required|boolean',
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'confirmed', Password::min(12)->letters()->numbers()]];
    }
}
