<?php

namespace Tests\Feature\Travel;

use App\Contracts\Tour\TourSearchProvider;
use App\Services\Tour\ViatorTourSearchProvider;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

class ViatorTourSearchProviderTest extends TestCase
{
    private const SECRET = 'viator-test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_viator_capability_requires_explicit_enablement(): void
    {
        $this->configureViator();

        config()->set(
            'travel_services.services.tours.provider',
            'viator'
        );

        config()->set(
            'travel_services.services.tours.enabled',
            false
        );

        $registry = app(TravelServiceRegistry::class);

        $this->assertFalse(
            $registry->all()['tours']['available']
        );

        config()->set(
            'travel_services.services.tours.enabled',
            true
        );

        $services = $registry->all();

        $this->assertTrue(
            $services['tours']['available']
        );

        $this->assertInstanceOf(
            ViatorTourSearchProvider::class,
            app(TourSearchProvider::class)
        );

        $this->assertFalse(
            config(
                'travel_services.services.tours.viator.booking_access'
            )
        );

        $this->assertStringNotContainsString(
            self::SECRET,
            json_encode(
                $services,
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function test_missing_api_key_fails_before_http(): void
    {
        Http::fake();

        $this->configureViator();

        config()->set(
            'travel_services.services.tours.viator.api_key',
            null
        );

        $exception = $this->captureException(
            fn () => (new ViatorTourSearchProvider)
                ->search($this->criteria())
        );

        $this->assertSame(503, $exception->getStatusCode());
        $this->assertSame(
            'Tour search provider is not configured.',
            $exception->getMessage()
        );

        Http::assertNothingSent();
    }

    public function test_search_uses_official_viator_freetext_contract(): void
    {
        $this->configureViator();

        Http::fake([
            'https://api.viator.com/partner/search/freetext' => Http::response([
                'products' => [],
            ]),
        ]);

        $results = (new ViatorTourSearchProvider)
            ->search(
                $this->criteria([
                    'destination' => 'Paris',
                    'travel_date' => '2030-06-10',
                    'travelers' => 4,
                ])
            );

        $this->assertSame([], $results);

        Http::assertSent(
            function (Request $request): bool {
                $body = $request->data();

                return
                    $request->method() === 'POST'
                    && $request->url()
                        === 'https://api.viator.com/partner/search/freetext'
                    && $request->hasHeader(
                        'exp-api-key',
                        self::SECRET
                    )
                    && $request->hasHeader(
                        'Accept-Language',
                        'en-US'
                    )
                    && $request->hasHeader(
                        'Accept',
                        'application/json;version=2.0'
                    )
                    && data_get(
                        $body,
                        'searchTerm'
                    ) === 'Paris'
                    && data_get(
                        $body,
                        'productFiltering.dateRange.from'
                    ) === '2030-06-10'
                    && data_get(
                        $body,
                        'productFiltering.dateRange.to'
                    ) === '2030-06-10'
                    && data_get(
                        $body,
                        'searchTypes.0.searchType'
                    ) === 'PRODUCTS'
                    && data_get(
                        $body,
                        'searchTypes.0.pagination.start'
                    ) === 1
                    && data_get(
                        $body,
                        'searchTypes.0.pagination.count'
                    ) === 20
                    && data_get(
                        $body,
                        'currency'
                    ) === 'USD'
                    && ! array_key_exists(
                        'travelers',
                        $body
                    );
            }
        );

        Http::assertSentCount(1);
    }

    public function test_search_without_date_does_not_fake_availability(): void
    {
        $this->configureViator();

        Http::fake([
            'https://api.viator.com/partner/search/freetext' => Http::response([
                'products' => [],
            ]),
        ]);

        (new ViatorTourSearchProvider)
            ->search(
                $this->criteria([
                    'travel_date' => null,
                ])
            );

        Http::assertSent(
            function (Request $request): bool {
                return ! array_key_exists(
                    'productFiltering',
                    $request->data()
                );
            }
        );
    }

    public function test_product_summary_is_normalized(): void
    {
        $this->configureViator();

        Http::fake([
            'https://api.viator.com/partner/search/freetext' => Http::response([
                'products' => [
                    [
                        'productCode' => '12345P1',
                        'title' => 'Paris Walking Tour',
                        'description' => 'Provider supplied summary.',
                        'pricing' => [
                            'summary' => [
                                'fromPrice' => 99.99,
                            ],
                        ],
                        'reviews' => [
                            'combinedAverageRating' => 4.9,
                        ],
                    ],
                ],
            ]),
        ]);

        $results = (new ViatorTourSearchProvider)
            ->search($this->criteria());

        $this->assertSame([
            [
                'reference' => '12345P1',
                'title' => 'Paris Walking Tour',
                'location' => '',
                'summary' => 'Provider supplied summary.',
            ],
        ], $results);

        $encoded = json_encode(
            $results,
            JSON_THROW_ON_ERROR
        );

        $this->assertStringNotContainsString(
            'fromPrice',
            $encoded
        );

        $this->assertStringNotContainsString(
            'combinedAverageRating',
            $encoded
        );
    }

    public function test_empty_products_are_safe(): void
    {
        $this->configureViator();

        Http::fake([
            'https://api.viator.com/partner/search/freetext' => Http::response([
                'products' => [],
            ]),
        ]);

        $this->assertSame(
            [],
            (new ViatorTourSearchProvider)
                ->search($this->criteria())
        );
    }

    public function test_malformed_response_fails_closed(): void
    {
        $this->configureViator();

        Http::fake([
            'https://api.viator.com/partner/search/freetext' => Http::response([
                'products' => [
                    [
                        'productCode' => '12345P1',
                        'title' => 999,
                    ],
                ],
            ]),
        ]);

        $exception = $this->captureException(
            fn () => (new ViatorTourSearchProvider)
                ->search($this->criteria())
        );

        $this->assertSame(
            'Tour search provider returned an invalid response.',
            $exception->getMessage()
        );
    }

    public function test_supplier_error_is_sanitized_and_not_retried(): void
    {
        $this->configureViator();

        Http::fake([
            'https://api.viator.com/partner/search/freetext' => Http::response([
                'error' => 'raw secret '.self::SECRET,
            ], 503),
        ]);

        $exception = $this->captureException(
            fn () => (new ViatorTourSearchProvider)
                ->search($this->criteria())
        );

        $this->assertSame(503, $exception->getStatusCode());

        $this->assertSame(
            'Tour search provider is temporarily unavailable.',
            $exception->getMessage()
        );

        $this->assertStringNotContainsString(
            self::SECRET,
            $exception->getMessage()
        );

        Http::assertSentCount(1);
    }

    public function test_supplier_reference_is_not_rendered_in_html(): void
    {
        $html = view(
            'tours.results',
            [
                'criteria' => $this->criteria(),
                'tours' => [
                    [
                        'reference' => 'private_viator_product_code',
                        'title' => 'Safe Tour',
                        'location' => '',
                        'summary' => 'Safe summary.',
                    ],
                ],
            ]
        )->render();

        $this->assertStringNotContainsString(
            'private_viator_product_code',
            $html
        );

        $this->assertStringContainsString(
            'Safe Tour',
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
                'destination' => 'Paris',
                'travel_date' => '2030-06-10',
                'travelers' => 2,
            ],
            $overrides
        );
    }

    private function configureViator(): void
    {
        config()->set([
            'travel_services.services.tours.viator.base_url' => 'https://api.viator.com/partner',

            'travel_services.services.tours.viator.api_key' => self::SECRET,

            'travel_services.services.tours.viator.api_version' => '2.0',

            'travel_services.services.tours.viator.locale' => 'en-US',

            'travel_services.services.tours.viator.currency' => 'USD',

            'travel_services.services.tours.viator.connect_timeout' => '5',

            'travel_services.services.tours.viator.http_timeout' => '20',

            'travel_services.services.tours.viator.search_count' => '20',
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
