<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\Procurement;
use App\Models\Transfer;
use App\Support\Display;

class ReportData
{
    public static function query(array $f)
    {
        $assetIds = AssetQuery::build($f)->select('assets.id');
        $q = match ($f['type'] ?? 'inventory') {
            'transfers' => Transfer::with('asset', 'requester', 'decider')->whereIn('asset_id', $assetIds),
            'maintenance' => Maintenance::with('asset', 'creator')->whereIn('asset_id', $assetIds),
            'procurements' => Procurement::with('allocations.fundSource')->whereHas('items.assets', fn ($q) => $q->whereIn('assets.id', $assetIds)),
            default => AssetQuery::build($f)->with('room.location', 'person', 'classification', 'ownership'),
        };
        $date = match ($f['type'] ?? 'inventory') {
            'transfers' => 'effective_on','maintenance' => 'performed_on', default => 'acquired_on'
        };
        if ($f['from'] ?? null) {
            $q->whereDate($date, '>=', $f['from']);
        }
        if ($f['to'] ?? null) {
            $q->whereDate($date, '<=', $f['to']);
        }

        return $q->orderBy($q->getModel()->getTable().'.id');
    }

    public static function headers(string $type): array
    {
        return match ($type) {
            'transfers' => ['Inventaris', 'Barang', 'Asal', 'Pemegang asal', 'Tujuan', 'Pemegang tujuan', 'Status', 'Efektif', 'Pengaju', 'Pemroses', 'Alasan', 'Koreksi transaksi'],
            'maintenance' => ['Inventaris', 'Barang', 'Tanggal', 'Keluhan', 'Tindakan', 'Pelaksana', 'Biaya (Rp)', 'Hasil', 'Kondisi hasil', 'Pencatat'],
            'procurements' => ['Referensi', 'Tanggal', 'Pemasok', 'Metode', 'Total pengadaan (Rp)', 'Sumber / tahun / dokumen / alokasi'],
            default => ['Inventaris', 'Nama', 'Klasifikasi', 'NUP / register', 'Kepemilikan', 'Lokasi', 'Ruangan', 'Penanggung jawab', 'Tahun', 'Nilai (Rp)', 'Kondisi', 'Status', 'Verifikasi'],
        };
    }

    public static function row($m, string $type): array
    {
        return match ($type) {
            'transfers' => [$m->asset->inventory_number, $m->asset->name, $m->origin_snapshot['room'] ?? 'Belum diketahui', $m->origin_snapshot['person'] ?? 'Belum diketahui',
                $m->destination_snapshot['room'] ?? 'Belum diketahui', $m->destination_snapshot['person'] ?? 'Belum diketahui', config('inventory.transfer_statuses.'.$m->status),
                $m->effective_on->toDateString(), $m->requester_snapshot ?? 'Snapshot nama belum tersedia', $m->decider_snapshot, $m->reason, $m->correction_of_id],
            'maintenance' => [$m->asset->inventory_number, $m->asset->name, $m->performed_on->toDateString(), $m->complaint, $m->action, $m->vendor, $m->cost, $m->result, Display::condition($m->condition_after), $m->creator_snapshot ?? 'Snapshot nama belum tersedia'],
            'procurements' => [$m->reference, $m->acquired_on?->toDateString(), $m->supplier, config('inventory.methods.'.$m->method), $m->total,
                $m->allocations->map(fn ($a) => $a->source_snapshot.' / '.$a->budget_year.' / '.$a->document_reference.' / Rp '.$a->amount)->join('; ')],
            default => [$m->inventory_number, $m->name, $m->classification?->code, $m->official_register, $m->ownership?->name, $m->room?->location?->name, $m->room?->name,
                $m->person?->name, $m->acquisition_year, $m->acquisition_value, Display::condition($m->condition), Display::status($m->lifecycle_status), $m->verified ? 'Diverifikasi' : 'Belum diverifikasi'],
        };
    }
}
