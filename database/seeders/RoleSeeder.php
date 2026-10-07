<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin - 使用 Shield 官方的 Super Admin 機制，無需手動分配所有權限
        // Shield 會自動透過配置的 super_admin 名稱來辨識，並給予完整存取權限
        Role::updateOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );

        // Tenant Admin - 可以管理其 Tenant 範圍內的所有資源
        $tenantAdmin = Role::updateOrCreate(
            ['name' => 'Tenant Admin', 'guard_name' => 'web'],
            ['name' => 'Tenant Admin', 'guard_name' => 'web']
        );

        // Tenant Staff - 只擁有基本檢視和建立權限
        $tenantStaff = Role::updateOrCreate(
            ['name' => 'Tenant Staff', 'guard_name' => 'web'],
            ['name' => 'Tenant Staff', 'guard_name' => 'web']
        );

        // 先建立所有需要的權限，確保它們存在
        $allRequiredPermissions = [
            // Tenant 相關權限
            'ViewAny:Tenant',
            'View:Tenant',
            'Update:Tenant',
            // User 相關權限
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            // Vehicle 相關權限
            'ViewAny:Vehicle',
            'View:Vehicle',
            'Create:Vehicle',
            'Update:Vehicle',
            'Delete:Vehicle',
            // VehiclePricing 相關權限
            'ViewAny:VehiclePricing',
            'View:VehiclePricing',
            'Create:VehiclePricing',
            'Update:VehiclePricing',
            'Delete:VehiclePricing',
            // Holiday 相關權限 - 只有 Super Admin 可以存取
            'ViewAny:Holiday',
            'View:Holiday',
            'Create:Holiday',
            'Update:Holiday',
            'Delete:Holiday',
            'DeleteAny:Holiday',
            'Restore:Holiday',
            'ForceDelete:Holiday',
            'ForceDeleteAny:Holiday',
            'RestoreAny:Holiday',
        ];

        foreach ($allRequiredPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // 取得所有存在的權限，確認 guard_name 都是 web
        $permissions = Permission::where('guard_name', 'web')->get()->keyBy('name');

        // Tenant Admin 權限：可以管理 Tenant 內的使用者
        $tenantAdminPermissions = [
            // User 相關權限 - 可以管理租戶下的使用者
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            // Vehicle 相關權限 - 可以管理租戶下的車輛
            'ViewAny:Vehicle',
            'View:Vehicle',
            'Create:Vehicle',
            'Update:Vehicle',
            'Delete:Vehicle',
            // VehiclePricing 相關權限 - 可以管理租戶下的車輛定價
            'ViewAny:VehiclePricing',
            'View:VehiclePricing',
            'Create:VehiclePricing',
            'Update:VehiclePricing',
            'Delete:VehiclePricing',
        ];

        // Tenant Staff 權限：只有基本檢視權限
        $tenantStaffPermissions = [
            // User 相關權限 - 只能檢視
            'ViewAny:User',
            'View:User',
            // Vehicle 相關權限 - 只能檢視
            'ViewAny:Vehicle',
            'View:Vehicle',
            // VehiclePricing 相關權限 - 只能檢視
            'ViewAny:VehiclePricing',
            'View:VehiclePricing',
        ];

        // 驗證 Tenant Admin 所有宣告的權限都存在
        $missingTenantAdminPermissions = collect($tenantAdminPermissions)
            ->filter(fn($perm) => !$permissions->has($perm))
            ->values();

        if ($missingTenantAdminPermissions->isNotEmpty()) {
            throw new \RuntimeException('Tenant Admin 缺少下列權限: ' . $missingTenantAdminPermissions->implode(', '));
        }

        // 驗證 Tenant Staff 所有宣告的權限都存在
        $missingTenantStaffPermissions = collect($tenantStaffPermissions)
            ->filter(fn($perm) => !$permissions->has($perm))
            ->values();

        if ($missingTenantStaffPermissions->isNotEmpty()) {
            throw new \RuntimeException('Tenant Staff 缺少下列權限: ' . $missingTenantStaffPermissions->implode(', '));
        }

        // 同步權限
        $tenantAdmin->syncPermissions($tenantAdminPermissions);
        $tenantStaff->syncPermissions($tenantStaffPermissions);
    }
}
