<?php

namespace Tests\Feature\Travel;

use App\Services\Travel\DuffelDestinationResolver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

class DuffelDestinationResolverTest extends TestCase
{
    private const SECRET = 'duffel-destination-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_missing_token_fails_before_http(): void
    {
        Http::fake();

        $this->configureDuffel();
        config()->set(
            'travel_services.services.hotels.duffel.access_token',
            null
        );

        $exception = $this->captureException(
            fn () => (new DuffelDestinationResolver)
                ->resolve('London')
        );

        $this->assertSame(503, $exception->getStatusCode());
        $this->assertSame(
            'Hotel destination resolver is not configured.',
            $exception->getMessage()
        );

        Http::assertNothingSent();
    }

    public function test_single_city_is_resolved_with_safe_coordinates(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/places/suggestions*' => Http::response([
                'data' => [
                    [
                        'type' => 'city',
                        'name' => 'New York',
                        'latitude' => 40.7128,
                        'longitude' => -74.0060,
                    ],
                    [
                        'type' => 'airport',
                        'name' => 'John F Kennedy International',
                        'latitude' => 40.6413,
                        'longitude' => -73.7781,
                    ],
                ],
            ]),
        ]);

        $result = (new DuffelDestinationResolver)
            ->resolve('New York');

        $this->assertSame('New York', $result['name']);
        $this->assertSame(40.7128, $result['latitude']);
        $this->assertSame(-74.0060, $result['longitude']);

        Http::assertSent(
            function (Request $request): bool {
                $query = [];

                parse_str(
                    (string) parse_url(
                        $request->url(),
                        PHP_URL_QUERY
                    ),
                    $query
                );

                return
                    $request->method() === 'GET'
                    && str_starts_with(
                        $request->url(),
                        'https://api.duffel.com/places/suggestions?'
                    )
                    && ($query['query'] ?? null) === 'New York'
                    && $request->hasHeader(
                        'Authorization',
                        'Bearer '.self::SECRET
                    )
                    && $request->hasHeader(
                        'Duffel-Version',
                        'v2'
                    )
                    && $request->hasHeader(
                        'Accept',
                        'application/json'
                    );
            }
        );

        Http::assertSentCount(1);
    }

    public function test_ambiguous_cities_fail_closed(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/places/suggestions*' => Http::response([
                'data' => [
                    [
                        'type' => 'city',
                        'name' => 'London',
                        'latitude' => 51.5072,
                        'longitude' => -0.1276,
                    ],
                    [
                        'type' => 'city',
                        'name' => 'London',
                        'latitude' => 42.9849,
                        'longitude' => -81.2453,
                    ],
                ],
            ]),
        ]);

        $exception = $this->captureException(
            fn () => (new DuffelDestinationResolver)
                ->resolve('London')
        );

        $this->assertSame(
            'Hotel destination could not be resolved.',
            $exception->getMessage()
        );
    }

    public function test_null_coordinates_fail_closed(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/places/suggestions*' => Http::response([
                'data' => [
                    [
                        'type' => 'city',
                        'name' => 'London',
                        'latitude' => null,
                        'longitude' => -0.1276,
                    ],
                ],
            ]),
        ]);

        $exception = $this->captureException(
            fn () => (new DuffelDestinationResolver)
                ->resolve('London')
        );

        $this->assertSame(
            'Hotel destination could not be resolved.',
            $exception->getMessage()
        );
    }

    public function test_out_of_range_coordinates_fail_closed(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/places/suggestions*' => Http::response([
                'data' => [
                    [
                        'type' => 'city',
                        'name' => 'Invalid City',
                        'latitude' => 91.0,
                        'longitude' => 181.0,
                    ],
                ],
            ]),
        ]);

        $exception = $this->captureException(
            fn () => (new DuffelDestinationResolver)
                ->resolve('Invalid City')
        );

        $this->assertSame(
            'Hotel destination could not be resolved.',
            $exception->getMessage()
        );
    }

    public function test_supplier_http_failures_are_sanitized(): void
    {
        $this->configureDuffel();

        foreach ([401, 403, 429, 500] as $status) {
            Http::fake([
                'https://api.duffel.com/places/suggestions*' => Http::response([
                    'errors' => [
                        [
                            'message' => 'raw supplier '.self::SECRET,
                        ],
                    ],
                ], $status),
            ]);

            $exception = $this->captureException(
                fn () => (new DuffelDestinationResolver)
                    ->resolve('London')
            );

            $this->assertSame(503, $exception->getStatusCode());
            $this->assertSame(
                'Hotel destination service is temporarily unavailable.',
                $exception->getMessage()
            );
            $this->assertStringNotContainsString(
                self::SECRET,
                $exception->getMessage()
            );
        }
    }

    public function test_malformed_payload_fails_closed(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/places/suggestions*' => Http::response([
                'data' => 'not-an-array',
            ]),
        ]);

        $exception = $this->captureException(
            fn () => (new DuffelDestinationResolver)
                ->resolve('London')
        );

        $this->assertSame(
            'Hotel destination service returned an invalid response.',
            $exception->getMessage()
        );
    }

    private function configureDuffel(): void
    {
        config()->set([
            'travel_services.services.hotels.duffel.base_url' => 'https://api.duffel.com',

            'travel_services.services.hotels.duffel.access_token' => self::SECRET,

            'travel_services.services.hotels.duffel.api_version' => 'v2',

            'travel_services.services.hotels.duffel.connect_timeout' => '5',

            'travel_services.services.hotels.duffel.http_timeout' => '30',

            'travel_services.services.hotels.duffel.search_radius_km' => '5',
        ]);
    }

    private function captureException(
        callable $callback
    ): ServiceUnavailableHttpException {
        try {
            $callback();
        } catch (ServiceUnavailableHttpException $exception) {
            return $exception;
        }

        $this->fail(
            'Expected ServiceUnavailableHttpException.'
        );
    }
}
