<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Http\Requests\ProcurementRequest;
use App\Models\Asset;
use App\Models\FundSource;
use App\Models\Procurement;
use App\Services\ProcurementService;
use Illuminate\Support\Facades\Gate;

class ProcurementController extends Controller
{
    public function index(ListRequest $r)
    {
        Gate::authorize('procurements.manage');
        $q = Procurement::with('creator')->withCount('items');
        if ($search = $r->validated('q')) {
            $q->where(fn ($q) => $q->where('reference', 'like', '%'.$search.'%')->orWhere('supplier', 'like', '%'.$search.'%'));
        }

return view('procurements.index', ['procurements' => $q->latest()->paginate(20)->withQueryString()]);
    }

    public function create()
    {
        Gate::authorize('procurements.manage');

        return view('procurements.form', AssetController::options() + ['fundSources' => FundSource::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(ProcurementRequest $r, ProcurementService $service)
    {
        $p = $service->create($r->validated(), $r->user());

        return redirect()->route('procurements.show', $p)->with('success', 'Pengadaan dan seluruh identitas unit berhasil dicatat.');
    }

    public function show(Procurement $procurement)
    {
        Gate::authorize('procurements.manage');

        return view('procurements.show', ['procurement' => $procurement->load('items', 'allocations.fundSource'), 'assets' => Asset::whereHas('procurementItem', fn ($q) => $q->where('procurement_id', $procurement->id))->paginate(25)]);
    }
}
