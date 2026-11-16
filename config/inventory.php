<?php

use App\Models\Classification;
use App\Models\FundSource;
use App\Models\Location;
use App\Models\Ownership;
use App\Models\Person;
use App\Models\Room;

return [
    'permissions' => ['dashboard.view', 'assets.view', 'assets.create', 'assets.update', 'assets.archive', 'assets.dispose',
        'procurements.manage', 'transfers.create', 'transfers.approve', 'maintenance.manage', 'reports.view', 'reports.export',
        'labels.print', 'attachments.view', 'attachments.upload', 'users.manage', 'masters.manage', 'permissions.manage', 'audit.view'],
    'conditions' => ['unknown' => 'Belum diperiksa', 'good' => 'Baik', 'light_damage' => 'Rusak ringan', 'heavy_damage' => 'Rusak berat'],
    'statuses' => ['active' => 'Aktif', 'maintenance' => 'Dalam pemeliharaan', 'archived' => 'Diarsipkan', 'disposed' => 'Dilepas / dihapus (internal)'],
    'methods' => ['unknown' => 'Belum diketahui', 'purchase' => 'Pembelian', 'grant' => 'Hibah', 'transfer' => 'Transfer'],
    'transfer_statuses' => ['draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'],
    'masters' => [
        'locations' => [Location::class, 'Lokasi'], 'rooms' => [Room::class, 'Ruangan'],
        'people' => [Person::class, 'Penanggung jawab'], 'classifications' => [Classification::class, 'Klasifikasi'],
        'fund-sources' => [FundSource::class, 'Sumber dana'], 'ownerships' => [Ownership::class, 'Status kepemilikan'],
    ],
];
