<?php

namespace App\Livewire;

use App\Http\Controllers\AssetController;
use App\Services\AssetQuery;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AssetTable extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $room_id = '';

    #[Url]
    public string $condition = '';

    #[Url]
    public string $lifecycle_status = '';

    #[Url]
    public string $classification_id = '';

    #[Url]
    public string $person_id = '';

    #[Url]
    public string $year = '';

    #[Url]
    public bool $incomplete = false;

    public array $selected = [];

    public function updated($name): void
    {
        if ($name !== 'selected') {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function render()
    {
        Gate::authorize('assets.view');
        $filters = $this->validate(['q' => 'nullable|string|max:150', 'room_id' => 'nullable|integer', 'condition' => 'nullable|in:'.implode(',', array_keys(config('inventory.conditions'))),
            'lifecycle_status' => 'nullable|in:'.implode(',', array_keys(config('inventory.statuses'))), 'classification_id' => 'nullable|integer', 'person_id' => 'nullable|integer', 'year' => 'nullable|integer|min:1900|max:2100', 'incomplete' => 'boolean', 'selected' => 'array|max:100', 'selected.*' => 'uuid']);

        return view('livewire.asset-table', AssetController::options() + ['assets' => AssetQuery::build($filters)->with('room', 'person', 'ownership', 'classification')->orderBy('inventory_number')->paginate(20), 'filters' => array_filter(collect($filters)->except('selected')->all(), fn ($v) => $v !== '' && $v !== false)]);
    }
}
