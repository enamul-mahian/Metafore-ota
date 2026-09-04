<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
        ]);

        $user = User::query()->firstOrCreate(
            [
                'email' => 'test@example.com',
            ],
            [
                'name' => 'Test User',
                'password' => Hash::make((string) env('LOCAL_TEST_USER_PASSWORD', bin2hex(random_bytes(16)))),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $user->syncRoles([
            'customer',
        ]);
    }
}
