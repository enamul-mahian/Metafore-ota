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

    public function test_guest_can_view_flight_search_page_with_sign_in_step(): void
    {
        $this->get(route('flights.index'))
            ->assertOk()
            ->assertSee('Search Flights')
            ->assertSee('data-flight-search-form', false)
            ->assertSee(route('flights.search'), false)
            ->assertSee('data-flight-sign-in-url="'.route('flights.sign-in').'"', false);
    }

    public function test_signed_in_customer_page_has_no_guest_sign_in_step(): void
    {
        $this->actingAs($this->customer())
            ->get(route('flights.index'))
            ->assertOk()
            ->assertDontSee('data-flight-sign-in-url', false);
    }

    public function test_unverified_user_is_sent_to_email_verification(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $user->assignRole('customer');

        $this->actingAs($user)
            ->get(route('flights.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_search_form_is_prefilled_from_valid_query_and_searches_on_load(): void
    {
        $departure = now()->addDays(10)->toDateString();

        $this->get(route('flights.index', [
            'trip_type' => 'one_way',
            'origin' => 'dac',
            'destination' => 'DXB',
            'departure_date' => $departure,
            'adults' => '2',
            'cabin_class' => 'business',
        ]))
            ->assertOk()
            ->assertSee('value="DAC"', false)
            ->assertSee('value="DXB"', false)
            ->assertSee('value="'.$departure.'"', false)
            ->assertSee('value="2"', false)
            ->assertSee('value="business" selected', false)
            ->assertSee('data-flight-auto-search', false);
    }

    public function test_invalid_query_values_are_not_prefilled_or_auto_searched(): void
    {
        $this->get(route('flights.index', [
            'origin' => '<script>',
            'destination' => 'DUBAI',
            'departure_date' => 'tomorrow',
        ]))
            ->assertOk()
            ->assertDontSee('&lt;script&gt;', false)
            ->assertDontSee('value="DUBAI"', false)
            ->assertDontSee('value="tomorrow"', false)
            ->assertDontSee('data-flight-auto-search', false);
    }

    public function test_guest_sign_in_link_requires_login_and_remembers_the_search(): void
    {
        $url = route('flights.sign-in', ['destination' => 'DXB', 'origin' => 'DAC']);

        $this->get($url)->assertRedirect(route('login'));

        $this->assertSame($url, session('url.intended'));
    }

    public function test_sign_in_link_returns_customer_to_search_with_sanitised_criteria(): void
    {
        $this->actingAs($this->customer())
            ->get(route('flights.sign-in', [
                'origin' => 'dac',
                'destination' => 'DXB',
                'departure_date' => '2030-01-15',
                'adults' => '12',
                '_token' => 'secret',
            ]))
            ->assertRedirect(route('flights.index', [
                'origin' => 'DAC',
                'destination' => 'DXB',
                'departure_date' => '2030-01-15',
            ]));
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
