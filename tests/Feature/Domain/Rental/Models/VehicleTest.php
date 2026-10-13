<?php

namespace Tests\Feature\Domain\Rental\Models;

use App\Domain\Rental\Models\Vehicle;
use App\Domain\Rental\Enums\VehicleStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_vehicle_with_valid_data(): void
    {
        $tenant = Tenant::factory()->create();

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Car',
            'plate_number' => 'ABC-1234',
            'status' => VehicleStatus::AVAILABLE,
            'seats' => 5,
            'description' => 'A test vehicle',
        ]);

        $this->assertInstanceOf(Vehicle::class, $vehicle);
        $this->assertEquals(VehicleStatus::AVAILABLE, $vehicle->status);
        $this->assertEquals('ABC-1234', $vehicle->plate_number);
    }

    /** @test */
    public function it_enforces_unique_plate_number_within_same_tenant(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $tenant = Tenant::factory()->create();

        Vehicle::create([
            'tenant_id' => $tenant->id,
            'name' => 'First Car',
            'plate_number' => 'ABC-1234',
            'status' => VehicleStatus::AVAILABLE,
            'seats' => 5,
        ]);

        // This should fail due to unique constraint
        Vehicle::create([
            'tenant_id' => $tenant->id,
            'name' => 'Second Car',
            'plate_number' => 'ABC-1234',
            'status' => VehicleStatus::MAINTENANCE,
            'seats' => 5,
        ]);
    }

    /** @test */
    public function it_allows_same_plate_number_across_different_tenants(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $vehicleA = Vehicle::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Car',
            'plate_number' => 'ABC-1234',
            'status' => VehicleStatus::AVAILABLE,
            'seats' => 5,
        ]);

        $vehicleB = Vehicle::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Car',
            'plate_number' => 'ABC-1234',
            'status' => VehicleStatus::AVAILABLE,
            'seats' => 5,
        ]);

        $this->assertCount(2, Vehicle::where('plate_number', 'ABC-1234')->get());
    }

    /** @test */
    public function it_cannot_create_vehicle_with_nonexistent_tenant(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Vehicle::create([
            'tenant_id' => 999, // Non-existent tenant ID
            'name' => 'Invalid Car',
            'plate_number' => 'XYZ-9999',
            'status' => VehicleStatus::AVAILABLE,
            'seats' => 5,
        ]);
    }

    /** @test */
    public function it_casts_status_to_enum(): void
    {
        $tenant = Tenant::factory()->create();

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'name' => 'Status Test Car',
            'plate_number' => 'XYZ-7890',
            'status' => VehicleStatus::MAINTENANCE,
            'seats' => 7,
        ]);

        $this->assertInstanceOf(VehicleStatus::class, $vehicle->status);
        $this->assertEquals(VehicleStatus::MAINTENANCE, $vehicle->status);
    }

    /** @test */
    public function it_belongs_to_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'name' => 'Relationship Test Car',
            'plate_number' => 'REL-0001',
            'status' => VehicleStatus::INACTIVE,
            'seats' => 5,
        ]);

        $this->assertInstanceOf(Tenant::class, $vehicle->tenant);
        $this->assertEquals($tenant->id, $vehicle->tenant->id);
    }

    /** @test */
    public function it_creates_vehicle_with_all_operational_fields(): void
    {
        $tenant = Tenant::factory()->create();
        $now = now();

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'name' => 'Full Featured Car',
            'plate_number' => 'FULL-1234',
            'status' => VehicleStatus::MAINTENANCE,
            'seats' => 5,
            'manufacture_year' => 2022,
            'mileage' => 50000,
            'fuel_type' => Vehicle::FUEL_HYBRID,
            'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
            'maintenance_start_at' => $now,
            'maintenance_expected_end_at' => $now->copy()->addDays(3),
            'maintenance_notes' => '定期保養與煞車檢查',
        ]);

        $this->assertEquals(2022, $vehicle->manufacture_year);
        $this->assertEquals(50000, $vehicle->mileage);
        $this->assertEquals(Vehicle::FUEL_HYBRID, $vehicle->fuel_type);
        $this->assertEquals(Vehicle::TRANSMISSION_AUTOMATIC, $vehicle->transmission);
        $this->assertEquals($now->toDateTimeString(), $vehicle->maintenance_start_at->toDateTimeString());
        $this->assertEquals($now->copy()->addDays(3)->toDateTimeString(), $vehicle->maintenance_expected_end_at->toDateTimeString());
        $this->assertEquals('定期保養與煞車檢查', $vehicle->maintenance_notes);
    }

    /** @test */
    public function it_allows_null_values_for_operational_fields(): void
    {
        $tenant = Tenant::factory()->create();

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'name' => 'Minimal Car',
            'plate_number' => 'MINI-5678',
            'status' => VehicleStatus::AVAILABLE,
            'seats' => 5,
            // All operational fields are null
        ]);

        $this->assertNull($vehicle->manufacture_year);
        $this->assertNull($vehicle->mileage);
        $this->assertNull($vehicle->fuel_type);
        $this->assertNull($vehicle->transmission);
        $this->assertNull($vehicle->maintenance_start_at);
        $this->assertNull($vehicle->maintenance_expected_end_at);
        $this->assertNull($vehicle->maintenance_notes);
    }

    /** @test */
    public function it_casts_numeric_fields_correctly(): void
    {
        $tenant = Tenant::factory()->create();

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'name' => 'Casting Test Car',
            'plate_number' => 'CAST-9012',
            'status' => VehicleStatus::AVAILABLE,
            'seats' => 5,
            'manufacture_year' => '2020', // String input
            'mileage' => '75000', // String input
        ]);

        $this->assertIsInt($vehicle->manufacture_year);
        $this->assertEquals(2020, $vehicle->manufacture_year);
        $this->assertIsInt($vehicle->mileage);
        $this->assertEquals(75000, $vehicle->mileage);
    }
}
