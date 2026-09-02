<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOrderPaymentReconciliationProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class DuffelFlightOrderPaymentReconciliationProviderTest extends TestCase
{
    public function test_unknown_ambiguous_payment_uses_get_list_and_remains_processing(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'meta' => [
                        'limit' =>
                            200,
                    ],
                    'data' => [],
                ], 200),
        ]);

        $result =
            $this->provider()
                ->reconcilePayment(
                    'ord_reconcilelist1',
                    null,
                    '88.40',
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

        Http::assertSent(
            function (Request $request): bool {
                $query =
                    [];
                parse_str(
                    (string) parse_url(
                        $request->url(),
                        PHP_URL_QUERY,
                    ),
                    $query,
                );

                return $request->method() === 'GET'
                    && parse_url(
                        $request->url(),
                        PHP_URL_PATH,
                    ) === '/air/payments'
                    && ($query['order_id'] ?? null)
                        === 'ord_reconcilelist1'
                    && (string) ($query['limit'] ?? '')
                        === '200';
            },
        );

        Http::assertSentCount(1);
    }

    public function test_single_exact_list_payment_can_resolve_succeeded(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        [
                            'id' =>
                                'pay_reconcilesuccess1',

                            'order_id' =>
                                'ord_reconcilesuccess1',

                            'type' =>
                                'balance',

                            'amount' =>
                                '91.50',

                            'currency' =>
                                'GBP',

                            'status' =>
                                'succeeded',
                        ],
                    ],
                ], 200),
        ]);

        $result =
            $this->provider()
                ->reconcilePayment(
                    'ord_reconcilesuccess1',
                    null,
                    '91.50',
                    'GBP',
                    'balance',
                );

        $this->assertSame(
            'succeeded',
            $result['status'],
        );

        $this->assertSame(
            'pay_reconcilesuccess1',
            $result['supplier_payment_id'],
        );

        Http::assertSentCount(1);
    }

    public function test_known_payment_id_uses_single_payment_get(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.test/air/payments/pay_knownpending1' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'pay_knownpending1',

                        'order_id' =>
                            'ord_knownpending1',

                        'type' =>
                            'balance',

                        'amount' =>
                            '40.00',

                        'currency' =>
                            'USD',

                        'status' =>
                            'pending',
                    ],
                ], 200),
        ]);

        $result =
            $this->provider()
                ->reconcilePayment(
                    'ord_knownpending1',
                    'pay_knownpending1',
                    '40.00',
                    'USD',
                    'balance',
                );

        $this->assertSame(
            'processing',
            $result['status'],
        );

        $this->assertSame(
            'pay_knownpending1',
            $result['supplier_payment_id'],
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->method() === 'GET'
                    && $request->url()
                        === 'https://api.duffel.test/air/payments/pay_knownpending1';
            },
        );

        Http::assertSentCount(1);
    }

    public function test_failed_and_cancelled_supplier_payment_are_terminal_failed(): void
    {
        $this->configureDuffel();

        foreach ([
            'failed',
            'cancelled',
        ] as $status) {
            Http::fake([
                '*' =>
                    Http::response([
                        'data' => [
                            'id' =>
                                'pay_terminalstatus1',

                            'order_id' =>
                                'ord_terminalstatus1',

                            'type' =>
                                'balance',

                            'amount' =>
                                '51.00',

                            'currency' =>
                                'EUR',

                            'status' =>
                                $status,
                        ],
                    ], 200),
            ]);

            $result =
                $this->provider()
                    ->reconcilePayment(
                        'ord_terminalstatus1',
                        'pay_terminalstatus1',
                        '51.00',
                        'EUR',
                        'balance',
                    );

            $this->assertSame(
                'failed',
                $result['status'],
            );

            Http::assertSentCount(1);
        }
    }

    public function test_multiple_exact_matches_fail_closed_without_mutation(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        [
                            'id' =>
                                'pay_duplicate1',

                            'order_id' =>
                                'ord_duplicate1',

                            'type' =>
                                'balance',

                            'amount' =>
                                '25.00',

                            'currency' =>
                                'GBP',

                            'status' =>
                                'failed',
                        ],
                        [
                            'id' =>
                                'pay_duplicate2',

                            'order_id' =>
                                'ord_duplicate1',

                            'type' =>
                                'balance',

                            'amount' =>
                                '25.00',

                            'currency' =>
                                'GBP',

                            'status' =>
                                'succeeded',
                        ],
                    ],
                ], 200),
        ]);

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        try {
            $this->provider()
                ->reconcilePayment(
                    'ord_duplicate1',
                    null,
                    '25.00',
                    'GBP',
                    'balance',
                );
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_supplier_failure_is_sanitized_and_provider_is_get_only(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'message' =>
                                    'secret supplier detail',
                            ],
                        ],
                    ],
                    500,
                ),
        ]);

        try {
            $this->provider()
                ->reconcilePayment(
                    'ord_failure1',
                    null,
                    '30.20',
                    'GBP',
                    'balance',
                );

            $this->fail(
                'Expected supplier failure.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertStringNotContainsString(
                'secret supplier detail',
                $exception->getMessage(),
            );
        }

        Http::assertSentCount(1);

        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/DuffelFlightOrderPaymentReconciliationProvider.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "->get(",
            $source,
        );

        $this->assertStringNotContainsString(
            "->post(",
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

    private function provider(): DuffelFlightOrderPaymentReconciliationProvider
    {
        return app(
            DuffelFlightOrderPaymentReconciliationProvider::class,
        );
    }
}