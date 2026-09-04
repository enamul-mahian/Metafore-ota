<?php

namespace Tests\Feature\Travel;

use App\Contracts\Hotel\HotelSearchProvider;
use App\Services\Travel\TravelServiceRegistry;
use Tests\TestCase;

class HomepageTravelServiceAvailabilityTest extends TestCase
{
    public function test_homepage_shows_unconfigured_services_by_default(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Flights')
            ->assertSee('Available')
            ->assertSee('Hotels')
            ->assertSee('Tours')
            ->assertSee('Visa')
            ->assertSee('Not Configured')
            ->assertDontSee('MetaFore');
    }

    public function test_enabled_service_stays_unavailable_without_required_configuration(): void
    {
        $this->configureHotelProvider(apiKey: null);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Hotels')
            ->assertSee('Not Configured')
            ->assertDontSee('href="http://localhost:8000/hotels"', false);
    }

    public function test_safely_configured_service_becomes_an_available_link_without_exposing_secrets(): void
    {
        $this->configureHotelProvider(
            apiKey: 'homepage-must-never-render-this-secret'
        );

        $this->assertTrue(
            config('travel_services.services.hotels.enabled')
        );
        $this->assertSame(
            'test-provider',
            config('travel_services.services.hotels.provider')
        );
        $this->assertTrue(
            is_a(
                config(
                    'travel_services.services.hotels.providers.test-provider'
                ),
                HotelSearchProvider::class,
                true
            )
        );
        $this->assertSame(
            ['credentials.api_key'],
            config(
                'travel_services.services.hotels.provider_requirements.test-provider'
            )
        );
        $this->assertTrue(
            app(TravelServiceRegistry::class)
                ->all()['hotels']['available']
        );

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Hotels')
            ->assertSee('Available')
            ->assertSee('href="http://localhost:8000/hotels"', false)
            ->assertDontSee('homepage-must-never-render-this-secret');
    }

    private function configureHotelProvider(?string $apiKey): void
    {
        config()->set('travel_services.services.hotels.enabled', true);
        config()->set(
            'travel_services.services.hotels.provider',
            'test-provider'
        );
        config()->set(
            'travel_services.services.hotels.providers.test-provider',
            HomepageHotelSearchProvider::class
        );
        config()->set(
            'travel_services.services.hotels.provider_requirements.test-provider',
            ['credentials.api_key']
        );
        config()->set(
            'travel_services.services.hotels.credentials.api_key',
            $apiKey
        );
    }
}

class HomepageHotelSearchProvider implements HotelSearchProvider
{
    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        return [];
    }
}
