<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOrderPaymentProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class DuffelFlightOrderPaymentProviderTest extends TestCase
{
    public function test_posts_balance_payment_once_with_server_values(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'pay_create1',

                        'order_id' =>
                            'ord_create1',

                        'type' =>
                            'balance',

                        'amount' =>
                            '30.20',

                        'currency' =>
                            'GBP',

                        'status' =>
                            'succeeded',
                    ],
                ], 201),
        ]);

        $result =
            $this->provider()
                ->createPayment(
                    'ord_create1',
                    '30.20',
                    'GBP',
                    'balance',
                );

        $this->assertSame(
            'succeeded',
            $result['status'],
        );

        $this->assertSame(
            'pay_create1',
            $result['supplier_payment_id'],
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->method() === 'POST'
                    && $request->url()
                        === 'https://api.duffel.test/air/payments'
                    && $request->data()
                        === [
                            'data' => [
                                'order_id' =>
                                    'ord_create1',

                                'payment' => [
                                    'type' =>
                                        'balance',

                                    'amount' =>
                                        '30.20',

                                    'currency' =>
                                        'GBP',
                                ],
                            ],
                        ];
            },
        );

        Http::assertSentCount(1);
    }

    public function test_supplier_500_is_ambiguous_processing_and_is_not_retried(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'message' =>
                                    'unknown supplier outcome',
                            ],
                        ],
                    ],
                    500,
                ),
        ]);

        $result =
            $this->provider()
                ->createPayment(
                    'ord_ambiguous1',
                    '40.00',
                    'USD',
                    'balance',
                );

        $this->assertSame(
            'processing',
            $result['status'],
        );

        $this->assertNull(
            $result['supplier_payment_id'],
        );

        Http::assertSentCount(1);
    }

    public function test_clear_422_is_failed_without_retry(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'type' =>
                                    'invalid_state',
                            ],
                        ],
                    ],
                    422,
                ),
        ]);

        $result =
            $this->provider()
                ->createPayment(
                    'ord_invalidstate1',
                    '40.00',
                    'USD',
                    'balance',
                );

        $this->assertSame(
            'failed',
            $result['status'],
        );

        Http::assertSentCount(1);
    }

    public function test_pending_payment_returns_processing_with_supplier_payment_id(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'pay_pending1',

                        'order_id' =>
                            'ord_pending1',

                        'type' =>
                            'balance',

                        'amount' =>
                            '51.00',

                        'currency' =>
                            'EUR',

                        'status' =>
                            'pending',
                    ],
                ], 201),
        ]);

        $result =
            $this->provider()
                ->createPayment(
                    'ord_pending1',
                    '51.00',
                    'EUR',
                    'balance',
                );

        $this->assertSame(
            'processing',
            $result['status'],
        );

        $this->assertSame(
            'pay_pending1',
            $result['supplier_payment_id'],
        );

        Http::assertSentCount(1);
    }

    public function test_malformed_success_is_treated_as_ambiguous_processing(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'pay_wrong1',

                        'order_id' =>
                            'ord_wrong_order',

                        'type' =>
                            'balance',

                        'amount' =>
                            '30.20',

                        'currency' =>
                            'GBP',

                        'status' =>
                            'succeeded',
                    ],
                ], 201),
        ]);

        $result =
            $this->provider()
                ->createPayment(
                    'ord_expected1',
                    '30.20',
                    'GBP',
                    'balance',
                );

        $this->assertSame(
            'processing',
            $result['status'],
        );

        Http::assertSentCount(1);
    }

    public function test_provider_source_has_no_retry_call(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/DuffelFlightOrderPaymentProvider.php',
                ),
            );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            "->post(",
            $source,
        );

        $this->assertStringContainsString(
            "'/air/payments'",
            $source,
        );

        $this->assertStringNotContainsString(
            '->retry(',
            $source,
        );
    }

    private function configureDuffel(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-duffel-token',
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
            5,
        );
    }

    private function provider(): DuffelFlightOrderPaymentProvider
    {
        return app(
            DuffelFlightOrderPaymentProvider::class,
        );
    }
}