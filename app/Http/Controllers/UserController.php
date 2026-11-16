<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(ListRequest $r)
    {
        Gate::authorize('users.manage');
        $q = User::with('role');
        if ($search = $r->validated('q')) {
            $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
        } if (in_array($r->validated('status'), ['active', 'inactive'])) {
            $q->where('is_active', $r->validated('status') === 'active');
        }

return view('users.index', ['users' => $q->orderBy('name')->paginate(20)->withQueryString(), 'roles' => Role::all()]);
    }

    public function store(UserRequest $r)
    {
        DB::transaction(function () use ($r) {
            $u = User::create($r->safe()->except('password_confirmation'));
            History::audit($r->user(), 'user.created', $u, ['name' => $u->name, 'role' => $u->role->name]);
        });

        return back()->with('success', 'Pengguna dibuat. Sampaikan password melalui saluran privat.');
    }

    public function update(UserRequest $r, User $user)
    {
        DB::transaction(function () use ($r, $user) {
            $role = Role::where('slug', 'administrator')->lockForUpdate()->firstOrFail();
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $data = $r->validated();
            if ($u->id === $r->user()->id && (int) $data['role_id'] !== $u->role_id) {
                throw ValidationException::withMessages(['role_id' => 'Anda tidak boleh mengubah peran sendiri.']);
            }
            if ($u->role_id === $role->id && (! $data['is_active'] || (int) $data['role_id'] !== $role->id) && User::where('role_id', $role->id)->where('is_active', true)->lockForUpdate()->get()->count() <= 1) {
                throw ValidationException::withMessages(['is_active' => 'Administrator aktif terakhir tidak dapat dinonaktifkan atau diturunkan.']);
            }
            if (empty($data['password'])) {
                unset($data['password']);
            }
            unset($data['password_confirmation']);
            $before = $u->only(['name', 'email', 'role_id', 'is_active']);
            $u->fill($data);
            if (! $u->is_active || $u->isDirty('password') || $u->isDirty('role_id')) {
                $u->remember_token = null;
                DB::table('sessions')->where('user_id', $u->id)->delete();
            }
            $u->save();
            History::audit($r->user(), 'user.updated', $u, ['before' => $before, 'after' => $u->only(['name', 'email', 'role_id', 'is_active'])]);
        });

        return back()->with('success','Pengguna diperbarui.');
    }
}
