<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'access admin panel',
            'manage settings',
            'view assets',
            'create assets',
            'update assets',
            'delete assets',
            'restore assets',
            'checkout assets',
            'checkin assets',
            'import assets',
            'export assets',
            'print labels',
            'view accessories',
            'create accessories',
            'update accessories',
            'delete accessories',
            'checkout accessories',
            'checkin accessories',
            'view accessory assignments',
            'view assignments',
            'create assignments',
            'update assignments',
            'delete assignments',
            'view users',
            'create users',
            'update users',
            'delete users',
            'import users',
            'view employees',
            'create employees',
            'update employees',
            'delete employees',
            'import employees',
            'view departments',
            'create departments',
            'update departments',
            'delete departments',
            'view locations',
            'create locations',
            'update locations',
            'delete locations',
            'view categories',
            'create categories',
            'update categories',
            'delete categories',
            'view asset models',
            'create asset models',
            'update asset models',
            'delete asset models',
            'view manufacturers',
            'create manufacturers',
            'update manufacturers',
            'delete manufacturers',
            'view status labels',
            'create status labels',
            'update status labels',
            'delete status labels',
            'view vendors',
            'create vendors',
            'update vendors',
            'delete vendors',
            'view maintenance',
            'create maintenance',
            'update maintenance',
            'delete maintenance',
            'close maintenance',
            'view attachments',
            'upload attachments',
            'download attachments',
            'delete attachments',
            'view reports',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        $itManagerPermissions = [
            'access admin panel',
            'view assets',
            'create assets',
            'update assets',
            'delete assets',
            'restore assets',
            'checkout assets',
            'checkin assets',
            'import assets',
            'export assets',
            'print labels',
            'view accessories',
            'create accessories',
            'update accessories',
            'delete accessories',
            'checkout accessories',
            'checkin accessories',
            'view accessory assignments',
            'view assignments',
            'create assignments',
            'update assignments',
            'delete assignments',
            'view users',
            'import users',
            'view employees',
            'create employees',
            'update employees',
            'delete employees',
            'import employees',
            'view departments',
            'view locations',
            'create locations',
            'update locations',
            'delete locations',
            'view categories',
            'create categories',
            'update categories',
            'delete categories',
            'view asset models',
            'create asset models',
            'update asset models',
            'delete asset models',
            'view manufacturers',
            'create manufacturers',
            'update manufacturers',
            'delete manufacturers',
            'view status labels',
            'create status labels',
            'update status labels',
            'delete status labels',
            'view vendors',
            'create vendors',
            'update vendors',
            'delete vendors',
            'view maintenance',
            'create maintenance',
            'update maintenance',
            'delete maintenance',
            'close maintenance',
            'view attachments',
            'upload attachments',
            'download attachments',
            'delete attachments',
            'view reports',
            'view audit logs',
        ];

        $itManager = Role::firstOrCreate(['name' => 'IT Manager', 'guard_name' => 'web']);
        $itManager->syncPermissions($itManagerPermissions);

        $readOnlyPermissions = [
            'access admin panel',
            'view assets',
            'view accessories',
            'view accessory assignments',
            'view assignments',
            'view users',
            'view employees',
            'view departments',
            'view locations',
            'view categories',
            'view asset models',
            'view manufacturers',
            'view status labels',
            'view vendors',
            'view maintenance',
            'view attachments',
            'download attachments',
            'view reports',
            'view audit logs',
        ];

        $readOnly = Role::firstOrCreate(['name' => 'Read-only', 'guard_name' => 'web']);
        $readOnly->syncPermissions($readOnlyPermissions);
    }
}
