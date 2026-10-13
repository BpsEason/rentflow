<?php

namespace Tests\Feature;

use App\Domain\Rental\Models\Customer;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Services\CustomerMemberPasswordService;
use App\Services\CustomerUserLinkService;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\TenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_seeder_links_members_and_is_repeatable(): void
    {
        $this->seed(TenantSeeder::class);
        $this->seed(CustomerSeeder::class);

        $initialCustomers = Customer::count();
        $initialUsers = User::count();

        $this->assertGreaterThanOrEqual(6, $initialCustomers);
        $this->assertSame($initialCustomers, Customer::whereNotNull('user_id')->count());
        $this->assertSame($initialCustomers, Customer::with('user')->whereHas('user')->count());
        $this->assertTrue(Hash::check('password', Customer::first()->user->password));

        $this->seed(CustomerSeeder::class);

        $this->assertSame($initialCustomers, Customer::count());
        $this->assertSame($initialUsers, User::count());
    }

    public function test_customer_policy_requires_permission_and_tenant_membership(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $manager = User::factory()->create();
        $manager->tenants()->attach($tenant->id, ['role' => 'admin']);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $permission = Permission::firstOrCreate(['name' => 'update_customer', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'Customer Manager', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $manager->assignRole($role);
        $policy = new CustomerPolicy();

        $this->assertTrue($policy->update($manager, $customer));

        $otherCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id]);
        $this->assertFalse($policy->update($manager, $otherCustomer));

        $unprivileged = User::factory()->create();
        $unprivileged->tenants()->attach($tenant->id, ['role' => 'staff']);
        $this->assertFalse($policy->update($unprivileged, $customer));
    }

    public function test_unlinked_customer_relationship_is_safe(): void
    {
        $customer = Customer::factory()->create(['user_id' => null]);

        $this->assertNull($customer->user);
    }

    public function test_shared_demo_accounts_are_not_seeded_outside_local_or_test_environments(): void
    {
        $this->app['env'] = 'staging';

        $this->seed(CustomerSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Customer::count());
    }

    public function test_authorized_manager_must_reauthenticate_and_member_password_is_hashed(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create(['password' => 'manager-password']);
        $member = User::factory()->create();
        $manager->tenants()->attach($tenant->id, ['role' => 'admin']);
        $member->tenants()->attach($tenant->id, ['role' => 'member']);
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
        ]);
        $permission = Permission::firstOrCreate(['name' => 'update_customer', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'Password Manager', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $manager->assignRole($role);
        $service = app(CustomerMemberPasswordService::class);

        try {
            $service->set($manager, $customer, 'wrong-current-password', 'new-member-password');
            $this->fail('A manager with an invalid current password must be rejected.');
        } catch (ValidationException) {
            $this->assertTrue(Hash::check('password', $member->fresh()->password));
        }

        $service->set($manager, $customer, 'manager-password', 'new-member-password');

        $storedPassword = $member->fresh()->password;
        $this->assertTrue(Hash::check('new-member-password', $storedPassword));
        $this->assertNotSame('new-member-password', $storedPassword);
    }

    public function test_customer_link_validation_blocks_cross_tenant_and_duplicate_members(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $member = User::factory()->create();
        $member->tenants()->attach($otherTenant->id, ['role' => 'member']);
        $service = app(CustomerUserLinkService::class);

        try {
            $service->assertCanLink($member->id, $tenant->id);
            $this->fail('A member from another tenant must not be linked.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $member->tenants()->attach($tenant->id, ['role' => 'member']);
        Customer::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $member->id]);

        try {
            $service->assertCanLink($member->id, $tenant->id);
            $this->fail('A member must not be linked to two customers in one tenant.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_customer_resource_query_is_scoped_to_the_active_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $manager = User::factory()->create();
        $manager->tenants()->attach($tenant->id, ['role' => 'admin']);
        $visibleCustomer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        Customer::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->actingAs($manager);
        Filament::setCurrentPanel('admin');
        Filament::setTenant($tenant);

        $this->assertSame(
            [$visibleCustomer->id],
            CustomerResource::getEloquentQuery()->pluck('id')->all(),
        );
    }
}
