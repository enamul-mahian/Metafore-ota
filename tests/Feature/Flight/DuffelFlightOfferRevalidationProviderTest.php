<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOfferRevalidationProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class DuffelFlightOfferRevalidationProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'flight.duffel.base_url',
            'https://api.duffel.com',
        );

        config()->set(
            'flight.duffel.api_version',
            'v2',
        );

        config()->set(
            'flight.duffel.http_timeout',
            30,
        );
    }

    public function test_missing_access_token_fails_before_http_request(): void
    {
        Http::fake();

        config()->set(
            'flight.duffel.access_token',
            null,
        );

        try {
            $this->provider()->revalidate(
                $this->trustedOffer(),
            );

            $this->fail(
                'Expected missing access token to fail.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );

            $this->assertStringContainsString(
                'not configured',
                $exception->getMessage(),
            );
        }

        Http::assertNothingSent();
    }

    public function test_non_duffel_offer_is_rejected_before_http_request(): void
    {
        Http::fake();

        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        $offer = $this->trustedOffer();

        $offer['provider'] = 'fixture';

        try {
            $this->provider()->revalidate(
                $offer,
            );

            $this->fail(
                'Expected non-Duffel offer to be rejected.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }

        Http::assertNothingSent();
    }

    public function test_missing_trusted_offer_id_is_rejected_before_http_request(): void
    {
        Http::fake();

        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        $offer = $this->trustedOffer();

        $offer['id'] = '';

        try {
            $this->provider()->revalidate(
                $offer,
            );

            $this->fail(
                'Expected missing trusted Duffel offer ID to be rejected.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }

        Http::assertNothingSent();
    }

    public function test_revalidation_gets_trusted_offer_id_and_refreshes_supplier_fare_fields(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        Http::fake([
            'https://api.duffel.com/air/offers/off_test_1' =>
                Http::response(
                    [
                        'data' => [
                            'id' => 'off_test_1',
                            'total_amount' => '130.75',
                            'total_currency' => 'USD',
                            'expires_at' => '2030-06-01T12:30:00Z',
                            'payment_requirements' => [
                                'requires_instant_payment' => true,
                            ],
                            'owner' => [
                                'iata_code' => 'ZZ',
                                'name' => 'Duffel Test Air',
                            ],
                        ],
                    ],
                    200,
                ),
        ]);

        $trustedOffer =
            $this->trustedOffer();

        $result =
            $this->provider()->revalidate(
                $trustedOffer,
            );

        $this->assertSame(
            'revalidated',
            $result['status'],
        );

        $this->assertSame(
            'duffel',
            $result['provider'],
        );

        $this->assertTrue(
            $result['live_revalidation'],
        );

        $this->assertTrue(
            $result['price_changed'],
        );

        $this->assertSame(
            'off_test_1',
            $result['offer']['id'],
        );

        $this->assertSame(
            'duffel',
            $result['offer']['provider'],
        );

        $this->assertSame(
            '130.75',
            $result['offer']['total_amount'],
        );

        $this->assertSame(
            'USD',
            $result['offer']['currency'],
        );

        $this->assertSame(
            '2030-06-01T12:30:00Z',
            $result['offer']['expires_at'],
        );

        $this->assertTrue(
            $result['offer']['requires_instant_payment'],
        );

        $this->assertSame(
            [
                'code' => 'ZZ',
                'name' => 'Duffel Test Air',
            ],
            $result['offer']['owner'],
        );

        /*
         * Route/slices remain the original trusted normalized selection.
         */
        $this->assertSame(
            'DAC',
            $result['offer']['origin'],
        );

        $this->assertSame(
            'DXB',
            $result['offer']['destination'],
        );

        $this->assertSame(
            $trustedOffer['slices'],
            $result['offer']['slices'],
        );

        Http::assertSentCount(
            1,
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->method() === 'GET'
                    && $request->url()
                        === 'https://api.duffel.com/air/offers/off_test_1'
                    && $request->hasHeader(
                        'Authorization',
                        'Bearer test-token',
                    )
                    && $request->hasHeader(
                        'Duffel-Version',
                        'v2',
                    )
                    && $request->hasHeader(
                        'Accept',
                        'application/json',
                    );
            },
        );

        Http::assertNotSent(
            fn (Request $request): bool =>
                str_contains(
                    $request->url(),
                    '/air/orders',
                ),
        );
    }

    public function test_price_changed_is_false_when_refreshed_amount_and_currency_match(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        Http::fake([
            'https://api.duffel.com/air/offers/off_test_1' =>
                Http::response(
                    [
                        'data' => [
                            'id' => 'off_test_1',
                            'total_amount' => '125.40',
                            'total_currency' => 'USD',
                            'expires_at' => '2030-06-01T12:30:00Z',
                            'payment_requirements' => [
                                'requires_instant_payment' => false,
                            ],
                            'owner' => [
                                'iata_code' => 'ZZ',
                                'name' => 'Duffel Test Air',
                            ],
                        ],
                    ],
                    200,
                ),
        ]);

        $result =
            $this->provider()->revalidate(
                $this->trustedOffer(),
            );

        $this->assertFalse(
            $result['price_changed'],
        );

        $this->assertSame(
            '125.40',
            $result['offer']['total_amount'],
        );

        $this->assertSame(
            'USD',
            $result['offer']['currency'],
        );
    }

    public function test_supplier_offer_id_mismatch_is_rejected(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        Http::fake([
            'https://api.duffel.com/air/offers/off_test_1' =>
                Http::response(
                    [
                        'data' => [
                            'id' => 'off_different',
                            'total_amount' => '125.40',
                            'total_currency' => 'USD',
                        ],
                    ],
                    200,
                ),
        ]);

        try {
            $this->provider()->revalidate(
                $this->trustedOffer(),
            );

            $this->fail(
                'Expected supplier offer ID mismatch to fail.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }
    }

    public function test_upstream_error_is_mapped_to_generic_service_unavailable(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        Http::fake([
            'https://api.duffel.com/air/offers/off_test_1' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'title' =>
                                    'supplier-internal-secret-message',
                            ],
                        ],
                    ],
                    500,
                ),
        ]);

        try {
            $this->provider()->revalidate(
                $this->trustedOffer(),
            );

            $this->fail(
                'Expected Duffel upstream error to fail.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );

            $this->assertSame(
                'Duffel flight offer revalidation is temporarily unavailable.',
                $exception->getMessage(),
            );

            $this->assertStringNotContainsString(
                'supplier-internal-secret-message',
                $exception->getMessage(),
            );
        }
    }

    public function test_malformed_success_response_is_rejected(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        Http::fake([
            'https://api.duffel.com/air/offers/off_test_1' =>
                Http::response(
                    [
                        'data' => [
                            'id' => 'off_test_1',
                        ],
                    ],
                    200,
                ),
        ]);

        try {
            $this->provider()->revalidate(
                $this->trustedOffer(),
            );

            $this->fail(
                'Expected incomplete Duffel offer response to fail.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }
    }

    public function test_revalidation_does_not_use_search_supplier_timeout_query_or_order_endpoint(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        config()->set(
            'flight.duffel.supplier_timeout_ms',
            99999,
        );

        Http::fake([
            'https://api.duffel.com/air/offers/off_test_1' =>
                Http::response(
                    [
                        'data' => [
                            'id' => 'off_test_1',
                            'total_amount' => '125.40',
                            'total_currency' => 'USD',
                            'expires_at' => '2030-06-01T12:30:00Z',
                            'payment_requirements' => [
                                'requires_instant_payment' => false,
                            ],
                            'owner' => [
                                'iata_code' => 'ZZ',
                                'name' => 'Duffel Test Air',
                            ],
                        ],
                    ],
                    200,
                ),
        ]);

        $this->provider()->revalidate(
            $this->trustedOffer(),
        );

        Http::assertSentCount(
            1,
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->method() === 'GET'
                    && ! str_contains(
                        $request->url(),
                        'supplier_timeout',
                    )
                    && ! str_contains(
                        $request->url(),
                        '/air/orders',
                    );
            },
        );
    }

    private function provider(): DuffelFlightOfferRevalidationProvider
    {
        return app(
            DuffelFlightOfferRevalidationProvider::class,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function trustedOffer(): array
    {
        return [
            'id' => 'off_test_1',
            'provider' => 'duffel',
            'total_amount' => '125.40',
            'currency' => 'USD',
            'expires_at' => '2030-06-01T12:00:00Z',
            'requires_instant_payment' => false,
            'owner' => [
                'code' => 'ZZ',
                'name' => 'Original Duffel Test Air',
            ],
            'origin' => 'DAC',
            'destination' => 'DXB',
            'slices' => [
                [
                    'id' => 'sli_trusted_1',
                    'origin' => [
                        'iata_code' => 'DAC',
                    ],
                    'destination' => [
                        'iata_code' => 'DXB',
                    ],
                    'segments' => [],
                ],
            ],
        ];
    }
}
