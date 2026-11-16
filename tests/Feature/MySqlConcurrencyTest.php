<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\InventoryFixture;
use Tests\TestCase;

class MySqlConcurrencyTest extends TestCase
{
    use DatabaseMigrations,InventoryFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventorySetUp();
    }

    private function contend(Asset $asset, array $args): void
    {
        $commands = [];
        DB::beginTransaction();
        Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
        try {
            for ($i = 0; $i < 2; $i++) {
                $p = new Process(array_merge([PHP_BINARY, base_path('tests/Support/transfer-worker.php')], $args), base_path(), ['APP_ENV' => 'testing']);
                $p->setTimeout(20);
                $p->start();
                $commands[] = $p;
            }
            $deadline = microtime(true) + 12;
            $waiters = 0;
            do {
                $waiters = (int) DB::selectOne('SELECT COUNT(*) total FROM performance_schema.data_lock_waits')->total;
                if ($waiters >= 2) {
                    break;
                }
                usleep(100000);
            } while (microtime(true) < $deadline);
            $this->assertGreaterThanOrEqual(2, $waiters, 'Both independent MySQL connections must be blocked on the asset row before release.');
            DB::commit();
            $codes = [];
            foreach ($commands as $p) {
                $p->wait();
                $codes[] = $p->getExitCode();
                $this->assertContains($p->getOutput(), ['accepted', 'conflict'], $p->getErrorOutput());
            }
            sort($codes);
            $this->assertSame([0, 2], $codes);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($commands as $p) {
                if ($p->isRunning()) {
                    $p->stop();
                }
            }
        }
    }

    public function test_parallel_drafts_leave_one_active_slot(): void
    {
        $a = $this->asset();
        $this->contend($a, ['draft', $a->id, (string) $this->operator->id, (string) $this->destination->id, (string) $this->person->id]);
        $this->assertSame(1, Transfer::where('active_asset_id', $a->id)->count());
        $this->assertSame($this->origin->id, $a->fresh()->room_id);
    }

    public function test_parallel_approvals_move_unit_once(): void
    {
        $a = $this->asset();
        $service = app(TransferService::class);
        $t = $service->draft($this->transferData($a), $this->operator);
        $service->submit($t, $this->operator);
        $this->contend($a, ['approve', (string) $t->id, (string) $this->approver->id]);
        $this->assertSame($this->destination->id, $a->fresh()->room_id);
        $this->assertSame(1, AssetEvent::where('type','transfer.approved')->count());
    }
}
