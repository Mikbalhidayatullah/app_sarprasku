<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Person;
use App\Models\Room;
use App\Models\School;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransferService
{
    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['transfer' => $message]);
    }

    private function today(string $date): void
    {
        if ($date !== School::today()) {
            throw ValidationException::withMessages(['effective_on' => 'Tanggal efektif harus hari ini pada zona waktu sekolah. Batalkan dan buat pengajuan baru bila tanggal sudah berganti.']);
        }
    }

    private function snapshot(?int $room, ?int $person): array
    {
        $r = $room ? Room::with('location')->findOrFail($room) : null;

        return ['room' => $r?->name, 'location' => $r?->location?->name, 'person' => $person ? Person::findOrFail($person)->name : null];
    }

    public function draft(array $data, User $actor): Transfer
    {
        Gate::forUser($actor)->authorize('transfers.create');

        return DB::transaction(function () use ($data, $actor) {
            $asset = Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('view', $asset);
            $this->today($data['effective_on']);
            if (in_array($asset->lifecycle_status, ['archived', 'disposed'])) {
                $this->invalid('Aset diarsipkan/dilepas tidak dapat dipindahkan.');
            }
            if (Transfer::where('active_asset_id', $asset->id)->exists()) {
                $this->invalid('Unit sudah memiliki perpindahan aktif. Selesaikan atau batalkan terlebih dahulu.');
            }
            $room = (int) $data['to_room_id'];
            $person = isset($data['to_person_id']) ? (int) $data['to_person_id'] : null;
            if ($asset->room_id === $room && $asset->person_id === $person) {
                $this->invalid('Tujuan harus berbeda dari posisi atau penanggung jawab saat ini.');
            }
            if (! empty($data['correction_of_id'])) {
                $original = Transfer::whereKey($data['correction_of_id'])->where('asset_id', $asset->id)->where('status', 'approved')->first();
                if (! $original) {
                    $this->invalid('Koreksi harus merujuk perpindahan disetujui milik unit yang sama.');
                }
            }
            $transfer = Transfer::create([
                'asset_id' => $asset->id, 'active_asset_id' => $asset->id, 'from_room_id' => $asset->room_id, 'from_person_id' => $asset->person_id,
                'to_room_id' => $room, 'to_person_id' => $person, 'origin_snapshot' => $this->snapshot($asset->room_id, $asset->person_id),
                'destination_snapshot' => $this->snapshot($room, $person), 'status' => 'draft', 'requested_by' => $actor->id, 'requester_snapshot' => $actor->name,
                'effective_on' => $data['effective_on'], 'reason' => $data['reason'], 'correction_of_id' => $data['correction_of_id'] ?? null,
            ]);
            History::event($asset, $actor, 'transfer.drafted', ['transfer_id' => $transfer->id, 'origin' => $transfer->origin_snapshot, 'destination' => $transfer->destination_snapshot, 'reason' => $transfer->reason, 'correction_of_id' => $transfer->correction_of_id], School::today());
            History::audit($actor, 'transfer.drafted', $transfer, ['asset_id' => $asset->id]);

            return $transfer;
        }, 3);
    }

    private function locked(Transfer $transfer, User $actor, string $ability, callable $action): Transfer
    {
        return DB::transaction(function () use ($transfer, $actor, $ability, $action) {
            $asset = Asset::whereKey($transfer->asset_id)->lockForUpdate()->firstOrFail();
            $current = Transfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize($ability, $current);
            $action($current, $asset);
            $current->save();

            return $current;
        }, 3);
    }

    private function originMatches(Transfer $t, Asset $a): void
    {
        if ($t->from_room_id !== $a->room_id || $t->from_person_id !== $a->person_id || in_array($a->lifecycle_status, ['archived', 'disposed'])) {
            $this->invalid('Posisi/status aset telah berubah. Batalkan pengajuan dan periksa posisi terkini.');
        }
    }

    public function submit(Transfer $t, User $actor): Transfer
    {
        return $this->locked($t, $actor, 'submit', function ($t, $a) use ($actor) {
            if ($t->status !== 'draft') {
                $this->invalid('Hanya draft dapat diajukan.');
            }
            $this->today($t->effective_on->toDateString());
            $this->originMatches($t, $a);
            $t->status = 'submitted';
            $t->submitted_at = now();
            History::event($a, $actor, 'transfer.submitted', ['transfer_id' => $t->id, 'reason' => $t->reason], School::today());
            History::audit($actor, 'transfer.submitted', $t);
        });
    }

    public function approve(Transfer $t, User $actor): Transfer
    {
        return $this->locked($t, $actor, 'approve', function ($t, $a) use ($actor) {
            if ($t->status !== 'submitted') {
                $this->invalid('Pengajuan sudah diproses atau belum diajukan.');
            }
            $this->today($t->effective_on->toDateString());
            $this->originMatches($t, $a);
            if (! Room::whereKey($t->to_room_id)->where('is_active', true)->exists() || ($t->to_person_id && ! Person::whereKey($t->to_person_id)->where('is_active', true)->exists())) {
                $this->invalid('Tujuan telah dinonaktifkan. Buat pengajuan baru.');
            }
            $a->update(['room_id' => $t->to_room_id, 'person_id' => $t->to_person_id]);
            $t->status = 'approved';
            $t->decided_by = $actor->id;
            $t->decider_snapshot = $actor->name;
            $t->decided_at = now();
            $t->active_asset_id = null;
            History::event($a, $actor, 'transfer.approved', ['transfer_id' => $t->id, 'origin' => $t->origin_snapshot, 'destination' => $t->destination_snapshot,
                'requested_by' => $t->requester_snapshot ?? 'Snapshot nama belum tersedia', 'approved_by' => $actor->name, 'reason' => $t->reason, 'correction_of_id' => $t->correction_of_id], $t->effective_on->toDateString());
            History::audit($actor, 'transfer.approved', $t, ['origin' => $t->origin_snapshot, 'destination' => $t->destination_snapshot]);
        });
    }

    public function reject(Transfer $t, User $actor, string $note): Transfer
    {
        return $this->finish($t, $actor, 'rejected', $note);
    }

    public function cancel(Transfer $t, User $actor, string $note): Transfer
    {
        return $this->finish($t, $actor, 'cancelled', $note);
    }

    private function finish(Transfer $t, User $actor, string $status, string $note): Transfer
    {
        return $this->locked($t, $actor, $status === 'cancelled' ? 'cancel' : 'approve', function ($t, $a) use ($actor, $status, $note) {
            if (! in_array($t->status, $status === 'cancelled' ? ['draft', 'submitted'] : ['submitted'])) {
                $this->invalid('Transaksi ini sudah final atau tidak dapat diproses.');
            }
            $t->status = $status;
            $t->active_asset_id = null;
            $t->decided_by = $actor->id;
            $t->decider_snapshot = $actor->name;
            $t->decided_at = now();
            $t->decision_note = $note;
            if ($status === 'cancelled') {
                $t->cancelled_at = now();
            }
            History::event($a,$actor,'transfer.'.$status,['transfer_id' => $t->id, 'note' => $note],School::today());
            History::audit($actor,'transfer.'.$status,$t,['note' => $note]);
        });
    }
}
