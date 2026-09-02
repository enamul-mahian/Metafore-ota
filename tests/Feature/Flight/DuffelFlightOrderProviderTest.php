<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOrderProvider;
use App\Services\Flight\DuffelFlightOrderRequestBuilder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class DuffelFlightOrderProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_live_order_gate_is_disabled_by_default_without_http(): void
    {
        Http::fake();

        config()->set(
            'flight_orders.duffel.live_order_creation_enabled',
            false,
        );

        $result =
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $this->completeIntent(),
                );

        $this->assertSame([
            'status' => 'unavailable',
            'live_order_creation' => false,
            'order_created' => false,
        ], $result);

        Http::assertNothingSent();
    }

    public function test_missing_access_token_fails_before_http(): void
    {
        Http::fake();

        $this->enableProvider();

        config()->set(
            'flight.duffel.access_token',
            '',
        );

        try {
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $this->completeIntent(),
                );

            $this->fail(
                'Missing Duffel access token should fail closed.',
            );
        } catch (
            ServiceUnavailableHttpException $exception
        ) {
            $this->assertSame(
                'Duffel flight order is temporarily unavailable.',
                $exception->getMessage(),
            );
        }

        Http::assertNothingSent();
    }

    public function test_non_duffel_intent_fails_before_http(): void
    {
        Http::fake();

        $this->enableProvider();

        $intent =
            $this->completeIntent();

        $intent['provider'] =
            'fixture';

        try {
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $intent,
                );

            $this->fail(
                'Non-Duffel intent should fail closed.',
            );
        } catch (
            ServiceUnavailableHttpException $exception
        ) {
            $this->assertSame(
                'Duffel flight order is temporarily unavailable.',
                $exception->getMessage(),
            );
        }

        Http::assertNothingSent();
    }

    public function test_trusted_hold_builder_is_posted_with_duffel_headers(): void
    {
        $this->enableProvider();

        config()->set(
            'flight.duffel.http_timeout',
            1,
        );

        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response([
                    'data' => [
                        'id' => 'ord_safe_order_1',

                        /*
                         * Supplier fields outside the safe provider
                         * contract must never be echoed back.
                         */
                        'private_supplier_field' =>
                            'do-not-return',
                    ],
                ], 201),
        ]);

        $intent =
            $this->completeIntent();

        $expectedRequest =
            app(
                DuffelFlightOrderRequestBuilder::class,
            )->buildHold(
                $intent,
            );

        $result =
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $intent,
                );

        $this->assertSame([
            'status' => 'created',
            'live_order_creation' => true,
            'order_created' => true,
            'supplier_order_id' => 'ord_safe_order_1',
        ], $result);

        $this->assertSame(
            [
                'status',
                'live_order_creation',
                'order_created',
                'supplier_order_id',
            ],
            array_keys($result),
        );

        $serialized = json_encode(
            $result,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'do-not-return',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'Tony',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'test-token',
            $serialized,
        );

        Http::assertSent(
            function (
                Request $request
            ) use (
                $expectedRequest
            ): bool {
                $data =
                    $request->data();

                return $request->method() === 'POST'
                    && $request->url()
                        === 'https://api.duffel.test/air/orders'
                    && $request->hasHeader(
                        'Authorization',
                        'Bearer test-token',
                    )
                    && $request->hasHeader(
                        'Duffel-Version',
                        'v2',
                    )
                    && $data === $expectedRequest
                    && data_get(
                        $data,
                        'data.type',
                    ) === 'hold'
                    && ! array_key_exists(
                        'payments',
                        $data['data'],
                    );
            },
        );
    }

    public function test_supplier_failure_is_generic_and_does_not_leak_payload(): void
    {
        $this->enableProvider();

        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response([
                    'errors' => [
                        [
                            'message' =>
                                'private supplier failure detail',
                        ],
                    ],
                ], 500),
        ]);

        try {
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $this->completeIntent(),
                );

            $this->fail(
                'Supplier failure should fail closed.',
            );
        } catch (
            ServiceUnavailableHttpException $exception
        ) {
            $this->assertSame(
                'Duffel flight order is temporarily unavailable.',
                $exception->getMessage(),
            );

            $this->assertStringNotContainsString(
                'private supplier failure detail',
                $exception->getMessage(),
            );
        }

        Http::assertSentCount(1);
    }

    public function test_malformed_success_response_fails_closed(): void
    {
        $this->enableProvider();

        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response([
                    'data' => [
                        'unexpected' => true,
                    ],
                ], 201),
        ]);

        try {
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $this->completeIntent(),
                );

            $this->fail(
                'Malformed supplier success should fail closed.',
            );
        } catch (
            ServiceUnavailableHttpException $exception
        ) {
            $this->assertSame(
                'Duffel flight order is temporarily unavailable.',
                $exception->getMessage(),
            );
        }

        Http::assertSentCount(1);
    }

    public function test_provider_source_has_timeout_floor_and_no_automatic_retry(): void
    {
        $source = file_get_contents(
            app_path(
                'Services/Flight/DuffelFlightOrderProvider.php',
            ),
        );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            '->timeout(',
            $source,
        );

        $this->assertStringContainsString(
            '130,',
            $source,
        );

        $this->assertStringNotContainsString(
            '->retry(',
            $source,
        );
    }

    private function enableProvider(): void
    {
        config()->set(
            'flight_orders.duffel.live_order_creation_enabled',
            true,
        );

        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        config()->set(
            'flight.duffel.base_url',
            'https://api.duffel.test',
        );

        config()->set(
            'flight.duffel.api_version',
            'v2',
        );

        config()->set(
            'flight.duffel.http_timeout',
            130,
        );
    }

    private function provider(): DuffelFlightOrderProvider
    {
        return app(
            DuffelFlightOrderProvider::class,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function completeIntent(): array
    {
        return [
            'provider' => 'duffel',

            'offer' => [
                'id' => 'off_safe_offer_1',
                'provider' => 'duffel',
                'total_amount' => '125.50',
                'currency' => 'USD',

                'requires_instant_payment' =>
                    false,

                'payment_required_by' =>
                    '2099-01-01T00:00:00Z',

                'passengers' => [
                    [
                        'id' => 'pas_adult_1',
                        'type' => 'adult',
                    ],
                ],
            ],

            'travelers' => [
                [
                    'type' => 'adult',
                    'title' => 'mr',
                    'given_name' => 'Tony',
                    'family_name' => 'Stark',
                    'date_of_birth' =>
                        '1980-07-24',
                    'gender' => 'm',
                    'email' =>
                        'tony@example.test',
                    'phone_number' =>
                        '+14155550101',
                ],
            ],

            'revalidation' => [
                'status' =>
                    'revalidated',
                'provider' =>
                    'duffel',
                'live_revalidation' =>
                    true,
                'price_changed' =>
                    false,
            ],
        ];
    }
}