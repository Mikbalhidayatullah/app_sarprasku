<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Ownership;
use App\Models\Procurement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InventoryFixture;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use InventoryFixture, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventorySetUp();
    }

    public function test_procurement_creates_distinct_units_with_exact_decimal_total(): void
    {
        $data = $this->procurementData();
        $this->actingAs($this->operator)->post('/procurements', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(3, Asset::count());
        $this->assertSame(3, Asset::distinct()->count('inventory_number'));
        $this->assertSame('3000.30', Procurement::first()->total);
        $this->assertDatabaseHas('assets', ['inventory_number' => 'KRS-2026-0001', 'acquisition_value' => '1000.10']);
        $this->assertDatabaseCount('asset_events', 3);
        $this->get('/procurements/'.Procurement::first()->id)->assertOk()->assertSee('KRS-2026-0003')->assertSee('Rp 3.000,30');
    }

    public function test_unbalanced_funding_rolls_back_everything(): void
    {
        $data = $this->procurementData();
        $data['allocations'][0]['amount'] = '3000.29';
        $this->actingAs($this->operator)->post('/procurements', $data)->assertSessionHasErrors('allocations');
        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('procurements', 0);
    }

    public function test_duplicate_inventory_number_is_rejected_and_unknown_data_stays_null(): void
    {
        $data = ['inventory_number' => '001-OLD', 'name' => 'Aset lama', 'ownership_id' => Ownership::first()->id, 'condition' => 'unknown', 'lifecycle_status' => 'active', 'acquisition_method' => 'unknown'];
        $this->actingAs($this->operator)->post('/assets', $data)->assertSessionHasNoErrors()->assertRedirect();
        $asset = Asset::first();
        $this->assertNull($asset->acquisition_value);
        $this->assertNull($asset->acquired_on);
        $this->post('/assets', $data)->assertSessionHasErrors('inventory_number');
        $this->assertDatabaseCount('assets', 1);
    }

    public function test_asset_metadata_cannot_directly_change_position_or_actor_or_school(): void
    {
        $asset = $this->asset();
        $this->actingAs($this->operator)->put('/assets/'.$asset->id, [
            'name' => 'Laptop terkoreksi', 'inventory_number' => $asset->inventory_number, 'ownership_id' => $asset->ownership_id,
            'acquisition_method' => 'unknown', 'room_id' => $this->destination->id, 'person_id' => null,
            'created_by' => $this->approver->id, 'school_id' => 999, 'condition' => 'heavy_damage', 'lifecycle_status' => 'disposed',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $asset->refresh();
        $this->assertSame($this->origin->id, $asset->room_id);
        $this->assertSame('good', $asset->condition);
        $this->assertSame('active', $asset->lifecycle_status);
        $this->assertSame($this->operator->id, $asset->created_by);
    }

    public function test_second_procurement_with_colliding_unit_rolls_back_all_rows(): void
    {
        $data = $this->procurementData();
        $this->asset(['inventory_number' => 'KRS-2026-0002']);
        $this->actingAs($this->operator)->post('/procurements', $data)->assertSessionHasErrors();
        $this->assertDatabaseCount('assets', 1);
        $this->assertDatabaseCount('procurements', 0);
    }
}
