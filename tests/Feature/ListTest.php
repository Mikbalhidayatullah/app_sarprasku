<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Ownership;
use App\Support\Display;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InventoryFixture;
use Tests\TestCase;

class ListTest extends TestCase
{
    use InventoryFixture,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventorySetUp();
    }

    public function test_user_and_master_search_are_applied_on_server(): void
    {
        $admin = $this->makeUser('administrator');
        $this->operator->update(['name' => 'Operator dicari']);
        $this->actingAs($admin)->get('/users?q=Operator%20dicari')->assertOk()->assertSee('Operator dicari')->assertDontSee($this->approver->email);
        Location::create(['name' => 'Tempat rahasia']);
        $this->get('/masters/locations?q=Gedung')->assertOk()->assertSee('Gedung A')->assertDontSee('Tempat rahasia');
    }

    public function test_inactive_master_cannot_be_assigned_to_new_assets_or_procurements(): void
    {
        $this->origin->update(['is_active' => false]);
        $this->actingAs($this->operator)->post('/assets', ['inventory_number' => 'A-1', 'name' => 'New', 'ownership_id' => Ownership::first()->id, 'room_id' => $this->origin->id, 'acquisition_method' => 'unknown', 'condition' => 'unknown'])->assertSessionHasErrors('room_id');
        $data = $this->procurementData();
        $this->post('/procurements', $data)->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_large_money_display_keeps_exact_cents(): void
    {
        $this->assertSame('Rp 9.999.999.999.999.999,99', Display::money('9999999999999999.99'));
    }
}
