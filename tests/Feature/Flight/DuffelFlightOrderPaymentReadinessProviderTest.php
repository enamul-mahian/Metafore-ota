<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOrderPaymentReadinessProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class DuffelFlightOrderPaymentReadinessProviderTest extends TestCase
{
    public function test_invalid_supplier_order_id_fails_before_http(): void
    {
        Http::fake();

        $this->configureDuffel();

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        try {
            $this->provider()
                ->readPaymentReadiness(
                    'invalid-order-id',
                );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_missing_access_token_fails_before_http(): void
    {
        Http::fake();

        $this->configureDuffel();

        config()->set(
            'flight.duffel.access_token',
            '',
        );

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        try {
            $this->provider()
                ->readPaymentReadiness(
                    'ord_readiness1',
                );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_reads_latest_order_using_get_and_normalizes_payment_readiness(): void
    {
        $this->configureDuffel();

        Http::fake([
            'https://api.duffel.test/air/orders/ord_readiness1' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_readiness1',

                        'total_amount' =>
                            '88400.00',

                        'total_currency' =>
                            'BDT',

                        'payment_status' => [
                            'awaiting_payment' =>
                                true,

                            'payment_required_by' =>
                                now()
                                    ->addHours(6)
                                    ->toIso8601String(),
                        ],
                    ],
                ], 200),
        ]);

        $result =
            $this->provider()
                ->readPaymentReadiness(
                    'ord_readiness1',
                );

        $this->assertTrue(
            $result['awaiting_payment'],
        );

        $this->assertSame(
            '88400.00',
            $result['total_amount'],
        );

        $this->assertSame(
            'BDT',
            $result['total_currency'],
        );

        $this->assertIsString(
            $result['payment_required_by'],
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->method() === 'GET'
                    && $request->url()
                        === 'https://api.duffel.test/air/orders/ord_readiness1'
                    && $request->hasHeader(
                        'Duffel-Version',
                        'v2',
                    );
            },
        );

        Http::assertSentCount(1);
    }

    public function test_mismatched_supplier_order_identity_fails_closed(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_different1',

                        'total_amount' =>
                            '88400.00',

                        'total_currency' =>
                            'BDT',

                        'payment_status' => [
                            'awaiting_payment' =>
                                true,

                            'payment_required_by' =>
                                now()
                                    ->addHours(6)
                                    ->toIso8601String(),
                        ],
                    ],
                ], 200),
        ]);

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        $this->provider()
            ->readPaymentReadiness(
                'ord_readiness1',
            );
    }

    public function test_malformed_payment_state_fails_closed(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_readiness1',

                        'total_amount' =>
                            '88400.00',

                        'total_currency' =>
                            'BDT',

                        'payment_status' => [
                            'awaiting_payment' =>
                                'yes',

                            'payment_required_by' =>
                                now()
                                    ->addHours(6)
                                    ->toIso8601String(),
                        ],
                    ],
                ], 200),
        ]);

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        $this->provider()
            ->readPaymentReadiness(
                'ord_readiness1',
            );
    }

    public function test_awaiting_payment_requires_payment_deadline(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_readiness1',

                        'total_amount' =>
                            '88400.00',

                        'total_currency' =>
                            'BDT',

                        'payment_status' => [
                            'awaiting_payment' =>
                                true,

                            'payment_required_by' =>
                                null,
                        ],
                    ],
                ], 200),
        ]);

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        $this->provider()
            ->readPaymentReadiness(
                'ord_readiness1',
            );
    }

    public function test_supplier_failure_is_sanitized(): void
    {
        $this->configureDuffel();

        Http::fake([
            '*' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'message' =>
                                    'supplier internal detail',
                            ],
                        ],
                    ],
                    500,
                ),
        ]);

        try {
            $this->provider()
                ->readPaymentReadiness(
                    'ord_readiness1',
                );

            $this->fail(
                'Supplier failure should fail closed.',
            );
        } catch (
            ServiceUnavailableHttpException $exception
        ) {
            $this->assertSame(
                'Duffel flight order payment readiness is temporarily unavailable.',
                $exception->getMessage(),
            );

            $this->assertStringNotContainsString(
                'supplier internal detail',
                $exception->getMessage(),
            );
        }
    }

    public function test_provider_source_is_read_only_and_has_no_payment_mutation(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/DuffelFlightOrderPaymentReadinessProvider.php',
                ),
            );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            "->get(",
            $source,
        );

        $this->assertStringContainsString(
            "'/air/orders/'",
            $source,
        );

        $this->assertStringNotContainsString(
            "->post(",
            $source,
        );

        $this->assertStringNotContainsString(
            '/air/payments',
            $source,
        );

        $this->assertStringNotContainsString(
            'markCreated(',
            $source,
        );

        $this->assertStringNotContainsString(
            'markFailed(',
            $source,
        );

        $this->assertStringNotContainsString(
            '->save(',
            $source,
        );

        $this->assertStringNotContainsString(
            '->update(',
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

    private function provider(): DuffelFlightOrderPaymentReadinessProvider
    {
        return app(
            DuffelFlightOrderPaymentReadinessProvider::class,
        );
    }
}