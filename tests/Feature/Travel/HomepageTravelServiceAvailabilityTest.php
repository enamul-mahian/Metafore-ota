<?php

namespace Tests\Feature\Travel;

use App\Contracts\Hotel\HotelSearchProvider;
use App\Contracts\Tour\TourSearchProvider;
use App\Contracts\Visa\VisaInformationProvider;
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

    public function test_all_configured_services_render_real_links_without_rendering_api_keys(): void
    {
        $this->configureHotelProvider('hotel-server-key');
        $this->configureProvider(
            'tours',
            HomepageTourSearchProvider::class,
            'tour-server-key'
        );
        $this->configureProvider(
            'visa',
            HomepageVisaInformationProvider::class,
            'visa-server-key'
        );

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('href="http://localhost:8000/hotels"', false)
            ->assertSee('href="http://localhost:8000/tours"', false)
            ->assertSee('href="http://localhost:8000/visa"', false);

        foreach ([
            'hotel-server-key',
            'tour-server-key',
            'visa-server-key',
        ] as $secret) {
            $response->assertDontSee($secret);
        }
    }

    private function configureHotelProvider(?string $apiKey): void
    {
        $this->configureProvider(
            'hotels',
            HomepageHotelSearchProvider::class,
            $apiKey
        );
    }

    /**
     * @param  class-string  $providerClass
     */
    private function configureProvider(
        string $service,
        string $providerClass,
        ?string $apiKey,
    ): void {
        config()->set("travel_services.services.{$service}.enabled", true);
        config()->set(
            "travel_services.services.{$service}.provider",
            'test-provider'
        );
        config()->set(
            "travel_services.services.{$service}.providers.test-provider",
            $providerClass
        );
        config()->set(
            "travel_services.services.{$service}.provider_requirements.test-provider",
            ['credentials.api_key']
        );
        config()->set(
            "travel_services.services.{$service}.credentials.api_key",
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

class HomepageTourSearchProvider implements TourSearchProvider
{
    public function search(array $criteria): array
    {
        return [];
    }
}

class HomepageVisaInformationProvider implements VisaInformationProvider
{
    public function requirements(array $criteria): array
    {
        return [];
    }
}
