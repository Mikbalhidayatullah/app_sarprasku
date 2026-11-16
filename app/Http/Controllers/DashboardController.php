<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\School;
use App\Models\Transfer;
use App\Services\AssetQuery;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke()
    {
        Gate::authorize('dashboard.view');
        $assets = AssetQuery::build([]);

        return view('dashboard', ['total' => (clone $assets)->count(), 'incomplete' => AssetQuery::build(['incomplete' => true])->count(),
            'pending' => Transfer::where('status', 'submitted')->count(), 'conditionSummary' => (clone $assets)->selectRaw('`condition`, COUNT(*) as total')->groupBy('condition')->pluck('total', 'condition'),
            'statusSummary' => (clone $assets)->selectRaw('lifecycle_status, COUNT(*) as total')->groupBy('lifecycle_status')->pluck('total', 'lifecycle_status'),
            'rooms' => Room::withCount(['assets' => fn ($q) => $q->where('school_id', School::query()->value('id'))])->orderByDesc('assets_count')->limit(8)->get(),
            'recent' => Transfer::with('asset', 'requester')->latest()->limit(5)->get()]);
    }
}
