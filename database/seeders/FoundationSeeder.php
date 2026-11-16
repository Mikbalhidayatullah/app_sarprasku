<?php

namespace Database\Seeders;

use App\Models\Ownership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use Illuminate\Database\Seeder;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        School::firstOrCreate(['id' => 1], ['name' => 'Sekolah — lengkapi identitas', 'timezone' => 'Asia/Jakarta']);
        Ownership::firstOrCreate(['code' => 'UNVERIFIED'], ['name' => 'Belum diverifikasi', 'is_bmn' => false]);
        $read = ['dashboard.view', 'assets.view', 'reports.view', 'reports.export', 'labels.print', 'attachments.view'];
        $operate = ['assets.create', 'assets.update', 'procurements.manage', 'transfers.create', 'maintenance.manage', 'attachments.upload'];
        $extra = ['users.manage', 'masters.manage', 'permissions.manage', 'transfers.approve', 'assets.archive', 'assets.dispose', 'audit.view'];
        foreach (array_merge($read, $operate, $extra) as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }
        foreach ([
            'administrator' => ['Administrator', array_merge($read, $operate, $extra)],
            'operator' => ['Operator sarpras', array_merge($read, $operate)],
            'approver' => ['Penyetuju', array_merge($read, ['transfers.approve'])],
            'viewer' => ['Viewer / pimpinan', $read],
        ] as $slug => [$name,$permissions]) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $name]);
            if (! $role->permissions()->exists()) {
                $role->permissions()->sync(Permission::whereIn('name', $permissions)->pluck('id'));
            }
        }
    }
}
