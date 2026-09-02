<?php

namespace Tests\Feature\Flight;

use App\Models\FlightOrderPaymentAttempt;
use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use App\Services\Flight\FlightOrderPaymentAttemptRecordStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FlightOrderPaymentAttemptRecordStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_one_user_owned_processing_payment_attempt_per_created_order(): void
    {
        $user =
            User::factory()
                ->create();

        $order =
            $this->createdOrder(
                $user->id,
                'off_paystore1',
                'ord_paystore1',
            );

        $store =
            app(
                FlightOrderPaymentAttemptRecordStore::class,
            );

        $created =
            $store->createProcessing(
                $user->id,
                $order['id'],
                'duffel',
                'ord_paystore1',
                'balance',
                '88.40',
                'USD',
            );

        $this->assertIsArray($created);

        $reference =
            $created['reference'];

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]{64}$/',
            $reference,
        );

        $attempt =
            $store->findForUser(
                $user->id,
                $reference,
            );

        $this->assertInstanceOf(
            FlightOrderPaymentAttempt::class,
            $attempt,
        );

        $this->assertSame(
            FlightOrderPaymentAttempt::STATUS_PROCESSING,
            $attempt->status,
        );

        $this->assertNotSame(
            $reference,
            $attempt->reference_hash,
        );

        $duplicate =
            $store->createProcessing(
                $user->id,
                $order['id'],
                'duffel',
                'ord_paystore1',
                'balance',
                '88.40',
                'USD',
            );

        $this->assertNull($duplicate);

        $this->assertSame(
            1,
            FlightOrderPaymentAttempt::query()
                ->count(),
        );
    }

    public function test_cross_user_reference_fails_closed(): void
    {
        $owner =
            User::factory()
                ->create();

        $other =
            User::factory()
                ->create();

        $order =
            $this->createdOrder(
                $owner->id,
                'off_crosspayment1',
                'ord_crosspayment1',
            );

        $store =
            app(
                FlightOrderPaymentAttemptRecordStore::class,
            );

        $created =
            $store->createProcessing(
                $owner->id,
                $order['id'],
                'duffel',
                'ord_crosspayment1',
                'balance',
                '25.00',
                'GBP',
            );

        $this->assertIsArray($created);

        $this->assertNull(
            $store->findForUser(
                $other->id,
                $created['reference'],
            ),
        );
    }

    public function test_terminal_transition_is_processing_only(): void
    {
        $user =
            User::factory()
                ->create();

        $order =
            $this->createdOrder(
                $user->id,
                'off_terminalpay1',
                'ord_terminalpay1',
            );

        $store =
            app(
                FlightOrderPaymentAttemptRecordStore::class,
            );

        $created =
            $store->createProcessing(
                $user->id,
                $order['id'],
                'duffel',
                'ord_terminalpay1',
                'balance',
                '30.20',
                'GBP',
            );

        $this->assertIsArray($created);

        $succeeded =
            $store->markSucceeded(
                'duffel',
                'ord_terminalpay1',
                'pay_terminal1',
            );

        $this->assertSame(
            FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
            $succeeded?->status,
        );

        $afterFailedAttempt =
            $store->markFailed(
                'duffel',
                'ord_terminalpay1',
                'pay_terminal1',
            );

        $this->assertSame(
            FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
            $afterFailedAttempt?->status,
        );
    }

    /**
     * @return array{
     *     id: int,
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
            'id' =>
                (int) $created->getKey(),

            'reference' =>
                $processing['reference'],
        ];
    }
}