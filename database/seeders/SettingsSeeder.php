<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Seed the application's default settings.
     */
    public function run(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            [
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'Eagle Global Hub LTD',
                'type' => 'string',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'localization',
                'key' => 'locale',
                'value' => config('app.locale', 'en'),
                'type' => 'string',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'localization',
                'key' => 'timezone',
                'value' => config('app.timezone', 'UTC'),
                'type' => 'string',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
