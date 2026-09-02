<?php

namespace Tests\Feature\Flight;

use App\Models\FlightOrderPaymentAttempt;
use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use App\Services\Flight\FlightOrderPaymentExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FlightOrderPaymentExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_server_payment_type_fails_before_supplier_http(): void
    {
        $this->configureDuffel();

        config()->set(
            'flight_payments.duffel.type',
            null,
        );

        Http::fake();

        $user =
            User::factory()
                ->create();

        $order =
            $this->createdOrder(
                $user->id,
                'off_noconfig1',
                'ord_noconfig1',
            );

        $result =
            $this->service()
                ->execute(
                    $user->id,
                    $order['reference'],
                );

        $this->assertNull($result);

        Http::assertNothingSent();

        $this->assertSame(
            0,
            FlightOrderPaymentAttempt::query()
                ->count(),
        );
    }

    public function test_executes_one_balance_payment_using_latest_supplier_price(): void
    {
        $this->configureDuffel();

        config()->set(
            'flight_payments.duffel.type',
            'balance',
        );

        $user =
            User::factory()
                ->create();

        $order =
            $this->createdOrder(
                $user->id,
                'off_execpayment1',
                'ord_execpayment1',
            );

        Http::fake([
            'https://api.duffel.test/air/orders/ord_execpayment1' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_execpayment1',

                        'total_amount' =>
                            '91.50',

                        'total_currency' =>
                            'GBP',

                        'payment_status' => [
                            'awaiting_payment' =>
                                true,

                            'payment_required_by' =>
                                now()
                                    ->addHours(4)
                                    ->toIso8601String(),
                        ],
                    ],
                ], 200),

            'https://api.duffel.test/air/payments' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'pay_execpayment1',

                        'order_id' =>
                            'ord_execpayment1',

                        'type' =>
                            'balance',

                        'amount' =>
                            '91.50',

                        'currency' =>
                            'GBP',

                        'status' =>
                            'succeeded',
                    ],
                ], 201),
        ]);

        $result =
            $this->service()
                ->execute(
                    $user->id,
                    $order['reference'],
                );

        $this->assertIsArray($result);

        $this->assertSame(
            'succeeded',
            $result['status'],
        );

        $this->assertSame(
            'duffel',
            $result['provider'],
        );

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]{64}$/',
            $result['attempt_reference'],
        );

        $paymentAttempt =
            FlightOrderPaymentAttempt::query()
                ->sole();

        $this->assertSame(
            FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
            $paymentAttempt->status,
        );

        $this->assertSame(
            '91.50',
            $paymentAttempt->amount,
        );

        $this->assertSame(
            'GBP',
            $paymentAttempt->currency,
        );

        $this->assertSame(
            'balance',
            $paymentAttempt->payment_type,
        );

        $this->assertSame(
            'pay_execpayment1',
            $paymentAttempt->supplier_payment_id,
        );

        Http::assertSentCount(2);

        Http::assertSent(
            function (Request $request): bool {
                if (
                    $request->method() !== 'POST'
                    || $request->url()
                        !== 'https://api.duffel.test/air/payments'
                ) {
                    return false;
                }

                return data_get(
                    $request->data(),
                    'data.order_id',
                ) === 'ord_execpayment1'
                    && data_get(
                        $request->data(),
                        'data.payment.type',
                    ) === 'balance'
                    && data_get(
                        $request->data(),
                        'data.payment.amount',
                    ) === '91.50'
                    && data_get(
                        $request->data(),
                        'data.payment.currency',
                    ) === 'GBP';
            },
        );
    }

    public function test_ambiguous_supplier_500_persists_processing_and_replay_does_not_post_again(): void
    {
        $this->configureDuffel();

        config()->set(
            'flight_payments.duffel.type',
            'balance',
        );

        $user =
            User::factory()
                ->create();

        $order =
            $this->createdOrder(
                $user->id,
                'off_ambiguouspay1',
                'ord_ambiguouspay1',
            );

        Http::fake([
            'https://api.duffel.test/air/orders/ord_ambiguouspay1' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_ambiguouspay1',

                        'total_amount' =>
                            '120.00',

                        'total_currency' =>
                            'USD',

                        'payment_status' => [
                            'awaiting_payment' =>
                                true,

                            'payment_required_by' =>
                                now()
                                    ->addHours(4)
                                    ->toIso8601String(),
                        ],
                    ],
                ], 200),

            'https://api.duffel.test/air/payments' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'message' =>
                                    'ambiguous',
                            ],
                        ],
                    ],
                    500,
                ),
        ]);

        $first =
            $this->service()
                ->execute(
                    $user->id,
                    $order['reference'],
                );

        $this->assertIsArray($first);

        $this->assertSame(
            'processing',
            $first['status'],
        );

        $this->assertSame(
            FlightOrderPaymentAttempt::STATUS_PROCESSING,
            FlightOrderPaymentAttempt::query()
                ->sole()
                ->status,
        );

        Http::assertSentCount(2);

        /*
         * Reset the fake recorder. The replay must now fail closed before
         * any supplier GET or POST because the durable payment attempt exists.
         */
        Http::fake();

        $second =
            $this->service()
                ->execute(
                    $user->id,
                    $order['reference'],
                );

        $this->assertNull($second);

        Http::assertNothingSent();

        $this->assertSame(
            1,
            FlightOrderPaymentAttempt::query()
                ->count(),
        );
    }

    public function test_processing_order_reference_never_reaches_payment_supplier(): void
    {
        $this->configureDuffel();

        config()->set(
            'flight_payments.duffel.type',
            'balance',
        );

        Http::fake();

        $user =
            User::factory()
                ->create();

        $store =
            app(
                FlightOrderAttemptRecordStore::class,
            );

        $order =
            $store->createProcessing(
                $user->id,
                'duffel',
                'off_processingpay1',
            );

        $this->assertIsArray($order);

        $result =
            $this->service()
                ->execute(
                    $user->id,
                    $order['reference'],
                );

        $this->assertNull($result);

        Http::assertNothingSent();
    }

    public function test_cross_user_reference_never_reaches_supplier(): void
    {
        $this->configureDuffel();

        config()->set(
            'flight_payments.duffel.type',
            'balance',
        );

        Http::fake();

        $owner =
            User::factory()
                ->create();

        $other =
            User::factory()
                ->create();

        $order =
            $this->createdOrder(
                $owner->id,
                'off_crosspayexec1',
                'ord_crosspayexec1',
            );

        $result =
            $this->service()
                ->execute(
                    $other->id,
                    $order['reference'],
                );

        $this->assertNull($result);

        Http::assertNothingSent();
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

    /**
     * @return array{
     *     reference: string
     * }
     */
    private function createdOrder(
        int $userId,
        string $supplierOfferId,
        string $supplierOrderId,
    ): array {
        $store =
            app(
                FlightOrderAttemptRecordStore::class,
            );

        $processing =
            $store->createProcessing(
                $userId,
                'duffel',
                $supplierOfferId,
            );

        $this->assertIsArray($processing);

        $created =
            $store->markCreated(
                'duffel',
                $supplierOfferId,
                $supplierOrderId,
            );

        $this->assertNotNull($created);

        return [
            'reference' =>
                $processing['reference'],
        ];
    }

    private function service(): FlightOrderPaymentExecutionService
    {
        return app(
            FlightOrderPaymentExecutionService::class,
        );
    }
}