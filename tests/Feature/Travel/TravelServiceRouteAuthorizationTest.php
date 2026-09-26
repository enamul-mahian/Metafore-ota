<?php

namespace Tests\Feature\Travel;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TravelServiceRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_unverified_customer_is_redirected_from_service_pages(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('customer');

        foreach (
            ['hotels.index', 'tours.index', 'visa.index'] as $routeName
        ) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertRedirect(route('verification.notice'));
        }
    }

    public function test_unverified_customer_is_redirected_before_service_actions(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('customer');

        foreach (
            ['hotels.search', 'tours.search', 'visa.requirements'] as $routeName
        ) {
            $this->actingAs($user)
                ->post(route($routeName))
                ->assertRedirect(route('verification.notice'));
        }
    }

    public function test_guests_can_open_public_service_pages(): void
    {
        foreach (['hotels.index', 'tours.index', 'visa.index'] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_guest_search_is_validated_and_never_fabricates_results(): void
    {
        $this->post(route('hotels.search'), [])
            ->assertSessionHasErrors(['destination', 'check_in', 'check_out', 'adults', 'rooms']);

        $this->post(route('tours.search'), [
            'destination' => 'Bali',
            'travelers' => 2,
        ])->assertServiceUnavailable();
    }

    public function test_verified_user_without_permission_is_still_forbidden(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        foreach (['hotels.index', 'tours.index', 'visa.index'] as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertForbidden();
        }

        foreach (['hotels.search', 'tours.search', 'visa.requirements'] as $routeName) {
            $this->actingAs($user)->post(route($routeName))->assertForbidden();
        }
    }

    public function test_public_search_actions_are_rate_limited(): void
    {
        foreach (['hotels.search', 'tours.search', 'visa.requirements'] as $routeName) {
            $this->assertContains(
                'throttle:20,1',
                Route::getRoutes()->getByName($routeName)->gatherMiddleware(),
            );
        }
    }
}
