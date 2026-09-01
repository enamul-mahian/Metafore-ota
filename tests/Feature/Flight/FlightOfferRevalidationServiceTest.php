<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\FixtureFlightOfferRevalidationProvider;
use App\Services\Flight\FlightOfferRevalidationService;
use App\Services\Flight\UnavailableFlightOfferRevalidationProvider;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class FlightOfferRevalidationServiceTest extends TestCase
{
    public function test_fixture_offer_uses_provider_neutral_revalidation_service_without_http(): void
    {
        Http::fake();

        $offer = $this->fixtureOffer();

        $result = app(
            FlightOfferRevalidationService::class,
        )->revalidate(
            $offer,
        );

        $this->assertSame(
            'demo_only',
            $result['status'],
        );

        $this->assertSame(
            'fixture',
            $result['provider'],
        );

        $this->assertFalse(
            $result['bookable'],
        );

        $this->assertFalse(
            $result['live_revalidation'],
        );

        $this->assertSame(
            $offer,
            $result['offer'],
        );

        Http::assertNothingSent();
    }

    public function test_fixture_revalidation_never_claims_a_live_or_bookable_offer(): void
    {
        $result = app(
            FlightOfferRevalidationService::class,
        )->revalidate(
            $this->fixtureOffer(),
        );

        $this->assertSame(
            'demo_only',
            $result['status'],
        );

        $this->assertFalse(
            $result['bookable'],
        );

        $this->assertFalse(
            $result['live_revalidation'],
        );
    }

    public function test_duffel_revalidation_is_intentionally_unavailable_in_foundation(): void
    {
        Http::fake();

        $offer = $this->fixtureOffer();

        $offer['provider'] = 'duffel';
        $offer['id'] = 'off_test_1';

        try {
            app(
                FlightOfferRevalidationService::class,
            )->revalidate(
                $offer,
            );

            $this->fail(
                'Expected Duffel revalidation to remain unavailable in this foundation.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );

            $this->assertStringContainsString(
                'not available',
                $exception->getMessage(),
            );
        }

        Http::assertNothingSent();
    }

    public function test_unknown_provider_falls_back_to_unavailable_without_http(): void
    {
        Http::fake();

        $offer = $this->fixtureOffer();

        $offer['provider'] = 'malicious-provider-name';

        try {
            app(
                FlightOfferRevalidationService::class,
            )->revalidate(
                $offer,
            );

            $this->fail(
                'Expected unknown provider revalidation to be unavailable.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }

        Http::assertNothingSent();
    }

    public function test_provider_value_cannot_be_used_as_an_arbitrary_class_name(): void
    {
        Http::fake();

        $offer = $this->fixtureOffer();

        $offer['provider'] =
            FixtureFlightOfferRevalidationProvider::class;

        try {
            app(
                FlightOfferRevalidationService::class,
            )->revalidate(
                $offer,
            );

            $this->fail(
                'Expected arbitrary class-name provider value to be rejected.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }

        Http::assertNothingSent();
    }

    public function test_missing_provider_falls_back_to_unavailable(): void
    {
        $offer = $this->fixtureOffer();

        unset(
            $offer['provider'],
        );

        try {
            app(
                FlightOfferRevalidationService::class,
            )->revalidate(
                $offer,
            );

            $this->fail(
                'Expected missing provider to be unavailable.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }
    }

    public function test_revalidation_configuration_keeps_duffel_safely_unavailable(): void
    {
        $this->assertSame(
            FixtureFlightOfferRevalidationProvider::class,
            config(
                'flight_revalidation.providers.fixture',
            ),
        );

        $this->assertSame(
            UnavailableFlightOfferRevalidationProvider::class,
            config(
                'flight_revalidation.providers.duffel',
            ),
        );

        $this->assertSame(
            UnavailableFlightOfferRevalidationProvider::class,
            config(
                'flight_revalidation.fallback',
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fixtureOffer(): array
    {
        return [
            'id' => 'fixture-offer-revalidation-1',
            'provider' => 'fixture',
            'total_amount' => '15470.00',
            'currency' => 'BDT',
            'expires_at' => null,
            'requires_instant_payment' => false,
            'owner' => [
                'code' => 'MFD',
                'name' => 'MetaFore Demo Air',
            ],
            'origin' => 'DAC',
            'destination' => 'CXB',
            'slices' => [],
        ];
    }
}
