<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Models\Asset;
use App\Models\Classification;
use App\Models\Ownership;
use App\Models\Person;
use App\Models\Room;
use App\Models\School;
use App\Services\AssetService;
use App\Services\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssetController extends Controller
{
    public static function options(?Asset $asset = null): array
    {
        return ['classifications' => Classification::where(fn ($q) => $q->where('is_active', true)->when($asset?->classification_id, fn ($q) => $q->orWhere('id', $asset->classification_id)))->orderBy('name')->get(), 'ownerships' => Ownership::where(fn ($q) => $q->where('is_active', true)->when($asset?->ownership_id, fn ($q) => $q->orWhere('id', $asset->ownership_id)))->orderBy('name')->get(), 'rooms' => Room::with('location')->where('is_active', true)->orderBy('name')->get(), 'people' => Person::where('is_active', true)->orderBy('name')->get()];
    }

    public function index()
    {
        Gate::authorize('assets.view');

        return view('assets.index', self::options());
    }

    public function create()
    {
        Gate::authorize('assets.create');

        return view('assets.form', self::options() + ['asset' => new Asset]);
    }

    public function store(AssetRequest $r, AssetService $service)
    {
        $a = $service->create($r->validated(), $r->user());

        return redirect()->route('assets.show', $a)->with('success', 'Unit aset dicatat. Lengkapi data yang belum diverifikasi.');
    }

    public function show(Asset $asset)
    {
        Gate::authorize('view', $asset);
        $asset->load(['room.location', 'person', 'classification', 'ownership', 'procurementItem.procurement.allocations.fundSource']);

        return view('assets.show', self::options() + ['asset' => $asset, 'events' => $asset->events()->paginate(15, ['*'], 'history_page'), 'attachments' => $asset->attachments()->latest()->paginate(10, ['*'], 'files_page'), 'transfers' => $asset->transfers()->with('requester', 'decider')->limit(10)->get()]);
    }

    public function edit(Asset $asset)
    {
        Gate::authorize('update', $asset);

        return view('assets.form', self::options($asset) + ['asset' => $asset]);
    }

    public function update(AssetRequest $r, Asset $asset)
    {
        DB::transaction(function () use ($r, $asset) {
            $a = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $before = $a->only(array_keys($r->validated()));
            $data = $r->validated();
            if ($a->procurement_item_id) {
                unset($data['acquisition_value'],$data['acquired_on'],$data['acquisition_year'],$data['acquisition_method']);
            }
            $a->update($data);
            History::audit($r->user(), 'asset.updated', $a, ['before' => $before, 'after' => $a->only(array_keys($data))]);
            History::event($a, $r->user(), 'asset.corrected', ['before' => $before, 'after' => $a->only(array_keys($data))], School::today());
        });

        return redirect()->route('assets.show',$asset)->with('success','Identitas barang diperbarui. Posisi dan kondisi diubah lewat transaksi terpisah.');
    }
}
