<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Classification;
use App\Models\FundSource;
use App\Models\Location;
use App\Models\Ownership;
use App\Models\Person;
use App\Models\Room;
use App\Models\User;
use App\Services\AssetService;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Data contoh hanya boleh digunakan di local/testing.');
        }
        $this->call(FoundationSeeder::class);
        $actor = User::whereHas('role', fn ($q) => $q->where('slug', 'administrator'))->where('is_active', true)->firstOrFail();
        $location = Location::firstOrCreate(['code' => 'DEMO-LOC'], ['name' => 'CONTOH · Gedung utama']);
        $person = Person::firstOrCreate(['code' => 'DEMO-PERSON'], ['name' => 'CONTOH · Pegawai sekolah']);
        $classification = Classification::firstOrCreate(['code' => 'DEMO-NONRESMI'], ['name' => 'CONTOH · Bukan klasifikasi resmi']);
        $owner = Ownership::firstOrCreate(['code' => 'DEMO-NONBMN'], ['name' => 'CONTOH · Non-BMN', 'is_bmn' => false]);
        FundSource::firstOrCreate(['code' => 'DEMO-FUND'], ['name' => 'CONTOH · Sumber dana sekolah']);
        $rooms = [];
        foreach (['Ruang guru', 'Laboratorium komputer', 'Perpustakaan', 'Kelas 7A'] as $i => $name) {
            $rooms[] = Room::firstOrCreate(['code' => 'DEMO-ROOM-'.$i], ['name' => 'CONTOH · '.$name, 'location_id' => $location->id]);
        }
        foreach (['Laptop pembelajaran', 'Proyektor ruang kelas', 'Meja kerja guru', 'Lemari arsip', 'Kursi belajar', 'Printer administrasi', 'Rak buku', 'Komputer laboratorium'] as $i => $name) {
            $number = 'CONTOH-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            if (Asset::where('inventory_number', $number)->exists()) {
                continue;
            }
            app(AssetService::class)->create(['name' => 'CONTOH · '.$name, 'inventory_number' => $number, 'classification_id' => $classification->id,
                'ownership_id' => $owner->id, 'condition' => ['good', 'good', 'light_damage', 'good', 'unknown', 'heavy_damage', 'good', 'good'][$i],
                'room_id' => $rooms[$i % 4]->id, 'person_id' => $person->id, 'acquisition_method' => 'unknown', 'notes' => 'DATA CONTOH untuk pengenalan aplikasi. Bukan aset atau riwayat BMN nyata.'], $actor);
        }
    }
}
