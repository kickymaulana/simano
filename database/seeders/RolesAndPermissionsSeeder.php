<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'submit evaluations',
            'view evaluation status',
            'manage evaluation periods',
            'manage evaluation templates',
            'manage users',
            'view evaluation reports',
            'export evaluation reports',
            'view audit logs',
        ];

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('employee', 'web')->syncPermissions([
            'submit evaluations',
            'view evaluation status',
        ]);

        Role::findOrCreate('admin', 'web')->syncPermissions($permissions);
        Role::findOrCreate('hr', 'web')->syncPermissions([
            'manage evaluation periods',
            'manage evaluation templates',
            'view evaluation reports',
            'export evaluation reports',
        ]);
    }
}
