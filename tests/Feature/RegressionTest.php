<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Attachment;
use App\Models\Classification;
use App\Models\Ownership;
use App\Models\Permission;
use App\Models\School;
use App\Services\ReportData;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\InventoryFixture;
use Tests\TestCase;

class RegressionTest extends TestCase
{
    use InventoryFixture,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventorySetUp();
    }

    public function test_known_acquisition_date_derives_year_and_conflicting_year_is_rejected(): void
    {
        $data = ['inventory_number' => 'DATED-01', 'name' => 'Known date', 'ownership_id' => Ownership::first()->id, 'condition' => 'good', 'acquisition_method' => 'purchase', 'acquired_on' => '2024-03-10'];
        $this->actingAs($this->operator)->post('/assets', $data)->assertSessionHasNoErrors();
        $this->assertSame(2024, Asset::where('inventory_number', 'DATED-01')->firstOrFail()->acquisition_year);
        $this->get('/reports/export?year=2024')->assertOk();
        $data['inventory_number'] = 'DATED-02';
        $data['acquisition_year'] = 2023;
        $this->post('/assets', $data)->assertSessionHasErrors('acquisition_year');
    }

    public function test_edit_can_keep_current_inactive_classification_and_ownership(): void
    {
        $class = Classification::create(['name' => 'Old classification', 'code' => '00099', 'is_active' => false]);
        $owner = Ownership::create(['name' => 'Old ownership', 'is_active' => false]);
        $asset = $this->asset(['classification_id' => $class->id, 'ownership_id' => $owner->id]);
        $this->actingAs($this->operator)->get('/assets/'.$asset->id.'/edit')->assertOk()->assertSee('00099 · Old classification')->assertSee('Old ownership');
    }

    public function test_label_uses_full_inventory_and_nup_and_rejects_impossible_small_layout(): void
    {
        $number = 'INV-123456789012345678901234567890123456789012';
        $a = $this->asset(['inventory_number' => $number, 'official_register' => '00123456789012345678901234567890123']);
        $this->actingAs($this->operator)->get('/labels?ids[]='.$a->id)->assertOk()->assertSee($number)->assertSee('00123456789012345678901234567890123');
        $a->update(['inventory_number' => str_repeat('A', 150), 'official_register' => str_repeat('0', 200)]);
        $this->get('/labels?ids[]='.$a->id)->assertSessionHasErrors('ids');
    }

    public function test_historical_actor_names_survive_renaming_before_and_after_approval(): void
    {
        $a = $this->asset();
        $s = app(TransferService::class);
        $original = $this->operator->name;
        $decider = $this->approver->name;
        $t = $s->draft($this->transferData($a), $this->operator);
        $s->submit($t, $this->operator);
        $this->operator->update(['name' => 'Renamed requester']);
        $s->approve($t, $this->approver);
        $this->approver->update(['name' => 'Renamed approver']);
        $row = ReportData::row($t->fresh()->load('asset', 'requester', 'decider'), 'transfers');
        $this->assertSame($original, $row[8]);
        $this->assertSame($decider, $row[9]);
        $this->assertSame($original, AssetEvent::where('type', 'transfer.approved')->first()->data['requested_by']);
    }

    public function test_attachment_metadata_is_hidden_when_read_permission_is_revoked(): void
    {
        $a = $this->asset();
        $viewer = $this->makeUser('viewer');
        Attachment::create(['asset_id' => $a->id, 'path' => 'private.pdf', 'original_name' => 'Secret-document-title.pdf', 'mime' => 'application/pdf', 'size' => 123, 'uploaded_by' => $this->operator->id]);
        $viewer->role->permissions()->detach(Permission::where('name', 'attachments.view')->value('id'));
        $this->actingAs($viewer)->get('/assets/'.$a->id)->assertOk()->assertDontSee('Secret-document-title.pdf');
    }

    public function test_effective_date_uses_school_timezone_instead_of_wib(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 16:30:00', 'UTC'));
        School::first()->update(['timezone' => 'Asia/Jayapura']);
        $this->assertSame('2026-01-02', School::today());
        $a = $this->asset();
        $data = $this->transferData($a);
        $this->actingAs($this->operator)->post('/transfers', $data)->assertSessionHasNoErrors();
        $data = $this->transferData($this->asset());
        $data['effective_on'] = '2026-01-01';
        $this->post('/transfers', $data)->assertSessionHasErrors('effective_on');
    }
}
