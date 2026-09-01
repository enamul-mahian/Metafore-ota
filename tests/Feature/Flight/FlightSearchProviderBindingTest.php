<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightSearchProvider;
use App\Services\Flight\UnavailableFlightSearchProvider;
use Tests\TestCase;

class FlightSearchProviderBindingTest extends TestCase
{
    public function test_unavailable_provider_can_be_resolved_from_configuration(): void
    {
        config()->set(
            'flight.search_provider',
            'unavailable'
        );

        $provider = $this->app->make(
            FlightSearchProvider::class
        );

        $this->assertInstanceOf(
            UnavailableFlightSearchProvider::class,
            $provider
        );
    }

    public function test_configured_provider_can_be_selected_without_changing_service_layer(): void
    {
        config()->set(
            'flight.providers.fake',
            ConfiguredFakeFlightSearchProvider::class
        );

        config()->set(
            'flight.search_provider',
            'fake'
        );

        $provider = $this->app->make(
            FlightSearchProvider::class
        );

        $this->assertInstanceOf(
            ConfiguredFakeFlightSearchProvider::class,
            $provider
        );
    }

    public function test_unknown_provider_falls_back_to_unavailable_provider(): void
    {
        config()->set(
            'flight.search_provider',
            'unknown-provider'
        );

        $provider = $this->app->make(
            FlightSearchProvider::class
        );

        $this->assertInstanceOf(
            UnavailableFlightSearchProvider::class,
            $provider
        );
    }
}

class ConfiguredFakeFlightSearchProvider implements FlightSearchProvider
{
    /**
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        return [];
    }
}
