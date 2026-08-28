<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingService $settings;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
        ]);

        Cache::flush();

        $this->settings = app(SettingService::class);
    }

    public function test_missing_setting_returns_default_value(): void
    {
        $value = $this->settings->get(
            'general',
            'missing_key',
            'fallback'
        );

        $this->assertSame('fallback', $value);
    }

    public function test_string_setting_can_be_created_and_read(): void
    {
        $setting = $this->settings->set(
            'general',
            'site_name',
            'MetaFore OTA',
            'string',
            true
        );

        $this->assertSame(
            'MetaFore OTA',
            $this->settings->get(
                'general',
                'site_name'
            )
        );

        $this->assertSame('general', $setting->group);
        $this->assertSame('site_name', $setting->key);
        $this->assertTrue($setting->is_public);
    }

    public function test_integer_float_boolean_and_json_values_are_cast_correctly(): void
    {
        $this->settings->set(
            'booking',
            'hold_minutes',
            30,
            'integer'
        );

        $this->settings->set(
            'pricing',
            'service_fee',
            12.5,
            'float'
        );

        $this->settings->set(
            'general',
            'maintenance_mode',
            true,
            'boolean'
        );

        $this->settings->set(
            'general',
            'features',
            [
                'flights' => true,
                'hotels' => false,
            ],
            'json'
        );

        $this->assertSame(
            30,
            $this->settings->get(
                'booking',
                'hold_minutes'
            )
        );

        $this->assertSame(
            12.5,
            $this->settings->get(
                'pricing',
                'service_fee'
            )
        );

        $this->assertTrue(
            $this->settings->get(
                'general',
                'maintenance_mode'
            )
        );

        $this->assertSame(
            [
                'flights' => true,
                'hotels' => false,
            ],
            $this->settings->get(
                'general',
                'features'
            )
        );
    }

    public function test_boolean_string_values_are_normalized(): void
    {
        $this->settings->set(
            'test',
            'enabled',
            'yes',
            'boolean'
        );

        $this->assertTrue(
            $this->settings->get(
                'test',
                'enabled'
            )
        );

        $this->settings->set(
            'test',
            'enabled',
            'off',
            'boolean'
        );

        $this->assertFalse(
            $this->settings->get(
                'test',
                'enabled'
            )
        );
    }

    public function test_setting_update_does_not_create_duplicate_rows(): void
    {
        $this->settings->set(
            'general',
            'site_name',
            'MetaFore OTA'
        );

        $this->settings->set(
            'general',
            'site_name',
            'MetaFore Travel'
        );

        $this->assertSame(
            1,
            Setting::query()
                ->where('group', 'general')
                ->where('key', 'site_name')
                ->count()
        );

        $this->assertSame(
            'MetaFore Travel',
            $this->settings->get(
                'general',
                'site_name'
            )
        );
    }

    public function test_set_invalidates_cached_setting_value(): void
    {
        $this->settings->set(
            'general',
            'site_name',
            'MetaFore OTA'
        );

        $this->assertSame(
            'MetaFore OTA',
            $this->settings->get(
                'general',
                'site_name'
            )
        );

        Setting::query()
            ->where('group', 'general')
            ->where('key', 'site_name')
            ->update([
                'value' => 'Direct Database Change',
            ]);

        /*
         * The old value is still cached here.
         */
        $this->assertSame(
            'MetaFore OTA',
            $this->settings->get(
                'general',
                'site_name'
            )
        );

        /*
         * SettingService::set() must clear that cache.
         */
        $this->settings->set(
            'general',
            'site_name',
            'Updated Through Service'
        );

        $this->assertSame(
            'Updated Through Service',
            $this->settings->get(
                'general',
                'site_name'
            )
        );
    }

    public function test_delete_removes_setting_and_invalidates_cache(): void
    {
        $this->settings->set(
            'general',
            'temporary',
            'value'
        );

        $this->assertSame(
            'value',
            $this->settings->get(
                'general',
                'temporary'
            )
        );

        $deleted = $this->settings->delete(
            'general',
            'temporary'
        );

        $this->assertTrue($deleted);

        $this->assertDatabaseMissing('settings', [
            'group' => 'general',
            'key' => 'temporary',
        ]);

        $this->assertSame(
            'fallback',
            $this->settings->get(
                'general',
                'temporary',
                'fallback'
            )
        );
    }

    public function test_unsupported_setting_type_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->settings->set(
            'general',
            'bad_type',
            'value',
            'object'
        );
    }

    public function test_invalid_boolean_value_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->settings->set(
            'general',
            'invalid_boolean',
            'maybe',
            'boolean'
        );
    }
}