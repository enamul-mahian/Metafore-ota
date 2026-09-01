<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightOrderProvider;
use App\Services\Flight\FlightOrderService;
use App\Services\Flight\UnavailableFlightOrderProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class ArbitraryFlightOrderProvider implements FlightOrderProvider
{
    public function createFromTrustedConfirmationIntent(
        array $trustedConfirmationIntent
    ): array {
        throw new RuntimeException(
            'Payload provider value must not resolve arbitrary classes.'
        );
    }
}

final class FlightOrderServiceTest extends TestCase
{
    public function test_fixture_provider_is_unavailable_without_http(): void
    {
        Http::fake();

        $result = $this->service()->createFromTrustedConfirmationIntent([
            'provider' => 'fixture',
        ]);

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => 'fixture',
            'live_order_creation' => false,
            'order_created' => false,
        ], $result);

        Http::assertNothingSent();
    }

    public function test_duffel_provider_is_unavailable_without_http(): void
    {
        Http::fake();

        $result = $this->service()->createFromTrustedConfirmationIntent([
            'provider' => 'duffel',
        ]);

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => 'duffel',
            'live_order_creation' => false,
            'order_created' => false,
        ], $result);

        Http::assertNothingSent();
    }

    public function test_missing_provider_fails_closed(): void
    {
        Http::fake();

        $result = $this->service()->createFromTrustedConfirmationIntent([]);

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => null,
            'live_order_creation' => false,
            'order_created' => false,
        ], $result);

        Http::assertNothingSent();
    }

    public function test_unknown_provider_fails_closed(): void
    {
        Http::fake();

        $result = $this->service()->createFromTrustedConfirmationIntent([
            'provider' => 'unknown',
        ]);

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => null,
            'live_order_creation' => false,
            'order_created' => false,
        ], $result);

        Http::assertNothingSent();
    }

    public function test_payload_provider_cannot_resolve_arbitrary_class(): void
    {
        Http::fake();

        $result = $this->service()->createFromTrustedConfirmationIntent([
            'provider' => ArbitraryFlightOrderProvider::class,
        ]);

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => null,
            'live_order_creation' => false,
            'order_created' => false,
        ], $result);

        Http::assertNothingSent();
    }

    public function test_invalid_config_mapping_fails_closed(): void
    {
        Http::fake();

        config()->set('flight_orders.providers', [
            'duffel' => \stdClass::class,
        ]);

        $result = $this->service()->createFromTrustedConfirmationIntent([
            'provider' => 'duffel',
        ]);

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => 'duffel',
            'live_order_creation' => false,
            'order_created' => false,
        ], $result);

        Http::assertNothingSent();
    }

    public function test_result_does_not_echo_sensitive_snapshot_fields(): void
    {
        Http::fake();

        $result = $this->service()->createFromTrustedConfirmationIntent([
            'provider' => 'fixture',
            'total_amount' => '999.99',
            'total_currency' => 'USD',
            'owner' => 'unsafe-owner',
            'travelers' => [
                [
                    'given_name' => 'Sensitive',
                    'family_name' => 'Traveler',
                ],
            ],
        ]);

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => 'fixture',
            'live_order_creation' => false,
            'order_created' => false,
        ], $result);

        $this->assertArrayNotHasKey('total_amount', $result);
        $this->assertArrayNotHasKey('total_currency', $result);
        $this->assertArrayNotHasKey('owner', $result);
        $this->assertArrayNotHasKey('travelers', $result);

        Http::assertNothingSent();
    }

    public function test_config_maps_current_providers_to_unavailable_provider(): void
    {
        $this->assertSame(
            UnavailableFlightOrderProvider::class,
            config('flight_orders.providers.fixture')
        );

        $this->assertSame(
            UnavailableFlightOrderProvider::class,
            config('flight_orders.providers.duffel')
        );
    }

    private function service(): FlightOrderService
    {
        return app(FlightOrderService::class);
    }
}
