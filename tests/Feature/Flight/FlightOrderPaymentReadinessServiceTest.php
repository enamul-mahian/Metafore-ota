<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use App\Services\Flight\FlightOrderPaymentReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FlightOrderPaymentReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_user_owned_duffel_attempt_reads_current_payment_readiness(): void
    {
        $this->configureDuffel();

        $user =
            User::factory()
                ->create();

        $attempt =
            $this->createdAttempt(
                $user->id,
                'duffel',
                'off_paymentready1',
                'ord_paymentready1',
            );

        Http::fake([
            'https://api.duffel.test/air/orders/ord_paymentready1' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_paymentready1',

                        'total_amount' =>
                            '88400.00',

                        'total_currency' =>
                            'BDT',

                        'payment_status' => [
                            'awaiting_payment' =>
                                true,

                            'payment_required_by' =>
                                now()
                                    ->addHours(8)
                                    ->toIso8601String(),
                        ],
                    ],
                ], 200),
        ]);

        $result =
            $this->service()
                ->read(
                    $user->id,
                    $attempt['reference'],
                );

        $this->assertIsArray($result);

        $this->assertSame(
            'ready_for_payment',
            $result['status'],
        );

        $this->assertSame(
            'duffel',
            $result['provider'],
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

        $this->assertArrayNotHasKey(
            'supplier_order_id',
            $result,
        );

        $this->assertArrayNotHasKey(
            'supplier_offer_id',
            $result,
        );

        Http::assertSentCount(1);
    }

    public function test_processing_attempt_fails_closed_before_supplier_http(): void
    {
        $this->configureDuffel();

        Http::fake();

        $user =
            User::factory()
                ->create();

        $store =
            app(
                FlightOrderAttemptRecordStore::class,
            );

        $attempt =
            $store->createProcessing(
                $user->id,
                'duffel',
                'off_stillprocessing1',
            );

        $this->assertIsArray($attempt);

        $result =
            $this->service()
                ->read(
                    $user->id,
                    $attempt['reference'],
                );

        $this->assertNull($result);

        Http::assertNothingSent();
    }

    public function test_cross_user_reference_fails_closed_before_supplier_http(): void
    {
        $this->configureDuffel();

        Http::fake();

        $owner =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $attempt =
            $this->createdAttempt(
                $owner->id,
                'duffel',
                'off_crossuserready1',
                'ord_crossuserready1',
            );

        $result =
            $this->service()
                ->read(
                    $otherUser->id,
                    $attempt['reference'],
                );

        $this->assertNull($result);

        Http::assertNothingSent();
    }

    public function test_unknown_reference_fails_closed_before_supplier_http(): void
    {
        $this->configureDuffel();

        Http::fake();

        $user =
            User::factory()
                ->create();

        $result =
            $this->service()
                ->read(
                    $user->id,
                    str_repeat(
                        'A',
                        64,
                    ),
                );

        $this->assertNull($result);

        Http::assertNothingSent();
    }

    public function test_unsupported_provider_fails_closed_before_supplier_http(): void
    {
        $this->configureDuffel();

        Http::fake();

        $user =
            User::factory()
                ->create();

        $attempt =
            $this->createdAttempt(
                $user->id,
                'fixture',
                'off_fixturepayment1',
                'ord_fixturepayment1',
            );

        $result =
            $this->service()
                ->read(
                    $user->id,
                    $attempt['reference'],
                );

        $this->assertNull($result);

        Http::assertNothingSent();
    }

    public function test_expired_deadline_is_not_payment_ready(): void
    {
        $this->configureDuffel();

        $user =
            User::factory()
                ->create();

        $attempt =
            $this->createdAttempt(
                $user->id,
                'duffel',
                'off_expiredpayment1',
                'ord_expiredpayment1',
            );

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_expiredpayment1',

                        'total_amount' =>
                            '88400.00',

                        'total_currency' =>
                            'BDT',

                        'payment_status' => [
                            'awaiting_payment' =>
                                true,

                            'payment_required_by' =>
                                now()
                                    ->subMinute()
                                    ->toIso8601String(),
                        ],
                    ],
                ], 200),
        ]);

        $result =
            $this->service()
                ->read(
                    $user->id,
                    $attempt['reference'],
                );

        $this->assertIsArray($result);

        $this->assertSame(
            'not_ready_for_payment',
            $result['status'],
        );

        Http::assertSentCount(1);
    }

    public function test_already_not_awaiting_payment_is_not_payment_ready(): void
    {
        $this->configureDuffel();

        $user =
            User::factory()
                ->create();

        $attempt =
            $this->createdAttempt(
                $user->id,
                'duffel',
                'off_alreadypaid1',
                'ord_alreadypaid1',
            );

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_alreadypaid1',

                        'total_amount' =>
                            '88400.00',

                        'total_currency' =>
                            'BDT',

                        'payment_status' => [
                            'awaiting_payment' =>
                                false,

                            'payment_required_by' =>
                                now()
                                    ->addHours(2)
                                    ->toIso8601String(),
                        ],
                    ],
                ], 200),
        ]);

        $result =
            $this->service()
                ->read(
                    $user->id,
                    $attempt['reference'],
                );

        $this->assertIsArray($result);

        $this->assertSame(
            'not_ready_for_payment',
            $result['status'],
        );

        $this->assertFalse(
            $result['awaiting_payment'],
        );
    }

    public function test_service_source_has_no_supplier_payment_or_ticket_mutation(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightOrderPaymentReadinessService.php',
                ),
            );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'findForUser(',
            $source,
        );

        $this->assertStringContainsString(
            'supplier_order_id',
            $source,
        );

        $this->assertStringNotContainsString(
            '/air/payments',
            $source,
        );

        $this->assertStringNotContainsString(
            '->post(',
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
            'ticket',
            strtolower(
                str_replace(
                    'ticket.',
                    '',
                    $source,
                ),
            ),
        );
    }

    /**
     * @return array{
     *     reference: string
     * }
     */
    private function createdAttempt(
        int $userId,
        string $provider,
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
                $provider,
                $supplierOfferId,
            );

        $this->assertIsArray(
            $processing,
        );

        $created =
            $store->markCreated(
                $provider,
                $supplierOfferId,
                $supplierOrderId,
            );

        $this->assertNotNull(
            $created,
        );

        return [
            'reference' =>
                $processing['reference'],
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

    private function service(): FlightOrderPaymentReadinessService
    {
        return app(
            FlightOrderPaymentReadinessService::class,
        );
    }
}