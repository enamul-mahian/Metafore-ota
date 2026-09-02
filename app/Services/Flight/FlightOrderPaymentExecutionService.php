<?php

namespace App\Services\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\FlightOrderPaymentAttempt;

final class FlightOrderPaymentExecutionService
{
    public function __construct(
        private readonly FlightOrderAttemptRecordStore $orderAttemptStore,
        private readonly FlightOrderPaymentAttemptRecordStore $paymentAttemptStore,
        private readonly FlightOrderPaymentReadinessService $readinessService,
        private readonly DuffelFlightOrderPaymentProvider $duffelProvider,
    ) {
    }

    /**
     * Execute at most one durable payment attempt for a created hold order.
     *
     * Browser authority is limited to the opaque order-attempt reference.
     * Supplier order ID, amount, currency and payment type are all derived
     * from server-side state/configuration.
     *
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     provider: 'duffel',
     *     attempt_reference: string
     * }|null
     */
    public function execute(
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

        $provider =
            is_string(
                $orderAttempt->provider,
            )
                ? strtolower(
                    trim(
                        $orderAttempt->provider,
                    ),
                )
                : '';

        if ($provider !== 'duffel') {
            return null;
        }

        $orderAttemptId =
            (int) $orderAttempt->getKey();

        /*
         * A durable payment attempt already exists for this order.
         *
         * Never issue another supplier POST from a replayed execution call.
         */
        if (
            $this->paymentAttemptStore
                ->findByOrderAttemptForUser(
                    $userId,
                    $orderAttemptId,
                )
            instanceof FlightOrderPaymentAttempt
        ) {
            return null;
        }

        $paymentType =
            config(
                'flight_payments.duffel.type',
            );

        if (
            ! is_string($paymentType)
            || trim($paymentType) !== 'balance'
        ) {
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

        if (
            $supplierOrderId === ''
            || strlen($supplierOrderId) > 255
            || ! str_starts_with(
                $supplierOrderId,
                'ord_',
            )
            || preg_match(
                '/^[A-Za-z0-9_]+$/',
                $supplierOrderId,
            ) !== 1
        ) {
            return null;
        }

        /*
         * Always retrieve current supplier order state immediately before
         * payment. Browser-provided amount/currency are never accepted.
         */
        $readiness =
            $this->readinessService
                ->read(
                    $userId,
                    $orderAttemptReference,
                );

        if (
            ! is_array($readiness)
            || ($readiness['status'] ?? null)
                !== 'ready_for_payment'
            || ($readiness['provider'] ?? null)
                !== 'duffel'
        ) {
            return null;
        }

        $amount =
            $readiness['total_amount']
                ?? null;

        $currency =
            $readiness['total_currency']
                ?? null;

        if (
            ! is_string($amount)
            || ! is_string($currency)
        ) {
            return null;
        }

        /*
         * Persist processing BEFORE supplier mutation.
         *
         * The unique order-attempt constraint is the local replay guard.
         */
        $processing =
            $this->paymentAttemptStore
                ->createProcessing(
                    $userId,
                    $orderAttemptId,
                    'duffel',
                    $supplierOrderId,
                    'balance',
                    $amount,
                    $currency,
                );

        if (! is_array($processing)) {
            return null;
        }

        $paymentReference =
            $processing['reference']
                ?? null;

        if (! is_string($paymentReference)) {
            return null;
        }

        /*
         * Exactly one provider call. The Duffel provider has no retry path.
         */
        $supplierResult =
            $this->duffelProvider
                ->createPayment(
                    $supplierOrderId,
                    $amount,
                    $currency,
                    'balance',
                );

        $status =
            $supplierResult['status']
                ?? null;

        $supplierPaymentId =
            $supplierResult[
                'supplier_payment_id'
            ]
                ?? null;

        if ($status === 'succeeded') {
            if (! is_string($supplierPaymentId)) {
                return $this->processingResult(
                    $paymentReference,
                );
            }

            $resolved =
                $this->paymentAttemptStore
                    ->markSucceeded(
                        'duffel',
                        $supplierOrderId,
                        $supplierPaymentId,
                    );

            return [
                'status' =>
                    $resolved?->status ===
                        FlightOrderPaymentAttempt::STATUS_SUCCEEDED
                            ? 'succeeded'
                            : 'processing',

                'provider' =>
                    'duffel',

                'attempt_reference' =>
                    $paymentReference,
            ];
        }

        if ($status === 'failed') {
            $resolved =
                $this->paymentAttemptStore
                    ->markFailed(
                        'duffel',
                        $supplierOrderId,
                        is_string($supplierPaymentId)
                            ? $supplierPaymentId
                            : null,
                    );

            return [
                'status' =>
                    $resolved?->status ===
                        FlightOrderPaymentAttempt::STATUS_FAILED
                            ? 'failed'
                            : 'processing',

                'provider' =>
                    'duffel',

                'attempt_reference' =>
                    $paymentReference,
            ];
        }

        if (is_string($supplierPaymentId)) {
            $this->paymentAttemptStore
                ->markProcessingWithSupplierPayment(
                    'duffel',
                    $supplierOrderId,
                    $supplierPaymentId,
                );
        }

        return $this->processingResult(
            $paymentReference,
        );
    }

    /**
     * @return array{
     *     status: 'processing',
     *     provider: 'duffel',
     *     attempt_reference: string
     * }
     */
    private function processingResult(
        string $paymentReference,
    ): array {
        return [
            'status' =>
                'processing',

            'provider' =>
                'duffel',

            'attempt_reference' =>
                $paymentReference,
        ];
    }
}