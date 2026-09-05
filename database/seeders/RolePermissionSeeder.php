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

            'agents.view',
            'agents.manage',

            'affiliates.view',
            'affiliates.manage',

            'students.view',
            'students.manage',

            'flights.search',
            'flights.book',

            'hotels.search',
            'hotels.book',

            'tours.search',
            'tours.book',

            'visa.apply',
            'visa.view',
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

            'agents.view',
            'agents.manage',

            'affiliates.view',
            'affiliates.manage',

            'students.view',
            'students.manage',

            'flights.search',
            'flights.book',

            'hotels.search',
            'hotels.book',

            'tours.search',
            'tours.book',

            'visa.apply',
            'visa.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Customer Permissions
        |--------------------------------------------------------------------------
        */

        $customer->syncPermissions([
            'dashboard.view',
            'flights.search',
            'flights.book',
            'hotels.search',
            'hotels.book',
            'tours.search',
            'tours.book',
            'visa.apply',
            'visa.view',
        ]);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
