<?php

namespace App\Services\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\FlightOrderPaymentAttempt;

final class FlightOrderPaymentReconciliationService
{
    public function __construct(
        private readonly FlightOrderPaymentAttemptRecordStore $paymentAttemptStore,
        private readonly DuffelFlightOrderPaymentReconciliationProvider $duffelProvider,
    ) {
    }

    /**
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     provider: string
     * }|null
     */
    public function reconcile(
        int $userId,
        string $paymentAttemptReference,
    ): ?array {
        $paymentAttempt =
            $this->paymentAttemptStore
                ->findForUser(
                    $userId,
                    $paymentAttemptReference,
                );

        if (
            ! $paymentAttempt
                instanceof FlightOrderPaymentAttempt
        ) {
            return null;
        }

        $local =
            $this->localResult(
                $paymentAttempt,
            );

        if (
            $paymentAttempt->status !==
            FlightOrderPaymentAttempt::STATUS_PROCESSING
        ) {
            return $local;
        }

        $provider =
            is_string(
                $paymentAttempt->provider,
            )
                ? strtolower(
                    trim(
                        $paymentAttempt->provider,
                    ),
                )
                : '';

        if ($provider !== 'duffel') {
            return null;
        }

        $orderAttempt =
            $paymentAttempt
                ->flightOrderAttempt()
                ->first();

        if (
            ! $orderAttempt
                instanceof FlightOrderAttempt
            || $orderAttempt->status !==
                FlightOrderAttempt::STATUS_CREATED
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

        $amount =
            is_string(
                $paymentAttempt->amount,
            )
                ? trim(
                    $paymentAttempt->amount,
                )
                : '';

        $currency =
            is_string(
                $paymentAttempt->currency,
            )
                ? strtoupper(
                    trim(
                        $paymentAttempt->currency,
                    ),
                )
                : '';

        $paymentType =
            is_string(
                $paymentAttempt->payment_type,
            )
                ? trim(
                    $paymentAttempt->payment_type,
                )
                : '';

        $supplierPaymentId =
            is_string(
                $paymentAttempt->supplier_payment_id,
            )
                ? trim(
                    $paymentAttempt->supplier_payment_id,
                )
                : null;

        if ($supplierPaymentId === '') {
            $supplierPaymentId =
                null;
        }

        $supplierResult =
            $this->duffelProvider
                ->reconcilePayment(
                    $supplierOrderId,
                    $supplierPaymentId,
                    $amount,
                    $currency,
                    $paymentType,
                );

        $supplierStatus =
            $supplierResult['status']
                ?? null;

        $resolvedPaymentId =
            $supplierResult[
                'supplier_payment_id'
            ]
                ?? null;

        if (
            $supplierStatus === 'processing'
        ) {
            if (is_string($resolvedPaymentId)) {
                $this->paymentAttemptStore
                    ->markProcessingWithSupplierPayment(
                        'duffel',
                        $supplierOrderId,
                        $resolvedPaymentId,
                    );
            }

            return $this->rereadResult(
                $userId,
                $paymentAttemptReference,
            );
        }

        if (
            ! is_string($resolvedPaymentId)
        ) {
            return null;
        }

        if (
            $supplierStatus === 'succeeded'
        ) {
            $this->paymentAttemptStore
                ->markSucceeded(
                    'duffel',
                    $supplierOrderId,
                    $resolvedPaymentId,
                );

            return $this->rereadResult(
                $userId,
                $paymentAttemptReference,
            );
        }

        if (
            $supplierStatus === 'failed'
        ) {
            $this->paymentAttemptStore
                ->markFailed(
                    'duffel',
                    $supplierOrderId,
                    $resolvedPaymentId,
                );

            return $this->rereadResult(
                $userId,
                $paymentAttemptReference,
            );
        }

        return null;
    }

    /**
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     provider: string
     * }|null
     */
    private function rereadResult(
        int $userId,
        string $paymentAttemptReference,
    ): ?array {
        $latest =
            $this->paymentAttemptStore
                ->findForUser(
                    $userId,
                    $paymentAttemptReference,
                );

        return $latest instanceof FlightOrderPaymentAttempt
            ? $this->localResult(
                $latest,
            )
            : null;
    }

    /**
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     provider: string
     * }
     */
    private function localResult(
        FlightOrderPaymentAttempt $attempt,
    ): array {
        return [
            'status' =>
                $attempt->status,

            'provider' =>
                (string) $attempt->provider,
        ];
    }
}