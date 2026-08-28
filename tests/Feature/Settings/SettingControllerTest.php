<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_guest_is_redirected_from_settings(): void
    {
        $this->get(route('admin.settings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_settings(): void
    {
        $user = $this->createUserWithRole('customer');

        $this->actingAs($user)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_settings(): void
    {
        $user = $this->createUserWithRole('admin');

        $this->actingAs($user)
            ->getJson(route('admin.settings.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment([
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'MetaFore OTA',
                'type' => 'string',
                'is_public' => true,
            ]);
    }

    public function test_admin_cannot_update_settings(): void
    {
        $user = $this->createUserWithRole('admin');

        $this->actingAs($user)
            ->putJson(
                route('admin.settings.update', [
                    'group' => 'general',
                    'key' => 'site_name',
                ]),
                [
                    'value' => 'Changed By Admin',
                    'type' => 'string',
                    'is_public' => true,
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'site_name',
            'value' => 'MetaFore OTA',
        ]);
    }

    public function test_super_admin_can_view_one_setting(): void
    {
        $user = $this->createUserWithRole('super-admin');

        $this->actingAs($user)
            ->getJson(
                route('admin.settings.show', [
                    'group' => 'general',
                    'key' => 'site_name',
                ])
            )
            ->assertOk()
            ->assertJsonPath(
                'data.value',
                'MetaFore OTA'
            )
            ->assertJsonPath(
                'data.type',
                'string'
            );
    }

    public function test_super_admin_can_create_or_update_setting(): void
    {
        $user = $this->createUserWithRole('super-admin');

        $this->actingAs($user)
            ->putJson(
                route('admin.settings.update', [
                    'group' => 'general',
                    'key' => 'maintenance_mode',
                ]),
                [
                    'value' => 'yes',
                    'type' => 'boolean',
                    'is_public' => false,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Setting saved successfully.'
            )
            ->assertJsonPath(
                'data.value',
                true
            )
            ->assertJsonPath(
                'data.type',
                'boolean'
            );

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'maintenance_mode',
            'value' => '1',
            'type' => 'boolean',
        ]);
    }

    public function test_unsupported_setting_type_is_rejected(): void
    {
        $user = $this->createUserWithRole('super-admin');

        $this->actingAs($user)
            ->putJson(
                route('admin.settings.update', [
                    'group' => 'general',
                    'key' => 'invalid_type',
                ]),
                [
                    'value' => 'value',
                    'type' => 'object',
                    'is_public' => false,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'type',
            ]);
    }

    public function test_invalid_boolean_value_is_rejected(): void
    {
        $user = $this->createUserWithRole('super-admin');

        $this->actingAs($user)
            ->putJson(
                route('admin.settings.update', [
                    'group' => 'general',
                    'key' => 'bad_boolean',
                ]),
                [
                    'value' => 'maybe',
                    'type' => 'boolean',
                    'is_public' => false,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'value',
            ]);
    }

    public function test_super_admin_can_delete_setting(): void
    {
        $user = $this->createUserWithRole('super-admin');

        $this->actingAs($user)
            ->deleteJson(
                route('admin.settings.destroy', [
                    'group' => 'general',
                    'key' => 'site_name',
                ])
            )
            ->assertOk()
            ->assertJson([
                'message' => 'Setting deleted successfully.',
            ]);

        $this->assertDatabaseMissing('settings', [
            'group' => 'general',
            'key' => 'site_name',
        ]);
    }
}