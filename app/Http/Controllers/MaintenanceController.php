<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConditionRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\MaintenanceRequest;
use App\Http\Requests\StatusRequest;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\School;
use App\Models\Transfer;
use App\Services\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MaintenanceController extends Controller
{
    public function index(ListRequest $r)
    {
        Gate::authorize('maintenance.manage');
        $q = Maintenance::with('asset', 'creator')->whereHas('asset', fn ($q) => $q->where('school_id', School::query()->value('id')));
        if ($search = $r->validated('q')) {
            $q->where(fn ($q) => $q->where('vendor', 'like', '%'.$search.'%')->orWhereHas('asset', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('inventory_number', 'like', '%'.$search.'%'))));
        }

return view('maintenances.index', ['maintenances' => $q->latest('performed_on')->paginate(20)->withQueryString()]);
    }

    public function create(Request $r)
    {
        Gate::authorize('maintenance.manage');
        $asset = Asset::findOrFail($r->query('asset'));
        Gate::authorize('view', $asset);

        return view('maintenances.form', ['asset' => $asset]);
    }

    public function store(MaintenanceRequest $r)
    {
        $m = DB::transaction(function () use ($r) {
            $data = $r->validated();
            $a = Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();
            Gate::authorize('view', $a);
            if (in_array($a->lifecycle_status, ['archived', 'disposed'])) {
                throw ValidationException::withMessages(['asset_id' => 'Aktifkan kembali aset melalui administrator sebelum pemeliharaan.']);
            }
            $m = Maintenance::create($data + ['created_by' => $r->user()->id, 'creator_snapshot' => $r->user()->name]);
            $before = $a->condition;
            $a->update(['condition' => $data['condition_after']]);
            History::event($a, $r->user(), 'maintenance.recorded', $data + ['maintenance_id' => $m->id, 'condition_before' => $before], $data['performed_on']);
            History::audit($r->user(), 'maintenance.created', $m, $data);

            return $m;
        });

        return redirect()->route('assets.show', $m->asset_id)->with('success', 'Pemeliharaan dan kondisi hasil pemeriksaan dicatat.');
    }

    public function condition(ConditionRequest $r, Asset $asset)
    {
        DB::transaction(function () use ($r, $asset) {
            $a = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $before = $a->condition;
            $a->update(['condition' => $r->validated('condition')]);
            History::event($a, $r->user(), 'condition.changed', ['before' => $before] + $r->validated(), $r->validated('inspected_on'));
            History::audit($r->user(), 'condition.changed', $a, ['before' => $before, 'after' => $a->condition]);
        });

        return back()->with('success', 'Kondisi dan hasil pemeriksaan dicatat. Tambahkan foto pendukung pada lampiran.');
    }

    public function status(StatusRequest $r, Asset $asset)
    {
        DB::transaction(function () use ($r, $asset) {
            $a = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            if ($a->lifecycle_status === 'disposed' && $r->user()->cannot('dispose', $a)) {
                abort(403);
            }
            if ($a->lifecycle_status === 'archived' && $r->user()->cannot('archive', $a)) {
                abort(403);
            }
            if (Transfer::where('active_asset_id', $a->id)->exists()) {
                throw ValidationException::withMessages(['lifecycle_status' => 'Selesaikan/batalkan perpindahan aktif terlebih dahulu.']);
            }
            $before = $a->lifecycle_status;
            $a->update(collect($r->validated())->only('lifecycle_status', 'disposal_reference')->all());
            History::event($a, $r->user(), 'status.changed', ['before' => $before] + $r->validated(), School::today());
            History::audit($r->user(), 'status.changed', $a, ['before' => $before] + $r->validated());
        });

        return back()->with('success','Status internal dicatat. Ini tidak menyelesaikan prosedur penghapusan BMN resmi.');
    }
}
