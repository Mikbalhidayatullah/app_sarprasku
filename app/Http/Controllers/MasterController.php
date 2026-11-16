<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Http\Requests\MasterRequest;
use App\Http\Requests\SchoolRequest;
use App\Models\Location;
use App\Models\School;
use App\Services\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MasterController extends Controller
{
    public function index(ListRequest $r, string $type = 'locations')
    {
        Gate::authorize('masters.manage');
        $config = config('inventory.masters.'.$type);
        abort_unless($config, 404);
        $q = $config[0]::query();
        if ($search = $r->validated('q')) {
            $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
        } if (in_array($r->validated('status'), ['active', 'inactive'])) {
            $q->where('is_active', $r->validated('status') === 'active');
        }

        return view('masters.index', ['type' => $type, 'title' => $config[1], 'records' => $q->orderBy('name')->paginate(20)->withQueryString(), 'locations' => Location::orderBy('name')->get(), 'school' => School::firstOrFail()]);
    }

    public function store(MasterRequest $r, string $type)
    {
        DB::transaction(function () use ($r, $type) {
            $class = config('inventory.masters.'.$type)[0];
            $m = $class::create($r->validated());
            History::audit($r->user(), 'master.created', $m, $m->toArray());
        });

        return back()->with('success', 'Master ditambahkan.');
    }

    public function update(MasterRequest $r, string $type, int $id)
    {
        DB::transaction(function () use ($r, $type, $id) {
            $class = config('inventory.masters.'.$type)[0];
            $m = $class::findOrFail($id);
            $before = $m->toArray();
            $m->update($r->validated());
            History::audit($r->user(), 'master.updated', $m, ['before' => $before, 'after' => $m->toArray()]);
        });

        return back()->with('success', 'Master diperbarui; histori tetap dipertahankan.');
    }

    public function school(SchoolRequest $r)
    {
        DB::transaction(function () use ($r) {
            $s = School::firstOrFail();
            $s->update($r->validated());
            History::audit($r->user(), 'school.updated', $s, $r->validated());
        });

        return back()->with('success','Identitas sekolah diperbarui.');
    }
}
