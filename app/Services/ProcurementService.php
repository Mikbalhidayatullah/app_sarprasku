<?php

namespace App\Services;

use App\Models\FundSource;
use App\Models\Procurement;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public function create(array $data, User $actor): Procurement
    {
        Gate::forUser($actor)->authorize('procurements.manage');
        $total = '0.00';
        $funds = '0.00';
        $units = 0;
        foreach ($data['items'] as $item) {
            $total = bcadd($total, bcmul($item['unit_price'], (string) $item['quantity'], 2), 2);
            $units += $item['quantity'];
        }
        foreach ($data['allocations'] as $allocation) {
            $funds = bcadd($funds, $allocation['amount'], 2);
        }
        if ($units > 1000 || bccomp($total, '9999999999999999.99', 2) > 0) {
            throw ValidationException::withMessages(['items' => 'Maksimal 1.000 unit per transaksi dan nominal harus dalam batas penyimpanan.']);
        }
        if (bccomp($funds, $total, 2) !== 0) {
            throw ValidationException::withMessages(['allocations' => 'Jumlah alokasi sumber dana harus tepat sama dengan total rincian pengadaan ('.$total.').']);
        }
        try {
            return DB::transaction(function () use ($data, $actor, $total, $units) {
                $p = Procurement::create(collect($data)->only(['reference', 'supplier', 'acquired_on', 'method', 'notes'])->all() + ['total' => $total, 'created_by' => $actor->id, 'creator_snapshot' => $actor->name]);
                foreach ($data['allocations'] as $allocation) {
                    $p->allocations()->create($allocation + ['source_snapshot' => FundSource::findOrFail($allocation['fund_source_id'])->name]);
                }
                foreach ($data['items'] as $item) {
                    $line = $p->items()->create($item);
                    for ($i = 1; $i <= $item['quantity']; $i++) {
                        app(AssetService::class)->create(['name' => $item['name'], 'inventory_number' => $item['inventory_prefix'].'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                            'classification_id' => $item['classification_id'] ?? null, 'ownership_id' => $item['ownership_id'], 'procurement_item_id' => $line->id,
                            'acquisition_value' => $item['unit_price'], 'acquired_on' => $data['acquired_on'], 'acquisition_year' => substr($data['acquired_on'], 0, 4),
                            'acquisition_method' => $data['method'], 'condition' => 'unknown', 'verified' => false, 'room_id' => $data['room_id'] ?? null, 'person_id' => $data['person_id'] ?? null], $actor);
                    }
                }
                History::audit($actor, 'procurement.created', $p, ['reference' => $p->reference, 'total' => $total, 'units' => $units]);

                return $p;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['reference' => 'Referensi atau nomor inventaris sudah digunakan. Ubah prefix agar setiap unit unik.']);
        }
    }
}
