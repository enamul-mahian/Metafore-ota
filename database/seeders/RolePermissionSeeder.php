<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's initial roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $guard = 'web';

        /*
        |--------------------------------------------------------------------------
        | Core Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            'dashboard.view',

            'users.view',
            'users.manage',

            'roles.view',
            'roles.manage',

            'settings.view',
            'settings.manage',

            'master-data.view',
            'master-data.manage',

            'flights.search',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => $guard,
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guard,
        ]);

        $customer = Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => $guard,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Super Admin Permissions
        |--------------------------------------------------------------------------
        */

        $superAdmin->syncPermissions($permissions);

        /*
        |--------------------------------------------------------------------------
        | Admin Permissions
        |--------------------------------------------------------------------------
        */

        $admin->syncPermissions([
            'dashboard.view',

            'users.view',
            'users.manage',

            'roles.view',

            'settings.view',

            'master-data.view',
            'master-data.manage',

            'flights.search',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Customer Permissions
        |--------------------------------------------------------------------------
        */

        $customer->syncPermissions([
            'dashboard.view',
            'flights.search',
        ]);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
