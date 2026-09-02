<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightSearchPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_view_flight_search_page(): void
    {
        $this->get(route('flights.index'))
            ->assertRedirect(route('login'));
    }

    public function test_verified_customer_can_view_flight_search_page(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->get(route('flights.index'))
            ->assertOk()
            ->assertSee('Search Flights')
            ->assertSee('data-flight-search-form', false)
            ->assertSee(route('flights.search'), false)
            ->assertSee('name="cabin_class"', false)
            ->assertDontSee('name="cabin"', false);
    }

    public function test_verified_user_without_permission_cannot_view_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('flights.index'))
            ->assertForbidden();
    }

    public function test_customer_dashboard_links_to_flight_search(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('flights.index'), false)
            ->assertSee('Search Flights');
    }

    private function customer(): User
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $customer->assignRole('customer');

        return $customer;
    }
}
