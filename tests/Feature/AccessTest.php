<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\FoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FoundationSeeder::class);
        $this->withoutVite();
    }

    private function account(string $role = 'administrator', bool $active = true): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', $role)->value('id'), 'is_active' => $active]);
    }

    public function test_guests_are_redirected_and_public_registration_does_not_exist(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_login_logout_and_inactive_login(): void
    {
        $user = $this->account();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_reuse_session(): void
    {
        $user = $this->account();
        $this->actingAs($user);
        DB::table('users')->where('id', $user->id)->update(['is_active' => false]);
        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_roles_and_self_escalation_are_enforced(): void
    {
        $viewer = $this->account('viewer');
        $this->actingAs($viewer)->get('/')->assertOk();
        $this->get('/users')->assertForbidden();
        $this->post('/assets', [])->assertForbidden();
        $this->post('/masters/locations', ['name' => 'Injected'])->assertForbidden();
        $this->put('/users/'.$viewer->id, ['role_id' => Role::where('slug', 'administrator')->value('id')])->assertForbidden();
        $this->assertSame('viewer', $viewer->fresh()->role->slug);
    }

    public function test_last_active_admin_cannot_be_disabled_or_demoted(): void
    {
        $admin = $this->account();
        $this->actingAs($admin)->put('/users/'.$admin->id, [
            'name' => $admin->name, 'email' => $admin->email,
            'role_id' => Role::where('slug', 'viewer')->value('id'), 'is_active' => false,
        ])->assertSessionHasErrors();
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertSame('administrator', $admin->fresh()->role->slug);
    }

    public function test_admin_deactivation_revokes_stored_sessions_and_keeps_actor(): void
    {
        $admin = $this->account();
        $other = $this->account('operator');
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $other->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($admin)->put('/users/'.$other->id, ['name' => $other->name, 'email' => $other->email, 'role_id' => $other->role_id, 'is_active' => false])->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['user_id' => $other->id]);
        $this->assertDatabaseHas('users', ['id' => $other->id, 'is_active' => false]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'user.updated']);
    }

    public function test_master_preserves_codes_and_can_be_deactivated(): void
    {
        $this->actingAs($this->account())->post('/masters/classifications', ['name' => 'Referensi sekolah', 'code' => '000123', 'is_active' => true])->assertRedirect();
        $this->assertDatabaseHas('classifications', ['code' => '000123']);
    }
}
