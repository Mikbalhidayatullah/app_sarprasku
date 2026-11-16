<?php

namespace Tests\Feature;

use App\Livewire\AssetTable;
use App\Models\School;
use App\Support\Display;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InventoryFixture;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use InventoryFixture,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventorySetUp();
    }

    public static function matrix(): array
    {
        return [
            ['administrator', '/users', 200], ['administrator', '/masters', 200], ['administrator', '/permissions', 200], ['administrator', '/audit', 200],
            ['operator', '/users', 403], ['operator', '/masters', 403], ['operator', '/procurements', 200], ['operator', '/transfers', 200], ['operator', '/maintenances', 200],
            ['approver', '/users', 403], ['approver', '/procurements', 403], ['approver', '/transfers', 200], ['approver', '/maintenances', 403],
            ['viewer', '/procurements', 403], ['viewer', '/maintenances', 403], ['viewer', '/reports', 200], ['viewer', '/assets', 200], ['viewer', '/audit', 403],
        ];
    }

    #[DataProvider('matrix')]
    public function test_role_matrix_protects_server_endpoints(string $role, string $path, int $status): void
    {
        $this->actingAs($this->makeUser($role))->get($path)->assertStatus($status);
    }

    public function test_password_reset_uses_real_broker_and_revokes_sessions(): void
    {
        $user = $this->operator;
        $token = Password::broker()->createToken($user);
        DB::table('sessions')->insert(['id' => 'reset-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'NewTestPassword1947', 'password_confirmation' => 'NewTestPassword1947'])->assertSessionHasNoErrors()->assertRedirect('/login');
        $this->assertTrue(Hash::check('NewTestPassword1947', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'AnotherPassword1947', 'password_confirmation' => 'AnotherPassword1947'])->assertSessionHasErrors();
    }

    public function test_login_is_rate_limited_and_redirect_cannot_point_outside_site(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', ['email' => $this->operator->email, 'password' => 'wrong']);
        }
        $response->assertStatus(429);
        $user = $this->makeUser('viewer');
        $this->withSession(['url.intended' => 'https://attacker.invalid/steal'])->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
    }

    public function test_administrator_cannot_change_own_role_permissions(): void
    {
        $admin = $this->makeUser('administrator');
        $this->actingAs($admin)->put('/permissions/'.$admin->role_id, ['permissions' => []])->assertForbidden();
        $this->assertTrue($admin->hasPermission('users.manage'));
    }

    public function test_outside_school_object_is_forbidden_even_for_administrator(): void
    {
        $other = School::create(['name' => 'Different installation scope', 'timezone' => 'Asia/Makassar']);
        $a = $this->asset(['school_id' => $other->id]);
        $this->actingAs($this->makeUser('administrator'))->get('/assets/'.$a->id)->assertForbidden();
        $this->get('/labels?ids[]='.$a->id)->assertForbidden();
        $this->get('/reports/export')->assertOk();
    }

    public function test_livewire_rechecks_permissions_and_escapes_untrusted_asset_names(): void
    {
        $this->asset(['name' => '<script>alert(1)</script>']);
        $this->actingAs($this->operator);
        Livewire::test(AssetTable::class)->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;')->set('q', 'not-found')->assertDontSee('alert(1)');
        $user = $this->makeUser('viewer');
        $user->role->permissions()->detach();
        $this->actingAs($user);
        Livewire::test(AssetTable::class)->assertForbidden();
    }

    public function test_csrf_protection_is_active_for_http_changes(): void
    {
        $this->app['env'] = 'local';
        $this->post('/login', ['email' => $this->operator->email, 'password' => 'password'])->assertStatus(419);
    }

    public static function csvInputs(): array
    {
        return [[" \t=1+1", "' \t=1+1"], ['+SUM(1)', "'+SUM(1)"], ['-1+2', "'-1+2"], ['@SUM(1)', "'@SUM(1)"], ["\r=1", "'\r=1"], ['00123', "'00123"], ['Normal', 'Normal']];
    }

    #[DataProvider('csvInputs')]
    public function test_csv_neutralizes_control_characters_and_formulas(string $input, string $expected): void
    {
        $this->assertSame($expected, Display::csv($input));
    }

    public function test_all_workflow_forms_and_detail_pages_render(): void
    {
        $a = $this->asset();
        $admin = $this->makeUser('administrator');
        $this->actingAs($admin);
        foreach (['/assets/create', '/assets/'.$a->id, '/assets/'.$a->id.'/edit', '/procurements/create', '/transfers/create?asset='.$a->id, '/maintenances/create?asset='.$a->id] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (['locations', 'rooms', 'people', 'classifications', 'fund-sources', 'ownerships'] as $type) {
            $this->get('/masters/'.$type)->assertOk();
        }
    }
}
