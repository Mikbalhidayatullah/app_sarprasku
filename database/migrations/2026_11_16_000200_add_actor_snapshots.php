<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $t) {
            $t->string('requester_snapshot')->nullable();
            $t->string('decider_snapshot')->nullable();
        });
        Schema::table('maintenances', fn (Blueprint $t) => $t->string('creator_snapshot')->nullable());
        Schema::table('procurements', fn (Blueprint $t) => $t->string('creator_snapshot')->nullable());
        DB::table('transfers')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $request = DB::table('asset_events')->where('asset_id', $row->asset_id)->where('type', 'transfer.drafted')->where('data->transfer_id', $row->id)->value('actor_snapshot');
                $decision = DB::table('asset_events')->where('asset_id', $row->asset_id)->whereIn('type', ['transfer.approved', 'transfer.rejected', 'transfer.cancelled'])->where('data->transfer_id', $row->id)->value('actor_snapshot');
                DB::table('transfers')->where('id', $row->id)->update(['requester_snapshot' => $request, 'decider_snapshot' => $decision]);
            }
        });
        foreach (['maintenances' => 'maintenance.created', 'procurements' => 'procurement.created'] as $table => $action) {
            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $action) {
                foreach ($rows as $row) {
                    $name = DB::table('audit_logs')->where('action', $action)->where('subject_id', (string) $row->id)->value('actor_snapshot');
                    DB::table($table)->where('id', $row->id)->update(['creator_snapshot' => $name]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('transfers', fn (Blueprint $t) => $t->dropColumn(['requester_snapshot', 'decider_snapshot']));
        Schema::table('maintenances', fn (Blueprint $t) => $t->dropColumn('creator_snapshot'));
        Schema::table('procurements', fn (Blueprint $t) => $t->dropColumn('creator_snapshot'));
    }
};
