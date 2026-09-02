<?php

namespace Tests\Feature\Flight;

use App\Models\FlightOrderPaymentAttempt;
use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use App\Services\Flight\FlightOrderPaymentAttemptRecordStore;
use App\Services\Flight\FlightOrderPaymentReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FlightOrderPaymentReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_and_cross_user_references_fail_before_supplier_http(): void
    {
        $this->configureDuffel();

        Http::fake();

        $owner =
            User::factory()
                ->create();

        $other =
            User::factory()
                ->create();

        $payment =
            $this->processingPayment(
                $owner->id,
                'off_crossreconcile1',
                'ord_crossreconcile1',
                '20.00',
                'USD',
            );

        $this->assertNull(
            $this->service()
                ->reconcile(
                    $owner->id,
                    str_repeat(
                        'Z',
                        64,
                    ),
                ),
        );

        $this->assertNull(
            $this->service()
                ->reconcile(
                    $other->id,
                    $payment['reference'],
                ),
        );

        Http::assertNothingSent();
    }

    public function test_terminal_local_payment_returns_without_supplier_http(): void
    {
        $this->configureDuffel();

        Http::fake();

        $user =
            User::factory()
                ->create();

        $payment =
            $this->processingPayment(
                $user->id,
                'off_terminalreconcile1',
                'ord_terminalreconcile1',
                '30.00',
                'GBP',
            );

        $store =
            app(
                FlightOrderPaymentAttemptRecordStore::class,
            );

        $store->markSucceeded(
            'duffel',
            'ord_terminalreconcile1',
            'pay_terminalreconcile1',
        );

        $result =
            $this->service()
                ->reconcile(
                    $user->id,
                    $payment['reference'],
                );

        $this->assertSame(
            'succeeded',
            $result['status'],
        );

        Http::assertNothingSent();
    }

    public function test_supplier_absence_keeps_processing_without_retry_or_terminal_mutation(): void
    {
        $this->configureDuffel();

        $user =
            User::factory()
                ->create();

        $payment =
            $this->processingPayment(
                $user->id,
                'off_absentreconcile1',
                'ord_absentreconcile1',
                '40.00',
                'USD',
            );

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [],
                ], 200),
        ]);

        $result =
            $this->service()
                ->reconcile(
                    $user->id,
                    $payment['reference'],
                );

        $this->assertSame(
            'processing',
            $result['status'],
        );

        $latest =
            FlightOrderPaymentAttempt::query()
                ->sole();

        $this->assertSame(
            FlightOrderPaymentAttempt::STATUS_PROCESSING,
            $latest->status,
        );

        Http::assertSentCount(1);
    }

    public function test_exact_supplier_success_marks_payment_succeeded_atomically(): void
    {
        $this->configureDuffel();

        $user =
            User::factory()
                ->create();

        $payment =
            $this->processingPayment(
                $user->id,
                'off_successreconcile1',
                'ord_successreconcile1',
                '91.50',
                'GBP',
            );

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        [
                            'id' =>
                                'pay_successreconcile1',

                            'order_id' =>
                                'ord_successreconcile1',

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
            $this->service()
                ->reconcile(
                    $user->id,
                    $payment['reference'],
                );

        $this->assertSame(
            'succeeded',
            $result['status'],
        );

        $latest =
            FlightOrderPaymentAttempt::query()
                ->sole();

        $this->assertSame(
            FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
            $latest->status,
        );

        $this->assertSame(
            'pay_successreconcile1',
            $latest->supplier_payment_id,
        );

        Http::assertSentCount(1);
    }

    public function test_pending_payment_id_is_persisted_and_next_reconciliation_uses_single_get(): void
    {
        $this->configureDuffel();

        $user =
            User::factory()
                ->create();

        $payment =
            $this->processingPayment(
                $user->id,
                'off_pendingreconcile1',
                'ord_pendingreconcile1',
                '51.00',
                'EUR',
            );

        /*
         * One fake callback handles both sequential supplier reads.
         *
         * First reconciliation:
         * GET /air/payments?order_id=...
         * returns the pending payment and persists its supplier payment ID.
         *
         * Second reconciliation:
         * GET /air/payments/{payment_id}
         * returns the same payment as succeeded.
         */
        Http::fake(
            function ($request) {
                $url =
                    $request->url();

                $path =
                    parse_url(
                        $url,
                        PHP_URL_PATH,
                    );

                if ($path === '/air/payments') {
                    $query = [];

                    parse_str(
                        (string) parse_url(
                            $url,
                            PHP_URL_QUERY,
                        ),
                        $query,
                    );

                    if (
                        ($query['order_id'] ?? null)
                            !== 'ord_pendingreconcile1'
                    ) {
                        throw new \RuntimeException(
                            'Unexpected payment order query.',
                        );
                    }

                    if (
                        (string) ($query['limit'] ?? '')
                            !== '200'
                    ) {
                        throw new \RuntimeException(
                            'Unexpected payment list limit.',
                        );
                    }

                    return Http::response([
                        'data' => [
                            [
                                'id' =>
                                    'pay_pendingreconcile1',

                                'order_id' =>
                                    'ord_pendingreconcile1',

                                'type' =>
                                    'balance',

                                'amount' =>
                                    '51.00',

                                'currency' =>
                                    'EUR',

                                'status' =>
                                    'pending',
                            ],
                        ],
                    ], 200);
                }

                if (
                    $path ===
                    '/air/payments/pay_pendingreconcile1'
                ) {
                    return Http::response([
                        'data' => [
                            'id' =>
                                'pay_pendingreconcile1',

                            'order_id' =>
                                'ord_pendingreconcile1',

                            'type' =>
                                'balance',

                            'amount' =>
                                '51.00',

                            'currency' =>
                                'EUR',

                            'status' =>
                                'succeeded',
                        ],
                    ], 200);
                }

                throw new \RuntimeException(
                    'Unexpected supplier URL: '
                        . $url,
                );
            },
        );

        $first =
            $this->service()
                ->reconcile(
                    $user->id,
                    $payment['reference'],
                );

        $this->assertSame(
            'processing',
            $first['status'],
        );

        $this->assertSame(
            'pay_pendingreconcile1',
            FlightOrderPaymentAttempt::query()
                ->sole()
                ->supplier_payment_id,
        );

        $second =
            $this->service()
                ->reconcile(
                    $user->id,
                    $payment['reference'],
                );

        $this->assertSame(
            'succeeded',
            $second['status'],
        );

        $latest =
            FlightOrderPaymentAttempt::query()
                ->sole();

        $this->assertSame(
            FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
            $latest->status,
        );

        $this->assertSame(
            'pay_pendingreconcile1',
            $latest->supplier_payment_id,
        );

        Http::assertSentCount(2);
    }
    /**
     * @return array{
     *     reference: string
     * }
     */
    private function processingPayment(
        int $userId,
        string $supplierOfferId,
        string $supplierOrderId,
        string $amount,
        string $currency,
    ): array {
        $orderStore =
            app(
                FlightOrderAttemptRecordStore::class,
            );

        $order =
            $orderStore->createProcessing(
                $userId,
                'duffel',
                $supplierOfferId,
            );

        $this->assertIsArray(
            $order,
        );

        $createdOrder =
            $orderStore->markCreated(
                'duffel',
                $supplierOfferId,
                $supplierOrderId,
            );

        $this->assertNotNull(
            $createdOrder,
        );

        $paymentStore =
            app(
                FlightOrderPaymentAttemptRecordStore::class,
            );

        $payment =
            $paymentStore->createProcessing(
                $userId,
                (int) $createdOrder->getKey(),
                'duffel',
                $supplierOrderId,
                'balance',
                $amount,
                $currency,
            );

        $this->assertIsArray(
            $payment,
        );

        return [
            'reference' =>
                $payment['reference'],
        ];
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

    private function service(): FlightOrderPaymentReconciliationService
    {
        return app(
            FlightOrderPaymentReconciliationService::class,
        );
    }
}