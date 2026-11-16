<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    public function index()
    {
        Gate::authorize('permissions.manage');

        return view('users.permissions', ['roles' => Role::with('permissions')->get(), 'permissions' => Permission::all()]);
    }

    public function update(PermissionRequest $r, Role $role)
    {
        abort_if($role->slug === 'administrator' || $role->id === $r->user()->role_id, 403);
        DB::transaction(function () use ($r, $role) {
            $locked = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            $before = $locked->permissions->pluck('name')->all();
            $ids = Permission::whereIn('name', $r->validated('permissions'))->pluck('id');
            $locked->permissions()->sync($ids);
            DB::table('sessions')->whereIn('user_id', User::where('role_id', $locked->id)->select('id'))->delete();
            History::audit($r->user(), 'role.permissions.updated', $locked, ['before' => $before, 'after' => $r->validated('permissions')]);
        });

        return back()->with('success', 'Pemetaan izin diperbarui.');
    }
}
