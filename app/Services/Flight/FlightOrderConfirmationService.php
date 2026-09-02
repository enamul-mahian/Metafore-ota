<?php

namespace App\Services\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\FlightOrderPaymentAttempt;

final class FlightOrderConfirmationService
{
    public function __construct(
        private readonly FlightOrderAttemptRecordStore $orderAttemptStore,
        private readonly FlightOrderPaymentAttemptRecordStore $paymentAttemptStore,
        private readonly DuffelFlightOrderConfirmationProvider $provider,
    ) {
    }

    /**
     * @return array{
     *     status: 'confirmed',
     *     provider: 'duffel',
     *     booking_reference: string
     * }|null
     */
    public function retrieve(
        int $userId,
        string $orderAttemptReference,
    ): ?array {
        $orderAttempt =
            $this->orderAttemptStore
                ->findForUser(
                    $userId,
                    $orderAttemptReference,
                );

        if (
            ! $orderAttempt instanceof FlightOrderAttempt
            || $orderAttempt->status !==
                FlightOrderAttempt::STATUS_CREATED
        ) {
            return null;
        }

        $orderProvider =
            is_string($orderAttempt->provider)
                ? strtolower(
                    trim($orderAttempt->provider),
                )
                : '';

        if ($orderProvider !== 'duffel') {
            return null;
        }

        $orderAttemptId =
            (int) $orderAttempt->getKey();

        if ($orderAttemptId < 1) {
            return null;
        }

        /*
         * Payment attempts do not persist supplier_order_id as a column.
         *
         * Their durable binding is:
         * authenticated user + flight_order_attempt_id.
         *
         * The payment identity hash already protects the supplier
         * provider/order identity inside the payment store boundary.
         */
        $paymentAttempt =
            $this->paymentAttemptStore
                ->findByOrderAttemptForUser(
                    $userId,
                    $orderAttemptId,
                );

        if (
            ! $paymentAttempt
                instanceof FlightOrderPaymentAttempt
            || $paymentAttempt->status !==
                FlightOrderPaymentAttempt::STATUS_SUCCEEDED
        ) {
            return null;
        }

        $paymentProvider =
            is_string($paymentAttempt->provider)
                ? strtolower(
                    trim($paymentAttempt->provider),
                )
                : '';

        if ($paymentProvider !== 'duffel') {
            return null;
        }

        $supplierOrderId =
            is_string(
                $orderAttempt->supplier_order_id,
            )
                ? trim(
                    $orderAttempt->supplier_order_id,
                )
                : '';

        if ($supplierOrderId === '') {
            return null;
        }

        return $this->provider
            ->retrieve(
                $supplierOrderId,
            );
    }
}