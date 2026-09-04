<?php

namespace Tests\Feature\Travel;

use App\Contracts\Hotel\HotelSearchProvider;
use App\Contracts\Tour\TourSearchProvider;
use App\Contracts\Visa\VisaInformationProvider;
use App\Services\Hotel\UnavailableHotelSearchProvider;
use App\Services\Tour\UnavailableTourSearchProvider;
use App\Services\Travel\TravelServiceRegistry;
use App\Services\Visa\UnavailableVisaInformationProvider;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

class TravelServiceCapabilityTest extends TestCase
{
    public function test_unconfigured_modules_are_unavailable_by_default(): void
    {
        $services = app(TravelServiceRegistry::class)->all();

        $this->assertTrue($services['flights']['available']);

        foreach (['hotels', 'tours', 'visa'] as $service) {
            $this->assertFalse($services[$service]['available']);
            $this->assertSame(
                'Not Configured',
                $services[$service]['status']
            );
            $this->assertNull($services[$service]['route_name']);
        }
    }

    public function test_unknown_provider_cannot_make_a_service_available(): void
    {
        config()->set('travel_services.services.hotels.enabled', true);
        config()->set(
            'travel_services.services.hotels.provider',
            'unknown-provider'
        );
        config()->set(
            'travel_services.services.hotels.credentials.api_key',
            'server-only-secret'
        );

        $services = app(TravelServiceRegistry::class)->all();

        $this->assertFalse($services['hotels']['available']);
        $this->assertStringNotContainsString(
            'server-only-secret',
            json_encode($services, JSON_THROW_ON_ERROR)
        );
    }

    public function test_container_uses_unavailable_providers_by_default(): void
    {
        $this->assertInstanceOf(
            UnavailableHotelSearchProvider::class,
            app(HotelSearchProvider::class)
        );
        $this->assertInstanceOf(
            UnavailableTourSearchProvider::class,
            app(TourSearchProvider::class)
        );
        $this->assertInstanceOf(
            UnavailableVisaInformationProvider::class,
            app(VisaInformationProvider::class)
        );
    }

    public function test_unavailable_hotel_provider_fails_safely(): void
    {
        $this->expectException(ServiceUnavailableHttpException::class);
        $this->expectExceptionMessage('Hotel service is not configured.');

        app(HotelSearchProvider::class)->search([]);
    }

    public function test_unavailable_tour_provider_fails_safely(): void
    {
        $this->expectException(ServiceUnavailableHttpException::class);
        $this->expectExceptionMessage('Tour service is not configured.');

        app(TourSearchProvider::class)->search([]);
    }

    public function test_unavailable_visa_provider_fails_safely(): void
    {
        $this->expectException(ServiceUnavailableHttpException::class);
        $this->expectExceptionMessage(
            'Visa information service is not configured.'
        );

        app(VisaInformationProvider::class)->requirements([]);
    }
}
