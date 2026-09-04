<?php

namespace Tests\Feature\Travel;

use App\Contracts\Visa\VisaInformationProvider;
use App\Services\Travel\TravelServiceRegistry;
use App\Services\Visa\SherpaVisaInformationProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

class SherpaVisaInformationProviderTest extends TestCase
{
    private const SECRET = 'sherpa-test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_sherpa_requires_explicit_enablement(): void
    {
        $this->configureSherpa();

        config()->set(
            'travel_services.services.visa.provider',
            'sherpa'
        );

        config()->set(
            'travel_services.services.visa.enabled',
            false
        );

        $registry = app(TravelServiceRegistry::class);

        $this->assertFalse(
            $registry->all()['visa']['available']
        );

        config()->set(
            'travel_services.services.visa.enabled',
            true
        );

        $services = $registry->all();

        $this->assertTrue(
            $services['visa']['available']
        );

        $this->assertInstanceOf(
            SherpaVisaInformationProvider::class,
            app(VisaInformationProvider::class)
        );

        $this->assertFalse(
            config(
                'travel_services.services.visa.sherpa.application_access'
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

        $this->configureSherpa();

        config()->set(
            'travel_services.services.visa.sherpa.api_key',
            null
        );

        $exception = $this->captureException(
            fn () => (new SherpaVisaInformationProvider)
                ->requirements($this->criteria())
        );

        $this->assertSame(503, $exception->getStatusCode());

        $this->assertSame(
            'Visa information provider is not configured.',
            $exception->getMessage()
        );

        Http::assertNothingSent();
    }

    public function test_v3_trip_request_uses_honest_trip_context(): void
    {
        $this->configureSherpa();

        Http::fake([
            'https://requirements-api.joinsherpa.com/v3/trips*' => Http::response([
                'data' => [
                    'attributes' => [
                        'headline' => 'Visa information',
                        'informationGroups' => [],
                    ],
                ],
                'included' => [],
            ]),
        ]);

        $result = (new SherpaVisaInformationProvider)
            ->requirements($this->criteria());

        $this->assertSame(
            'Visa information',
            $result['summary']
        );

        Http::assertSent(
            function (Request $request): bool {
                $body = $request->data();

                return
                    $request->method() === 'POST'
                    && str_starts_with(
                        $request->url(),
                        'https://requirements-api.joinsherpa.com/v3/trips?'
                    )
                    && str_contains(
                        $request->url(),
                        'include=procedure'
                    )
                    && $request->hasHeader(
                        'x-api-key',
                        self::SECRET
                    )
                    && $request->hasHeader(
                        'Content-Type',
                        'application/vnd.api+json'
                    )
                    && data_get(
                        $body,
                        'data.type'
                    ) === 'TRIP'
                    && data_get(
                        $body,
                        'data.attributes.traveller.passports'
                    ) === ['BGD']
                    && data_get(
                        $body,
                        'data.attributes.travelNodes.0.locationCode'
                    ) === 'BGD'
                    && data_get(
                        $body,
                        'data.attributes.travelNodes.0.departure.date'
                    ) === '2030-06-10'
                    && data_get(
                        $body,
                        'data.attributes.travelNodes.0.departure.time'
                    ) === '10:30'
                    && data_get(
                        $body,
                        'data.attributes.travelNodes.1.locationCode'
                    ) === 'ARE'
                    && data_get(
                        $body,
                        'data.attributes.travelNodes.1.arrival.date'
                    ) === '2030-06-10'
                    && data_get(
                        $body,
                        'data.attributes.travelNodes.1.arrival.time'
                    ) === '14:45'
                    && data_get(
                        $body,
                        'data.attributes.travelNodes.0.departure.travelMode'
                    ) === 'AIR'
                    && data_get(
                        $body,
                        'data.attributes.travelNodes.1.arrival.travelMode'
                    ) === 'AIR';
            }
        );

        Http::assertSentCount(1);
    }

    public function test_visa_group_is_joined_to_included_procedure(): void
    {
        $this->configureSherpa();

        Http::fake([
            'https://requirements-api.joinsherpa.com/v3/trips*' => Http::response([
                'data' => [
                    'attributes' => [
                        'headline' => 'Visa requirements for this trip.',
                        'informationGroups' => [
                            [
                                'type' => 'VISA_REQUIREMENTS',
                                'groupings' => [
                                    [
                                        'name' => 'United Arab Emirates',
                                        'data' => [
                                            [
                                                'type' => 'PROCEDURE',
                                                'id' => 'procedure-1',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => 'procedure-1',
                        'type' => 'PROCEDURE',
                        'attributes' => [
                            'title' => 'Visa requirement',
                            'description' => 'Review the stated requirement before travel.',
                            'documentTypes' => [
                                'VISA',
                                'E_VISA',
                            ],
                            'actions' => [
                                [
                                    'provider' => 'sherpa',
                                    'url' => 'https://example.test/apply',
                                    'product' => [
                                        'price' => [
                                            'value' => 100,
                                            'currency' => 'USD',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = (new SherpaVisaInformationProvider)
            ->requirements($this->criteria());

        $this->assertSame(
            'Visa requirements for this trip.',
            $result['summary']
        );

        $this->assertSame(
            [
                'Visa requirement: Review the stated requirement before travel.',
            ],
            $result['requirements']
        );

        $this->assertSame(
            [
                'VISA',
                'E_VISA',
            ],
            $result['documents']
        );

        $encoded = json_encode(
            $result,
            JSON_THROW_ON_ERROR
        );

        $this->assertStringNotContainsString(
            'example.test/apply',
            $encoded
        );

        $this->assertStringNotContainsString(
            '"price"',
            $encoded
        );
    }

    public function test_response_without_visa_group_remains_informational(): void
    {
        $this->configureSherpa();

        Http::fake([
            'https://requirements-api.joinsherpa.com/v3/trips*' => Http::response([
                'data' => [
                    'attributes' => [
                        'headline' => 'No visa group was returned.',
                    ],
                ],
            ]),
        ]);

        $result = (new SherpaVisaInformationProvider)
            ->requirements($this->criteria());

        $this->assertSame(
            'No visa group was returned.',
            $result['summary']
        );

        $this->assertSame(
            [],
            $result['requirements']
        );

        $this->assertSame(
            [],
            $result['documents']
        );
    }

    public function test_invalid_trip_fails_before_http(): void
    {
        Http::fake();

        $this->configureSherpa();

        $criteria = $this->criteria([
            'origin_country' => 'ARE',
            'destination_country' => 'ARE',
        ]);

        $exception = $this->captureException(
            fn () => (new SherpaVisaInformationProvider)
                ->requirements($criteria)
        );

        $this->assertSame(
            'Visa trip information is invalid.',
            $exception->getMessage()
        );

        Http::assertNothingSent();
    }

    public function test_malformed_response_fails_closed(): void
    {
        $this->configureSherpa();

        Http::fake([
            'https://requirements-api.joinsherpa.com/v3/trips*' => Http::response([
                'data' => [
                    'attributes' => 'invalid',
                ],
            ]),
        ]);

        $exception = $this->captureException(
            fn () => (new SherpaVisaInformationProvider)
                ->requirements($this->criteria())
        );

        $this->assertSame(
            'Visa information provider returned an invalid response.',
            $exception->getMessage()
        );
    }

    public function test_supplier_failure_is_sanitized_and_not_retried(): void
    {
        $this->configureSherpa();

        Http::fake([
            'https://requirements-api.joinsherpa.com/v3/trips*' => Http::response([
                'error' => 'supplier secret '.self::SECRET,
            ], 503),
        ]);

        $exception = $this->captureException(
            fn () => (new SherpaVisaInformationProvider)
                ->requirements($this->criteria())
        );

        $this->assertSame(503, $exception->getStatusCode());

        $this->assertSame(
            'Visa information provider is temporarily unavailable.',
            $exception->getMessage()
        );

        $this->assertStringNotContainsString(
            self::SECRET,
            $exception->getMessage()
        );

        Http::assertSentCount(1);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function criteria(array $overrides = []): array
    {
        return array_merge(
            [
                'nationality' => 'BGD',
                'origin_country' => 'BGD',
                'destination_country' => 'ARE',
                'departure_date' => '2030-06-10',
                'departure_time' => '10:30',
                'arrival_date' => '2030-06-10',
                'arrival_time' => '14:45',
            ],
            $overrides
        );
    }

    private function configureSherpa(): void
    {
        config()->set([
            'travel_services.services.visa.sherpa.base_url' => 'https://requirements-api.joinsherpa.com',

            'travel_services.services.visa.sherpa.api_key' => self::SECRET,

            'travel_services.services.visa.sherpa.locale' => 'en-US',

            'travel_services.services.visa.sherpa.currency' => 'USD',

            'travel_services.services.visa.sherpa.connect_timeout' => '5',

            'travel_services.services.visa.sherpa.http_timeout' => '20',
        ]);
    }

    private function captureException(
        callable $callback
    ): ServiceUnavailableHttpException {
        try {
            $callback();
        } catch (
            ServiceUnavailableHttpException $exception
        ) {
            return $exception;
        }

        $this->fail(
            'Expected ServiceUnavailableHttpException.'
        );
    }
}
