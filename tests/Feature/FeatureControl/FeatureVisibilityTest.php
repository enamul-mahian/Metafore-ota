<?php

namespace Tests\Feature\FeatureControl;

use App\Models\User;
use App\Services\Feature\FeatureManager;
use App\Services\Travel\TravelServiceRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class FeatureVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_disabled_public_visibility_hides_navigation_and_blocks_direct_get(): void
    {
        app(FeatureManager::class)->update(
            'support',
            $this->state(publicVisible: false),
        );

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="'.route('support').'"', false);

        $this->get(route('support'))
            ->assertNotFound();
    }

    public function test_authenticated_visibility_hides_navigation_and_blocks_get_and_write_routes(): void
    {
        $customer = $this->userWithRole('customer');

        app(FeatureManager::class)->update(
            'flights',
            $this->state(authenticatedVisible: false),
        );

        $this->actingAs($customer)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Search Flights')
            ->assertDontSee('href="'.route('flights.index').'"', false);

        $this->actingAs($customer)
            ->get(route('flights.index'))
            ->assertNotFound();

        $this->actingAs($customer)
            ->postJson(route('flights.search'), [])
            ->assertNotFound();
    }

    public function test_disabled_feature_blocks_relevant_action_route_before_validation(): void
    {
        $customer = $this->userWithRole('customer');

        app(FeatureManager::class)->update(
            'hotels',
            $this->state(enabled: false),
        );

        $this->actingAs($customer)
            ->postJson(route('hotels.search'), [])
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'This feature is currently unavailable.',
            );
    }

    public function test_disabled_registered_feature_blocks_everyone_except_super_admin_preview(): void
    {
        $customer = $this->userWithRole('customer');
        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super-admin');

        app(FeatureManager::class)->update(
            'support',
            $this->state(enabled: false),
        );

        $this->get(route('support'))
            ->assertNotFound();

        $this->actingAs($customer)
            ->get(route('support'))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('support'))
            ->assertNotFound();

        $this->actingAs($superAdmin)
            ->get(route('support'))
            ->assertOk()
            ->assertSee('Super Admin Preview:')
            ->assertSee('disabled or hidden for other users');

        $this->assertFalse(app(FeatureManager::class)->isEnabled('support'));
    }

    public function test_disabled_payments_block_payment_endpoints_independently(): void
    {
        $customer = $this->userWithRole('customer');

        app(FeatureManager::class)->update(
            'payments',
            $this->state(enabled: false),
        );

        $this->actingAs($customer)
            ->postJson(
                route(
                    'flights.bookings.orders.attempts.payments.store',
                    ['attemptReference' => 'attempt-does-not-matter'],
                ),
            )
            ->assertNotFound();
    }

    public function test_every_registered_client_surface_is_wired_to_feature_enforcement(): void
    {
        $customer = $this->userWithRole('customer');
        $features = app(FeatureManager::class);
        $routes = [
            'flights' => ['get', route('flights.index')],
            'hotels' => ['get', route('hotels.index')],
            'tours' => ['get', route('tours.index')],
            'visa' => ['get', route('visa.index')],
            'bookings' => ['get', route('bookings.index')],
            'payments' => [
                'postJson',
                route(
                    'flights.bookings.orders.attempts.payments.store',
                    ['attemptReference' => 'audit-attempt'],
                ),
            ],
            'support' => ['get', route('support')],
            'about' => ['get', route('about')],
            'account' => ['get', route('account.overview')],
            'dashboard' => ['get', route('dashboard')],
        ];

        foreach ($routes as $feature => [$method, $url]) {
            $features->update($feature, $this->state(enabled: false));

            $this->actingAs($customer)
                ->{$method}($url)
                ->assertNotFound();

            $features->update($feature, $this->state());
        }
    }

    public function test_enabled_feature_receives_existing_normal_access(): void
    {
        $customer = $this->userWithRole('customer');

        $this->actingAs($customer)
            ->get(route('flights.index'))
            ->assertOk()
            ->assertSee('Search Flights');
    }

    public function test_admin_visibility_is_independent_from_customer_visibility(): void
    {
        $admin = $this->userWithRole('admin');
        $customer = $this->userWithRole('customer');
        $superAdmin = $this->userWithRole('super-admin');

        app(FeatureManager::class)->update(
            'flights',
            $this->state(adminVisible: false),
        );

        $this->actingAs($admin)
            ->get(route('flights.index'))
            ->assertNotFound();

        $this->actingAs($customer)
            ->get(route('flights.index'))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->get(route('flights.index'))
            ->assertOk()
            ->assertSee('Super Admin Preview:');
    }

    public function test_super_admin_preview_does_not_activate_providers_or_live_execution(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $capabilitiesBefore = app(TravelServiceRegistry::class)->all();

        app(FeatureManager::class)->update(
            'hotels',
            $this->state(enabled: false),
        );

        $this->actingAs($superAdmin)
            ->get(route('hotels.index'))
            ->assertOk()
            ->assertSee('Super Admin Preview:')
            ->assertSee('Hotel service is not configured');

        $this->actingAs($superAdmin)
            ->postJson(route('hotels.search'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'destination',
                'check_in',
                'check_out',
                'adults',
                'rooms',
            ]);

        $this->actingAs($superAdmin)
            ->postJson(route('hotels.search'), [
                'destination' => 'Dhaka',
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(2)->toDateString(),
                'adults' => 1,
                'rooms' => 1,
            ])
            ->assertServiceUnavailable();

        $this->assertFalse(app(FeatureManager::class)->isEnabled('hotels'));
        $this->assertSame(
            $capabilitiesBefore,
            app(TravelServiceRegistry::class)->all(),
        );
        $this->assertFalse(config('travel_services.services.hotels.enabled'));
        $this->assertFalse(config('travel_services.services.tours.enabled'));
        $this->assertFalse(config('travel_services.services.visa.enabled'));
        $this->assertFalse(config('flight_orders.http_execution_enabled'));
        $this->assertFalse(config('flight_orders.duffel.live_order_creation_enabled'));
    }

    public function test_unknown_middleware_feature_key_fails_closed(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        Route::get('/feature-control/unknown-check', function (): string {
            return 'must not render';
        })->middleware('feature:not-registered');

        $this->get('/feature-control/unknown-check')
            ->assertNotFound()
            ->assertDontSee('must not render');

        $this->actingAs($superAdmin)
            ->get('/feature-control/unknown-check')
            ->assertNotFound()
            ->assertDontSee('must not render');
    }

    public function test_missing_middleware_feature_key_fails_closed(): void
    {
        Route::get('/feature-control/missing-key-check', function (): string {
            return 'must not render';
        })->middleware('feature');

        $this->get('/feature-control/missing-key-check')
            ->assertNotFound()
            ->assertDontSee('must not render');
    }

    public function test_unavailable_message_is_escaped_in_controlled_404_response(): void
    {
        app(FeatureManager::class)->update(
            'support',
            $this->state(
                enabled: false,
                message: 'Scheduled <script>alert("unsafe")</script>',
            ),
        );

        $this->get(route('support'))
            ->assertNotFound()
            ->assertSee('Scheduled &lt;script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
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
    private function state(
        bool $enabled = true,
        bool $publicVisible = true,
        bool $authenticatedVisible = true,
        bool $adminVisible = true,
        ?string $message = null,
    ): array {
        return [
            'enabled' => $enabled,
            'public_visible' => $publicVisible,
            'authenticated_visible' => $authenticatedVisible,
            'admin_visible' => $adminVisible,
            'message' => $message,
        ];
    }
}
