<?php

namespace App\Services\Flight;

use App\Models\FlightOrderAttempt;
use DateTimeImmutable;

final class FlightOrderPaymentReadinessService
{
    public function __construct(
        private readonly FlightOrderAttemptRecordStore $attemptStore,
        private readonly DuffelFlightOrderPaymentReadinessProvider $duffelProvider,
    ) {
    }

    /**
     * Read current payment readiness for one user-owned created hold order.
     *
     * The caller supplies only authenticated user identity plus the opaque
     * local attempt reference. Provider and supplier order identity are
     * resolved entirely from durable server-side state.
     *
     * This service does not mutate the order attempt and never creates a
     * supplier payment or ticket.
     *
     * @return array{
     *     status: 'ready_for_payment'|'not_ready_for_payment',
     *     provider: string,
     *     awaiting_payment: bool,
     *     total_amount: string,
     *     total_currency: string,
     *     payment_required_by: string|null
     * }|null
     */
    public function read(
        int $userId,
        string $attemptReference,
    ): ?array {
        $attempt =
            $this->attemptStore
                ->findForUser(
                    $userId,
                    $attemptReference,
                );

        if (! $attempt instanceof FlightOrderAttempt) {
            return null;
        }

        $status =
            is_string(
                $attempt->status,
            )
                ? trim(
                    $attempt->status,
                )
                : '';

        if (
            $status !==
                FlightOrderAttempt::STATUS_CREATED
        ) {
            return null;
        }

        $provider =
            $this->normalizeProvider(
                $attempt->provider,
            );

        if ($provider !== 'duffel') {
            return null;
        }

        $supplierOrderId =
            $this->normalizeSupplierOrderId(
                $attempt->supplier_order_id,
            );

        if ($supplierOrderId === null) {
            return null;
        }

        $supplierResult =
            $this->duffelProvider
                ->readPaymentReadiness(
                    $supplierOrderId,
                );

        $totalAmount =
            $this->normalizeAmount(
                $supplierResult[
                    'total_amount'
                ]
                    ?? null,
            );

        $totalCurrency =
            $this->normalizeCurrency(
                $supplierResult[
                    'total_currency'
                ]
                    ?? null,
            );

        $awaitingPayment =
            $supplierResult[
                'awaiting_payment'
            ]
                ?? null;

        $paymentRequiredBy =
            $this->normalizePaymentRequiredBy(
                $supplierResult[
                    'payment_required_by'
                ]
                    ?? null,
            );

        if (
            $totalAmount === null
            || $totalCurrency === null
            || ! is_bool($awaitingPayment)
        ) {
            return null;
        }

        $readyForPayment =
            $awaitingPayment
            && $this->isFutureDeadline(
                $paymentRequiredBy,
            );

        return [
            'status' =>
                $readyForPayment
                    ? 'ready_for_payment'
                    : 'not_ready_for_payment',

            'provider' =>
                $provider,

            'awaiting_payment' =>
                $awaitingPayment,

            'total_amount' =>
                $totalAmount,

            'total_currency' =>
                $totalCurrency,

            'payment_required_by' =>
                $paymentRequiredBy,
        ];
    }

    private function normalizeProvider(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            strtolower(
                trim(
                    $value,
                ),
            );

        if (
            $value === ''
            || strlen($value) > 64
            || preg_match(
                '/^[a-z0-9_-]+$/',
                $value,
            ) !== 1
        ) {
            return null;
        }

        return $value;
    }

    private function normalizeSupplierOrderId(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        if (
            $value === ''
            || strlen($value) > 255
            || ! str_starts_with(
                $value,
                'ord_',
            )
            || preg_match(
                '/^[A-Za-z0-9_]+$/',
                $value,
            ) !== 1
        ) {
            return null;
        }

        return $value;
    }

    private function normalizeAmount(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        if (
            $value === ''
            || strlen($value) > 32
            || preg_match(
                '/^[0-9]+(?:\.[0-9]+)?$/',
                $value,
            ) !== 1
        ) {
            return null;
        }

        return $value;
    }

    private function normalizeCurrency(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            strtoupper(
                trim(
                    $value,
                ),
            );

        if (
            preg_match(
                '/^[A-Z]{3}$/',
                $value,
            ) !== 1
        ) {
            return null;
        }

        return $value;
    }

    private function normalizePaymentRequiredBy(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        if (
            $value === ''
            || strlen($value) > 64
        ) {
            return null;
        }

        try {
            new DateTimeImmutable(
                $value,
            );
        } catch (\Throwable) {
            return null;
        }

        return $value;
    }

    private function isFutureDeadline(
        ?string $paymentRequiredBy,
    ): bool {
        if ($paymentRequiredBy === null) {
            return false;
        }

        try {
            $deadline =
                new DateTimeImmutable(
                    $paymentRequiredBy,
                );

            $now =
                new DateTimeImmutable(
                    'now',
                );
        } catch (\Throwable) {
            return false;
        }

        return $deadline > $now;
    }
}