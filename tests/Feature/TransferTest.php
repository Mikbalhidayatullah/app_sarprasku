<?php

namespace Tests\Feature;

use App\Models\AssetEvent;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\InventoryFixture;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use InventoryFixture, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventorySetUp();
    }

    private function submit(): Transfer
    {
        $asset = $this->asset();
        $this->actingAs($this->operator)->post('/transfers', $this->transferData($asset))->assertSessionHasNoErrors();
        $transfer = Transfer::latest('id')->first();
        $this->post('/transfers/'.$transfer->id.'/submit')->assertSessionHasNoErrors();

        return $transfer->fresh();
    }

    public function test_submission_does_not_move_and_approval_moves_once_with_history(): void
    {
        $transfer = $this->submit();
        $this->assertSame($this->origin->id, $transfer->asset->room_id);
        $this->actingAs($this->approver)->get('/transfers/'.$transfer->id)->assertOk()->assertSee('Kebutuhan pembelajaran');
        $this->actingAs($this->approver)->post('/transfers/'.$transfer->id.'/approve')->assertSessionHasNoErrors();
        $this->assertSame($this->destination->id, $transfer->asset->fresh()->room_id);
        $this->get('/transfers/'.$transfer->id)->assertOk()->assertSee('Transaksi disetujui tidak dapat diedit');
        $this->assertDatabaseHas('transfers', ['id' => $transfer->id, 'status' => 'approved', 'active_asset_id' => null]);
        $this->post('/transfers/'.$transfer->id.'/approve')->assertSessionHasErrors();
        $this->assertSame(1, AssetEvent::where('type', 'transfer.approved')->count());
    }

    public function test_self_approval_is_forbidden_even_with_both_permissions(): void
    {
        $admin = $this->makeUser('administrator');
        $asset = $this->asset();
        $this->actingAs($admin)->post('/transfers', $this->transferData($asset));
        $transfer = Transfer::first();
        $this->post('/transfers/'.$transfer->id.'/submit');
        $this->post('/transfers/'.$transfer->id.'/approve')->assertForbidden();
        $this->assertSame($this->origin->id, $asset->fresh()->room_id);
    }

    public function test_duplicate_active_request_and_database_slot_are_rejected(): void
    {
        $t = $this->submit();
        $this->actingAs($this->operator)->post('/transfers', $this->transferData($t->asset))->assertSessionHasErrors();
        $this->assertDatabaseCount('transfers', 1);
        $duplicate = $t->replicate();
        $this->expectException(UniqueConstraintViolationException::class);
        $duplicate->save();
    }

    public function test_rejection_and_cancellation_free_slot_without_moving(): void
    {
        $t = $this->submit();
        $this->actingAs($this->approver)->post('/transfers/'.$t->id.'/reject', ['decision_note' => 'Tujuan tidak sesuai'])->assertSessionHasNoErrors();
        $this->assertSame($this->origin->id, $t->asset->fresh()->room_id);
        $this->actingAs($this->operator)->post('/transfers', $this->transferData($t->asset))->assertSessionHasNoErrors();
        $new = Transfer::latest('id')->first();
        $this->post('/transfers/'.$new->id.'/cancel', ['decision_note' => 'Dibatalkan pengaju'])->assertSessionHasNoErrors();
        $this->assertNull($new->fresh()->active_asset_id);
    }

    public function test_conflicting_origin_and_non_today_effective_date_are_rejected(): void
    {
        $t = $this->submit();
        DB::table('assets')->where('id', $t->asset_id)->update(['room_id' => $this->destination->id]);
        $this->actingAs($this->approver)->post('/transfers/'.$t->id.'/approve')->assertSessionHasErrors();
        $data = $this->transferData($this->asset());
        $data['effective_on'] = '2020-01-01';
        $this->actingAs($this->operator)->post('/transfers', $data)->assertSessionHasErrors('effective_on');
    }

    public function test_history_keeps_snapshot_and_correction_references_original(): void
    {
        $t = $this->submit();
        $this->origin->update(['name' => 'Nama baru']);
        $this->actingAs($this->approver)->post('/transfers/'.$t->id.'/approve');
        $this->assertSame('Ruang 1', $t->fresh()->origin_snapshot['room']);
        $data = $this->transferData($t->asset->fresh());
        $data['to_room_id'] = $this->origin->id;
        $data['correction_of_id'] = $t->id;
        $data['reason'] = 'Koreksi berdasarkan berita acara';
        $this->actingAs($this->operator)->post('/transfers', $data)->assertSessionHasNoErrors();
        $correction = Transfer::latest('id')->first();
        $this->post('/transfers/'.$correction->id.'/submit');
        $this->actingAs($this->approver)->post('/transfers/'.$correction->id.'/approve')->assertSessionHasNoErrors();
        $this->assertSame('approved', $t->fresh()->status);
        $this->assertSame($this->origin->id, $t->asset->fresh()->room_id);
        $this->assertSame(2, AssetEvent::where('type', 'transfer.approved')->count());
    }

    public function test_failure_writing_history_rolls_back_position_and_decision(): void
    {
        $t = $this->submit();
        Event::listen('eloquent.creating: '.AssetEvent::class, function (AssetEvent $event) {
            $event->actor_id = 999999999;
        });
        try {
            app(TransferService::class)->approve($t, $this->approver);
            $this->fail('Expected history failure');
        } catch (QueryException $e) {
            $this->assertStringContainsString('foreign key constraint', $e->getMessage());
        } finally {
            Event::forget('eloquent.creating: '.AssetEvent::class);
        }
        $this->assertSame($this->origin->id, $t->asset->fresh()->room_id);
        $this->assertSame('submitted', $t->fresh()->status);
    }
}
