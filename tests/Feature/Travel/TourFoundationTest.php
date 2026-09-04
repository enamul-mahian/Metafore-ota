<?php

namespace Tests\Feature\Travel;

use App\Contracts\Tour\TourSearchProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_tour_service(): void
    {
        $this->get(route('tours.index'))
            ->assertRedirect(route('login'));
    }

    public function test_verified_customer_sees_safe_unconfigured_state(): void
    {
        $this->actingAs($this->customer())
            ->get(route('tours.index'))
            ->assertOk()
            ->assertSee('Tour service is not configured')
            ->assertSee('Not Configured')
            ->assertDontSee('Search Tours');
    }

    public function test_user_without_permission_cannot_access_tour_service(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('tours.index'))
            ->assertForbidden();
    }

    public function test_unconfigured_tour_search_fails_safely(): void
    {
        $this->actingAs($this->customer())
            ->post(route('tours.search'), $this->validSearch())
            ->assertServiceUnavailable()
            ->assertDontSee('booking confirmed');
    }

    public function test_configured_provider_can_return_an_honest_empty_result(): void
    {
        config()->set('travel_services.services.tours.enabled', true);
        config()->set(
            'travel_services.services.tours.provider',
            'test-provider'
        );
        config()->set(
            'travel_services.services.tours.providers.test-provider',
            EmptyTourSearchProvider::class
        );
        config()->set(
            'travel_services.services.tours.provider_requirements.test-provider',
            ['credentials.api_key']
        );
        config()->set(
            'travel_services.services.tours.credentials.api_key',
            'server-only-test-key'
        );

        $this->app->forgetInstance(TourSearchProvider::class);

        $this->actingAs($this->customer())
            ->post(route('tours.search'), $this->validSearch())
            ->assertOk()
            ->assertSee('No tours were returned')
            ->assertSeeText('No availability, price or booking has been assumed')
            ->assertDontSee('server-only-test-key');
    }

    public function test_tour_search_input_is_validated_before_provider_use(): void
    {
        $this->actingAs($this->customer())
            ->post(route('tours.search'), [
                'destination' => '',
                'travel_date' => now()->subDay()->toDateString(),
                'travelers' => 0,
            ])
            ->assertSessionHasErrors([
                'destination',
                'travel_date',
                'travelers',
            ]);
    }

    private function customer(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validSearch(): array
    {
        return [
            'destination' => 'Coxs Bazar',
            'travel_date' => now()->addWeek()->toDateString(),
            'travelers' => 2,
        ];
    }
}

class EmptyTourSearchProvider implements TourSearchProvider
{
    public function search(array $criteria): array
    {
        return [];
    }
}
