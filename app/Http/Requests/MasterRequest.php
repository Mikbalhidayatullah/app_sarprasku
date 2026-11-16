<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('masters.manage');
    }

    public function rules(): array
    {
        $type = $this->route('type');
        $config = config('inventory.masters.'.$type);
        abort_unless($config, 404);
        $table = (new $config[0])->getTable();
        $rules = ['name' => 'required|string|max:255', 'code' => ['nullable', 'string', 'max:255', Rule::unique($table)->ignore($this->route('id'))], 'notes' => 'nullable|string|max:5000', 'is_active' => 'required|boolean'];
        if ($type === 'rooms') {
            $rules['location_id'] = ['required', Rule::exists('locations', 'id')];
        }
        if ($type === 'ownerships') {
            $rules['is_bmn'] = 'required|boolean';
        }

        return $rules;
    }
}
