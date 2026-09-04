<?php

namespace Tests\Feature\FeatureControl;

use App\Models\Setting;
use App\Models\User;
use App\Services\Feature\FeatureManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

final class FeatureControlManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_feature_management(): void
    {
        $this->get(route('admin.features.index'))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_access_feature_management(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.features.index'))
            ->assertOk()
            ->assertSee('Feature Control')
            ->assertSee('Flights')
            ->assertSee('Hotels')
            ->assertSee('Payments')
            ->assertSee('Visibility is separate from provider activation.');
    }

    public function test_normal_admin_cannot_access_or_modify_feature_controls(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.features.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.features.update', 'flights'), $this->state(false))
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', [
            'group' => 'features',
            'key' => 'flights',
        ]);
    }

    public function test_customer_cannot_modify_feature_controls(): void
    {
        $customer = $this->userWithRole('customer');

        $this->actingAs($customer)
            ->patch(route('admin.features.update', 'flights'), $this->state(false))
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', [
            'group' => 'features',
            'key' => 'flights',
        ]);
    }

    public function test_super_admin_can_toggle_registered_feature(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->patch(
                route('admin.features.update', 'flights'),
                $this->state(false, 'Flight maintenance in progress.'),
            )
            ->assertRedirect(route('admin.features.index'))
            ->assertSessionHas('status', 'Flights feature is now disabled.');

        $setting = Setting::query()
            ->where('group', 'features')
            ->where('key', 'flights')
            ->sole();

        $this->assertSame('json', $setting->type);
        $this->assertFalse($setting->is_public);
        $this->assertSame(
            [
                'enabled' => false,
                'public_visible' => true,
                'authenticated_visible' => true,
                'admin_visible' => true,
                'message' => 'Flight maintenance in progress.',
            ],
            json_decode((string) $setting->value, true),
        );
    }

    public function test_unknown_feature_key_returns_404_and_is_not_persisted(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->patch(
                route('admin.features.update', 'not-registered'),
                $this->state(false),
            )
            ->assertNotFound();

        $this->assertDatabaseMissing('settings', [
            'group' => 'features',
            'key' => 'not-registered',
        ]);
    }

    public function test_unexpected_payload_keys_cannot_mutate_other_features_or_provider_config(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->patch(
                route('admin.features.update', 'hotels'),
                [
                    ...$this->state(true),
                    'feature' => 'payments',
                    'provider' => 'duffel',
                    'HOTELS_ENABLED' => true,
                    'DUFFEL_LIVE_ORDER_CREATION_ENABLED' => true,
                ],
            )
            ->assertRedirect(route('admin.features.index'));

        $this->assertDatabaseHas('settings', [
            'group' => 'features',
            'key' => 'hotels',
        ]);
        $this->assertDatabaseMissing('settings', [
            'group' => 'features',
            'key' => 'payments',
        ]);
        $this->assertFalse(config('travel_services.services.hotels.enabled'));
        $this->assertFalse(config('flight_orders.duffel.live_order_creation_enabled'));
    }

    public function test_invalid_visibility_payload_is_rejected_without_persistence(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->patchJson(
                route('admin.features.update', 'visa'),
                [
                    ...$this->state(true),
                    'public_visible' => 'sometimes',
                    'message' => str_repeat('x', 501),
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'public_visible',
                'message',
            ]);

        $this->assertDatabaseMissing('settings', [
            'group' => 'features',
            'key' => 'visa',
        ]);
    }

    public function test_form_request_authorization_blocks_direct_controller_bypass(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->withoutMiddleware(RoleMiddleware::class)
            ->patchJson(
                route('admin.features.update', 'about'),
                $this->state(false),
            )
            ->assertForbidden();

        $this->assertTrue(app(FeatureManager::class)->isEnabled('about'));
    }

    public function test_generic_settings_listing_does_not_expose_feature_controls(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        app(FeatureManager::class)->update(
            'flights',
            $this->state(false),
        );

        $this->actingAs($superAdmin)
            ->getJson(route('admin.settings.index'))
            ->assertOk()
            ->assertJsonMissing([
                'group' => 'features',
                'key' => 'flights',
            ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertDontSee('features.flights');
    }

    public function test_generic_settings_endpoint_cannot_create_unknown_feature_key(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->postJson(
                route('admin.settings.store', [
                    'group' => 'features',
                    'key' => 'not-registered',
                ]),
                [
                    'value' => ['enabled' => true],
                    'type' => 'json',
                    'is_public' => false,
                ],
            )
            ->assertNotFound();

        $this->assertDatabaseMissing('settings', [
            'group' => 'features',
            'key' => 'not-registered',
        ]);
    }

    public function test_generic_settings_endpoint_cannot_update_or_delete_feature_state(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        app(FeatureManager::class)->update(
            'flights',
            $this->state(false),
        );

        $this->actingAs($superAdmin)
            ->putJson(
                route('admin.settings.update', [
                    'group' => 'features',
                    'key' => 'flights',
                ]),
                [
                    'value' => $this->state(true),
                    'type' => 'json',
                    'is_public' => false,
                ],
            )
            ->assertNotFound();

        $this->actingAs($superAdmin)
            ->deleteJson(
                route('admin.settings.destroy', [
                    'group' => 'features',
                    'key' => 'flights',
                ]),
            )
            ->assertNotFound();

        $this->app->forgetInstance(FeatureManager::class);

        $this->assertFalse(app(FeatureManager::class)->isEnabled('flights'));
        $this->assertDatabaseHas('settings', [
            'group' => 'features',
            'key' => 'flights',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array<string, bool|string|null>
     */
    private function state(bool $enabled, ?string $message = null): array
    {
        return [
            'enabled' => $enabled,
            'public_visible' => true,
            'authenticated_visible' => true,
            'admin_visible' => true,
            'message' => $message,
        ];
    }
}
