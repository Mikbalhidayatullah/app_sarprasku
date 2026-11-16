<?php

namespace App\Http\Requests;

use App\Models\School;
use App\Support\ActiveReference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('procurements.manage');
    }

    public function rules(): array
    {
        return [
            'reference' => 'required|string|max:150|unique:procurements,reference', 'supplier' => 'nullable|string|max:255',
            'acquired_on' => 'required|date_format:Y-m-d|before_or_equal:'.School::today(), 'method' => ['required', Rule::in(['purchase', 'grant', 'transfer'])],
            'notes' => 'nullable|string|max:10000', 'room_id' => ['nullable', ActiveReference::rule('rooms')], 'person_id' => ['nullable', ActiveReference::rule('people')],
            'items' => 'required|array|min:1|max:100', 'items.*' => 'array:name,quantity,unit_price,inventory_prefix,classification_id,ownership_id',
            'items.*.name' => 'required|string|max:255', 'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.unit_price' => 'required|decimal:0,2|min:0|max:9999999999999.99', 'items.*.inventory_prefix' => 'required|string|max:120|regex:/^[A-Za-z0-9._\/-]+$/',
            'items.*.classification_id' => ['nullable', ActiveReference::rule('classifications')], 'items.*.ownership_id' => ['required', ActiveReference::rule('ownerships')],
            'allocations' => 'required|array|min:1|max:50', 'allocations.*' => 'array:fund_source_id,budget_year,document_reference,amount',
            'allocations.*.fund_source_id' => ['required', ActiveReference::rule('fund_sources')], 'allocations.*.budget_year' => 'required|integer|min:1900|max:2100',
            'allocations.*.document_reference' => 'required|string|max:255', 'allocations.*.amount' => 'required|decimal:0,2|min:0|max:9999999999999999.99',
        ];
    }
}
