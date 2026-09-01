<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightSearchProvider;
use App\Services\Flight\DuffelFlightSearchProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

class DuffelFlightSearchProviderTest extends TestCase
{
    public function test_duffel_provider_can_be_resolved_from_configuration(): void
    {
        config()->set(
            'flight.search_provider',
            'duffel'
        );

        $provider = $this->app->make(
            FlightSearchProvider::class
        );

        $this->assertInstanceOf(
            DuffelFlightSearchProvider::class,
            $provider
        );
    }

    public function test_missing_access_token_fails_without_making_http_request(): void
    {
        Http::fake();

        config()->set(
            'flight.duffel.access_token',
            null
        );

        try {
            (new DuffelFlightSearchProvider())
                ->search($this->criteria());

            $this->fail(
                'Expected ServiceUnavailableHttpException.'
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode()
            );
        }

        Http::assertNothingSent();
    }

    public function test_round_trip_search_is_mapped_to_duffel_offer_request(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/air/offer_requests*'
                => Http::response(
                    [
                        'data' => [
                            'offers' => [],
                        ],
                    ],
                    201
                ),
        ]);

        $criteria = $this->criteria([
            'trip_type' => 'round_trip',
            'return_date' => '2030-06-20',
            'adults' => 2,
            'children' => 1,
            'infants' => 1,
            'cabin_class' => 'business',
        ]);

        $result = (new DuffelFlightSearchProvider())
            ->search($criteria);

        $this->assertSame([], $result);

        Http::assertSent(
            function (Request $request): bool {
                $body = $request->data();

                return
                    $request->method() === 'POST' &&
                    str_starts_with(
                        $request->url(),
                        'https://api.duffel.com/air/offer_requests?'
                    ) &&
                    str_contains(
                        $request->url(),
                        'return_offers=true'
                    ) &&
                    str_contains(
                        $request->url(),
                        'supplier_timeout=20000'
                    ) &&
                    $request->hasHeader(
                        'Authorization',
                        'Bearer duffel_test_example'
                    ) &&
                    $request->hasHeader(
                        'Duffel-Version',
                        'v2'
                    ) &&
                    data_get(
                        $body,
                        'data.slices'
                    ) === [
                        [
                            'origin' => 'DAC',
                            'destination' => 'DXB',
                            'departure_date' => '2030-06-10',
                        ],
                        [
                            'origin' => 'DXB',
                            'destination' => 'DAC',
                            'departure_date' => '2030-06-20',
                        ],
                    ] &&
                    data_get(
                        $body,
                        'data.passengers'
                    ) === [
                        ['type' => 'adult'],
                        ['type' => 'adult'],
                        ['type' => 'child'],
                        ['type' => 'infant_without_seat'],
                    ] &&
                    data_get(
                        $body,
                        'data.cabin_class'
                    ) === 'business';
            }
        );
    }

    public function test_duffel_offer_response_is_normalized(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/air/offer_requests*'
                => Http::response(
                    [
                        'data' => [
                            'offers' => [
                                [
                                    'id' => 'off_test_1',
                                    'total_amount' => '125.40',
                                    'total_currency' => 'USD',
                                    'expires_at' => '2030-06-01T12:30:00Z',

                                    'payment_requirements' => [
                                        'requires_instant_payment' => true,
                                    ],

                                    'owner' => [
                                        'name' => 'Duffel Airways',
                                        'iata_code' => 'ZZ',
                                        'logo_symbol_url' => 'https://example.test/zz.svg',
                                    ],

                                    'slices' => [
                                        [
                                            'duration' => 'PT05H',

                                            'origin' => [
                                                'name' => 'Dhaka',
                                                'iata_code' => 'DAC',
                                            ],

                                            'destination' => [
                                                'name' => 'Dubai',
                                                'iata_code' => 'DXB',
                                            ],

                                            'segments' => [
                                                [
                                                    'id' => 'seg_test_1',
                                                    'departing_at' => '2030-06-10T10:00:00',
                                                    'arriving_at' => '2030-06-10T15:00:00',
                                                    'duration' => 'PT05H',
                                                    'origin_terminal' => '1',
                                                    'destination_terminal' => '3',

                                                    'origin' => [
                                                        'name' => 'Dhaka',
                                                        'iata_code' => 'DAC',
                                                    ],

                                                    'destination' => [
                                                        'name' => 'Dubai',
                                                        'iata_code' => 'DXB',
                                                    ],

                                                    'marketing_carrier' => [
                                                        'name' => 'Duffel Airways',
                                                        'iata_code' => 'ZZ',
                                                        'logo_symbol_url' => 'https://example.test/zz.svg',
                                                    ],

                                                    'marketing_carrier_flight_number' => '101',

                                                    'operating_carrier' => [
                                                        'name' => 'Duffel Airways',
                                                        'iata_code' => 'ZZ',
                                                        'logo_symbol_url' => 'https://example.test/zz.svg',
                                                    ],

                                                    'operating_carrier_flight_number' => '101',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    201
                ),
        ]);

        $offers = (new DuffelFlightSearchProvider())
            ->search($this->criteria());

        $this->assertCount(1, $offers);

        $this->assertSame(
            'off_test_1',
            $offers[0]['id']
        );

        $this->assertSame(
            'duffel',
            $offers[0]['provider']
        );

        $this->assertSame(
            '125.40',
            $offers[0]['total_amount']
        );

        $this->assertSame(
            'USD',
            $offers[0]['total_currency']
        );

        $this->assertTrue(
            $offers[0]['requires_instant_payment']
        );

        $this->assertSame(
            'Duffel Airways',
            $offers[0]['owner']['name']
        );

        $this->assertSame(
            'DAC',
            $offers[0]['slices'][0]['origin']['iata_code']
        );

        $this->assertSame(
            'DXB',
            $offers[0]['slices'][0]['destination']['iata_code']
        );

        $this->assertSame(
            'ZZ',
            $offers[0]['slices'][0]['segments'][0]
                ['operating_carrier']['iata_code']
        );

        $this->assertSame(
            '101',
            $offers[0]['slices'][0]['segments'][0]
                ['marketing_carrier_flight_number']
        );
    }

    public function test_duffel_upstream_error_is_mapped_to_service_unavailable(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/air/offer_requests*'
                => Http::response(
                    [
                        'errors' => [
                            [
                                'message' => 'Supplier unavailable',
                            ],
                        ],
                    ],
                    503
                ),
        ]);

        $this->expectException(
            ServiceUnavailableHttpException::class
        );

        (new DuffelFlightSearchProvider())
            ->search($this->criteria());
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function criteria(array $overrides = []): array
    {
        return array_merge(
            [
                'trip_type' => 'one_way',
                'origin' => 'DAC',
                'destination' => 'DXB',
                'departure_date' => '2030-06-10',
                'return_date' => null,
                'adults' => 1,
                'children' => 0,
                'infants' => 0,
                'cabin_class' => 'economy',
            ],
            $overrides
        );
    }

    private function configureDuffel(): void
    {
        config()->set([
            'flight.duffel.base_url'
                => 'https://api.duffel.com',

            'flight.duffel.access_token'
                => 'duffel_test_example',

            'flight.duffel.api_version'
                => 'v2',

            'flight.duffel.http_timeout'
                => 30,

            'flight.duffel.supplier_timeout_ms'
                => 20000,
        ]);
    }
}
