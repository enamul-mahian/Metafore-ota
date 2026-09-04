<?php

namespace Tests\Feature\FeatureControl;

use App\Models\FlightOrderAttempt;
use App\Models\Setting;
use App\Models\User;
use App\Services\Feature\FeatureManager;
use App\Services\SettingService;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FeatureManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_defaults_preserve_existing_feature_visibility(): void
    {
        $features = app(FeatureManager::class);

        $this->assertSame(
            [
                'flights',
                'hotels',
                'tours',
                'visa',
                'bookings',
                'payments',
                'support',
                'about',
                'account',
                'dashboard',
            ],
            array_keys($features->all()),
        );
        $this->assertTrue($features->isEnabled('flights'));
        $this->assertTrue($features->isVisibleTo('support', null));
    }

    public function test_unknown_feature_keys_fail_closed(): void
    {
        $features = app(FeatureManager::class);

        $this->assertFalse($features->isRegistered('not-registered'));
        $this->assertFalse($features->isEnabled('not-registered'));
        $this->assertFalse($features->isVisibleTo('not-registered', null));
    }

    public function test_null_or_malformed_persisted_override_fails_closed(): void
    {
        Setting::query()->create([
            'group' => 'features',
            'key' => 'support',
            'value' => null,
            'type' => 'json',
            'is_public' => false,
        ]);

        $this->assertFalse(app(FeatureManager::class)->isEnabled('support'));

        Setting::query()
            ->where('group', 'features')
            ->where('key', 'support')
            ->update(['value' => '{invalid-json']);
        app(SettingService::class)
            ->forget('features', 'support');
        $this->app->forgetInstance(FeatureManager::class);

        $this->assertFalse(app(FeatureManager::class)->isEnabled('support'));
    }

    public function test_successful_update_persists_and_invalidates_cached_state(): void
    {
        $features = app(FeatureManager::class);
        $features->update('flights', $this->state(enabled: true));

        $this->assertTrue($features->isEnabled('flights'));

        $features->update('flights', $this->state(enabled: false));

        $this->assertFalse($features->isEnabled('flights'));
        $this->assertDatabaseHas('settings', [
            'group' => 'features',
            'key' => 'flights',
            'type' => 'json',
            'is_public' => false,
        ]);

        $this->app->forgetInstance(FeatureManager::class);

        $this->assertFalse(app(FeatureManager::class)->isEnabled('flights'));
    }

    public function test_disabling_feature_preserves_existing_booking_data(): void
    {
        $user = User::factory()->create();
        $attempt = FlightOrderAttempt::query()->create([
            'user_id' => $user->id,
            'reference_hash' => str_repeat('a', 64),
            'attempt_identity_hash' => str_repeat('b', 64),
            'provider' => 'duffel',
            'supplier_offer_id' => 'off_preserved',
            'status' => FlightOrderAttempt::STATUS_CREATED,
        ]);

        app(FeatureManager::class)->update(
            'bookings',
            $this->state(enabled: false),
        );

        $this->assertModelExists($attempt);
        $this->assertSame(
            'off_preserved',
            $attempt->fresh()?->supplier_offer_id,
        );
    }

    public function test_visibility_updates_do_not_activate_travel_or_flight_providers(): void
    {
        $capabilitiesBefore = app(TravelServiceRegistry::class)->all();
        $features = app(FeatureManager::class);

        foreach (['flights', 'hotels', 'tours', 'visa'] as $feature) {
            $features->update($feature, $this->state(enabled: true));
        }

        $this->assertFalse(config('flight_orders.http_execution_enabled'));
        $this->assertFalse(config('flight_orders.duffel.live_order_creation_enabled'));
        $this->assertFalse(config('travel_services.services.hotels.enabled'));
        $this->assertSame(
            'unavailable',
            config('travel_services.services.hotels.provider'),
        );
        $this->assertFalse(config('travel_services.services.tours.enabled'));
        $this->assertSame(
            'unavailable',
            config('travel_services.services.tours.provider'),
        );
        $this->assertFalse(config('travel_services.services.visa.enabled'));
        $this->assertSame(
            'unavailable',
            config('travel_services.services.visa.provider'),
        );
        $this->assertSame(
            $capabilitiesBefore,
            app(TravelServiceRegistry::class)->all(),
        );
    }

    /**
     * @return array<string, bool|string|null>
     */
    private function state(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'public_visible' => true,
            'authenticated_visible' => true,
            'admin_visible' => true,
            'message' => null,
        ];
    }
}
