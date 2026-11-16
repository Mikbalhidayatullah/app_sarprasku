<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecisionRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Asset;
use App\Models\School;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TransferController extends Controller
{
    public function index(ListRequest $r)
    {
        Gate::authorize('assets.view');
        abort_unless($r->user()->can('transfers.create') || $r->user()->can('transfers.approve') || $r->user()->can('reports.view'), 403);
        $q = Transfer::with('asset', 'requester', 'decider')->whereHas('asset', fn ($q) => $q->where('school_id', School::first()->id));
        if ($search = $r->validated('q')) {
            $q->whereHas('asset', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('inventory_number', 'like', '%'.$search.'%')));
        }
        if (array_key_exists($r->query('status', ''), config('inventory.transfer_statuses'))) {
            $q->where('status', $r->query('status'));
        }

        return view('transfers.index', ['transfers' => $q->latest()->paginate(20)->withQueryString()]);
    }

    public function create(Request $r)
    {
        Gate::authorize('transfers.create');
        $asset = Asset::findOrFail($r->query('asset'));
        Gate::authorize('view', $asset);

        return view('transfers.form', AssetController::options() + ['asset' => $asset]);
    }

    public function store(TransferRequest $r, TransferService $s)
    {
        $t = $s->draft($r->validated(), $r->user());

        return redirect()->route('transfers.show', $t)->with('success', 'Draft disimpan. Posisi barang belum berubah.');
    }

    public function show(Transfer $transfer)
    {
        Gate::authorize('view', $transfer);

        return view('transfers.show', ['transfer' => $transfer->load('asset', 'requester', 'decider', 'attachments')]);
    }

    public function submit(Request $r, Transfer $transfer, TransferService $s)
    {
        $s->submit($transfer, $r->user());

        return back()->with('success', 'Pengajuan menunggu persetujuan.');
    }

    public function approve(Request $r, Transfer $transfer, TransferService $s)
    {
        $s->approve($transfer, $r->user());

        return back()->with('success', 'Perpindahan disetujui dan posisi barang diperbarui.');
    }

    public function reject(DecisionRequest $r, Transfer $transfer, TransferService $s)
    {
        $s->reject($transfer, $r->user(), $r->validated('decision_note'));

        return back()->with('success', 'Pengajuan ditolak.');
    }

    public function cancel(DecisionRequest $r, Transfer $transfer, TransferService $s)
    {
        $s->cancel($transfer,$r->user(),$r->validated('decision_note'));

        return back()->with('success','Perpindahan dibatalkan.');
    }
}
