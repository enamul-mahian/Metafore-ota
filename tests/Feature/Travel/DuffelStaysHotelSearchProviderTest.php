<?php

namespace Tests\Feature\Travel;

use App\Contracts\Hotel\HotelSearchProvider;
use App\Contracts\Travel\DestinationResolver;
use App\Services\Hotel\DuffelStaysHotelSearchProvider;
use App\Services\Travel\DuffelDestinationResolver;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

class DuffelStaysHotelSearchProviderTest extends TestCase
{
    private const SECRET = 'duffel-hotel-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_duffel_capability_requires_explicit_enablement(): void
    {
        $this->configureDuffel();

        config()->set(
            'travel_services.services.hotels.provider',
            'duffel'
        );
        config()->set(
            'travel_services.services.hotels.enabled',
            false
        );

        $registry = app(TravelServiceRegistry::class);

        $this->assertFalse(
            $registry->all()['hotels']['available']
        );

        config()->set(
            'travel_services.services.hotels.enabled',
            true
        );

        $services = $registry->all();

        $this->assertTrue(
            $services['hotels']['available']
        );

        $this->assertInstanceOf(
            DuffelStaysHotelSearchProvider::class,
            app(HotelSearchProvider::class)
        );

        $this->assertInstanceOf(
            DuffelDestinationResolver::class,
            app(DestinationResolver::class)
        );

        $this->assertStringNotContainsString(
            self::SECRET,
            json_encode(
                $services,
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function test_missing_token_fails_before_http(): void
    {
        Http::fake();

        $this->configureDuffel();

        config()->set(
            'travel_services.services.hotels.duffel.access_token',
            null
        );

        $provider = new DuffelStaysHotelSearchProvider(
            $this->fixedResolver()
        );

        $exception = $this->captureException(
            fn () => $provider->search(
                $this->criteria()
            )
        );

        $this->assertSame(503, $exception->getStatusCode());
        $this->assertSame(
            'Hotel search provider is not configured.',
            $exception->getMessage()
        );

        Http::assertNothingSent();
    }

    public function test_resolver_failure_prevents_stays_post(): void
    {
        Http::fake();

        $this->configureDuffel();

        $provider = new DuffelStaysHotelSearchProvider(
            $this->failingResolver()
        );

        $exception = $this->captureException(
            fn () => $provider->search(
                $this->criteria()
            )
        );

        $this->assertSame(
            'Hotel destination could not be resolved.',
            $exception->getMessage()
        );

        Http::assertNothingSent();
    }

    public function test_search_maps_to_official_duffel_stays_request(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/stays/search' => Http::response([
                'data' => [
                    'results' => [],
                ],
            ]),
        ]);

        $provider = new DuffelStaysHotelSearchProvider(
            $this->fixedResolver()
        );

        $results = $provider->search(
            $this->criteria([
                'adults' => 3,
                'rooms' => 2,
            ])
        );

        $this->assertSame([], $results);

        Http::assertSent(
            function (Request $request): bool {
                return
                    $request->method() === 'POST'
                    && $request->url()
                        === 'https://api.duffel.com/stays/search'
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
                    )
                    && $request->data() === [
                        'data' => [
                            'rooms' => 2,
                            'location' => [
                                'radius' => 5,
                                'geographic_coordinates' => [
                                    'latitude' => 51.5072,
                                    'longitude' => -0.1276,
                                ],
                            ],
                            'guests' => [
                                ['type' => 'adult'],
                                ['type' => 'adult'],
                                ['type' => 'adult'],
                            ],
                            'check_in_date' => '2030-06-10',
                            'check_out_date' => '2030-06-13',
                        ],
                    ];
            }
        );

        Http::assertSentCount(1);
    }

    public function test_success_response_is_normalized(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/stays/search' => Http::response([
                'data' => [
                    'results' => [
                        [
                            'id' => 'srr_test_1',
                            'accommodation' => [
                                'name' => 'Duffel Test Hotel',
                                'description' => 'A test accommodation.',
                                'location' => [
                                    'address' => [
                                        'city_name' => 'London',
                                        'region' => 'England',
                                        'country_code' => 'GB',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $provider = new DuffelStaysHotelSearchProvider(
            $this->fixedResolver()
        );

        $results = $provider->search(
            $this->criteria()
        );

        $this->assertSame([
            [
                'reference' => 'srr_test_1',
                'name' => 'Duffel Test Hotel',
                'location' => 'London, England, GB',
                'summary' => 'A test accommodation.',
            ],
        ], $results);
    }

    public function test_malformed_success_fails_closed(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/stays/search' => Http::response([
                'data' => [
                    'results' => [
                        [
                            'id' => 'srr_test_1',
                            'accommodation' => [
                                'name' => 123,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $provider = new DuffelStaysHotelSearchProvider(
            $this->fixedResolver()
        );

        $exception = $this->captureException(
            fn () => $provider->search(
                $this->criteria()
            )
        );

        $this->assertSame(
            'Hotel search provider returned an invalid response.',
            $exception->getMessage()
        );
    }

    public function test_supplier_failure_is_sanitized_and_not_retried(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.com/stays/search' => Http::response([
                'errors' => [
                    [
                        'message' => 'raw supplier '.self::SECRET,
                    ],
                ],
            ], 503),
        ]);

        $provider = new DuffelStaysHotelSearchProvider(
            $this->fixedResolver()
        );

        $exception = $this->captureException(
            fn () => $provider->search(
                $this->criteria()
            )
        );

        $this->assertSame(503, $exception->getStatusCode());
        $this->assertSame(
            'Hotel search provider is temporarily unavailable.',
            $exception->getMessage()
        );
        $this->assertStringNotContainsString(
            self::SECRET,
            $exception->getMessage()
        );

        Http::assertSentCount(1);
    }

    public function test_supplier_search_reference_is_not_rendered_in_html(): void
    {
        $html = view(
            'hotels.results',
            [
                'criteria' => $this->criteria(),
                'hotels' => [
                    [
                        'reference' => 'srr_private_supplier_reference',
                        'name' => 'Safe Hotel Name',
                        'location' => 'London, GB',
                        'summary' => 'Safe summary.',
                    ],
                ],
            ]
        )->render();

        $this->assertStringNotContainsString(
            'srr_private_supplier_reference',
            $html
        );

        $this->assertStringContainsString(
            'Safe Hotel Name',
            $html
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function criteria(array $overrides = []): array
    {
        return array_merge(
            [
                'destination' => 'London',
                'check_in' => '2030-06-10',
                'check_out' => '2030-06-13',
                'adults' => 2,
                'rooms' => 1,
            ],
            $overrides
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

    private function fixedResolver(): DestinationResolver
    {
        return new class implements DestinationResolver
        {
            public function resolve(string $destination): array
            {
                return [
                    'name' => 'London',
                    'latitude' => 51.5072,
                    'longitude' => -0.1276,
                ];
            }
        };
    }

    private function failingResolver(): DestinationResolver
    {
        return new class implements DestinationResolver
        {
            public function resolve(string $destination): array
            {
                throw new ServiceUnavailableHttpException(
                    null,
                    'Hotel destination could not be resolved.'
                );
            }
        };
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
