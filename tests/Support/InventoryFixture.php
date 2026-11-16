<?php

namespace Tests\Support;

use App\Models\Asset;
use App\Models\FundSource;
use App\Models\Location;
use App\Models\Ownership;
use App\Models\Person;
use App\Models\Role;
use App\Models\Room;
use App\Models\School;
use App\Models\User;
use Database\Seeders\FoundationSeeder;

trait InventoryFixture
{
    protected User $operator;

    protected User $approver;

    protected Room $origin;

    protected Room $destination;

    protected Person $person;

    protected function inventorySetUp(): void
    {
        $this->seed(FoundationSeeder::class);
        $this->withoutVite();
        $this->operator = $this->makeUser('operator');
        $this->approver = $this->makeUser('approver');
        $location = Location::create(['name' => 'Gedung A']);
        $this->origin = Room::create(['name' => 'Ruang 1', 'location_id' => $location->id]);
        $this->destination = Room::create(['name' => 'Ruang 2', 'location_id' => $location->id]);
        $this->person = Person::create(['name' => 'Pegawai tanpa akun']);
    }

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', $role)->value('id'), 'is_active' => true]);
    }

    protected function asset(array $overrides = []): Asset
    {
        return Asset::create(array_merge([
            'school_id' => School::first()->id, 'inventory_number' => 'INV-'.fake()->unique()->numerify('######'),
            'name' => 'Laptop', 'ownership_id' => Ownership::first()->id, 'condition' => 'good', 'lifecycle_status' => 'active',
            'room_id' => $this->origin->id, 'person_id' => $this->person->id, 'created_by' => $this->operator->id,
        ], $overrides));
    }

    protected function procurementData(): array
    {
        $source = FundSource::create(['name' => 'Referensi dana sekolah']);

        return [
            'reference' => 'P-001', 'method' => 'purchase', 'acquired_on' => now()->toDateString(),
            'items' => [['name' => 'Kursi', 'quantity' => 3, 'unit_price' => '1000.10', 'inventory_prefix' => 'KRS-2026', 'ownership_id' => Ownership::first()->id]],
            'allocations' => [['fund_source_id' => $source->id, 'budget_year' => 2026, 'document_reference' => 'DOC-1', 'amount' => '3000.30']],
            'room_id' => $this->origin->id, 'person_id' => $this->person->id,
        ];
    }

    protected function transferData(Asset $asset): array
    {
        return ['asset_id' => $asset->id, 'to_room_id' => $this->destination->id, 'to_person_id' => $this->person->id,
            'effective_on' => School::today(), 'reason' => 'Kebutuhan pembelajaran'];
    }
}
