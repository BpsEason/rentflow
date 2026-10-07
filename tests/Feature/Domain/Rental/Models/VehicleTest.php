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
}
