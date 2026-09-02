<?php

namespace App\Services\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\FlightOrderPaymentAttempt;
use Illuminate\Support\Str;

final class FlightOrderPaymentAttemptRecordStore
{
    /**
     * @return array{
     *     reference: string,
     *     attempt: FlightOrderPaymentAttempt
     * }|null
     */
    public function createProcessing(
        int $userId,
        int $flightOrderAttemptId,
        string $provider,
        string $supplierOrderId,
        string $paymentType,
        string $amount,
        string $currency,
    ): ?array {
        $provider =
            $this->normalizeProvider(
                $provider,
            );

        $supplierOrderId =
            $this->normalizeSupplierOrderId(
                $supplierOrderId,
            );

        $paymentType =
            $this->normalizePaymentType(
                $paymentType,
            );

        $amount =
            $this->normalizeAmount(
                $amount,
            );

        $currency =
            $this->normalizeCurrency(
                $currency,
            );

        if (
            $userId < 1
            || $flightOrderAttemptId < 1
            || $provider === null
            || $supplierOrderId === null
            || $paymentType === null
            || $amount === null
            || $currency === null
        ) {
            return null;
        }

        $orderAttempt =
            FlightOrderAttempt::query()
                ->whereKey(
                    $flightOrderAttemptId,
                )
                ->where(
                    'user_id',
                    $userId,
                )
                ->where(
                    'status',
                    FlightOrderAttempt::STATUS_CREATED,
                )
                ->where(
                    'provider',
                    $provider,
                )
                ->where(
                    'supplier_order_id',
                    $supplierOrderId,
                )
                ->first();

        if (! $orderAttempt instanceof FlightOrderAttempt) {
            return null;
        }

        $reference =
            Str::random(
                64,
            );

        $referenceHash =
            hash(
                'sha256',
                $reference,
            );

        $paymentIdentityHash =
            $this->paymentIdentityHash(
                $provider,
                $supplierOrderId,
            );

        $now =
            now();

        $inserted =
            FlightOrderPaymentAttempt::query()
                ->insertOrIgnore([
                    'user_id' =>
                        $userId,

                    'flight_order_attempt_id' =>
                        $flightOrderAttemptId,

                    'reference_hash' =>
                        $referenceHash,

                    'payment_identity_hash' =>
                        $paymentIdentityHash,

                    'provider' =>
                        $provider,

                    'payment_type' =>
                        $paymentType,

                    'amount' =>
                        $amount,

                    'currency' =>
                        $currency,

                    'status' =>
                        FlightOrderPaymentAttempt::STATUS_PROCESSING,

                    'supplier_payment_id' =>
                        null,

                    'resolved_at' =>
                        null,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]);

        if ($inserted !== 1) {
            return null;
        }

        $attempt =
            FlightOrderPaymentAttempt::query()
                ->where(
                    'reference_hash',
                    $referenceHash,
                )
                ->first();

        if (! $attempt instanceof FlightOrderPaymentAttempt) {
            return null;
        }

        return [
            'reference' =>
                $reference,

            'attempt' =>
                $attempt,
        ];
    }

    public function findForUser(
        int $userId,
        string $reference,
    ): ?FlightOrderPaymentAttempt {
        if (
            $userId < 1
            || ! $this->isReference(
                $reference,
            )
        ) {
            return null;
        }

        return FlightOrderPaymentAttempt::query()
            ->where(
                'user_id',
                $userId,
            )
            ->where(
                'reference_hash',
                hash(
                    'sha256',
                    $reference,
                ),
            )
            ->first();
    }

    public function findByOrderAttemptForUser(
        int $userId,
        int $flightOrderAttemptId,
    ): ?FlightOrderPaymentAttempt {
        if (
            $userId < 1
            || $flightOrderAttemptId < 1
        ) {
            return null;
        }

        return FlightOrderPaymentAttempt::query()
            ->where(
                'user_id',
                $userId,
            )
            ->where(
                'flight_order_attempt_id',
                $flightOrderAttemptId,
            )
            ->first();
    }

    public function markProcessingWithSupplierPayment(
        string $provider,
        string $supplierOrderId,
        string $supplierPaymentId,
    ): ?FlightOrderPaymentAttempt {
        $attempt =
            $this->findBySupplierIdentity(
                $provider,
                $supplierOrderId,
            );

        $supplierPaymentId =
            $this->normalizeSupplierPaymentId(
                $supplierPaymentId,
            );

        if (
            ! $attempt instanceof FlightOrderPaymentAttempt
            || $supplierPaymentId === null
            || $attempt->status !==
                FlightOrderPaymentAttempt::STATUS_PROCESSING
        ) {
            return null;
        }

        $existing =
            is_string(
                $attempt->supplier_payment_id,
            )
                ? trim(
                    $attempt->supplier_payment_id,
                )
                : '';

        if ($existing !== '') {
            return hash_equals(
                $existing,
                $supplierPaymentId,
            )
                ? $attempt
                : null;
        }

        FlightOrderPaymentAttempt::query()
            ->whereKey(
                $attempt->getKey(),
            )
            ->where(
                'status',
                FlightOrderPaymentAttempt::STATUS_PROCESSING,
            )
            ->whereNull(
                'supplier_payment_id',
            )
            ->update([
                'supplier_payment_id' =>
                    $supplierPaymentId,

                'updated_at' =>
                    now(),
            ]);

        return $this->findBySupplierIdentity(
            $provider,
            $supplierOrderId,
        );
    }

    public function markSucceeded(
        string $provider,
        string $supplierOrderId,
        string $supplierPaymentId,
    ): ?FlightOrderPaymentAttempt {
        return $this->markTerminal(
            $provider,
            $supplierOrderId,
            $supplierPaymentId,
            FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
        );
    }

    public function markFailed(
        string $provider,
        string $supplierOrderId,
        ?string $supplierPaymentId,
    ): ?FlightOrderPaymentAttempt {
        return $this->markTerminal(
            $provider,
            $supplierOrderId,
            $supplierPaymentId,
            FlightOrderPaymentAttempt::STATUS_FAILED,
        );
    }

    private function markTerminal(
        string $provider,
        string $supplierOrderId,
        ?string $supplierPaymentId,
        string $status,
    ): ?FlightOrderPaymentAttempt {
        $provider =
            $this->normalizeProvider(
                $provider,
            );

        $supplierOrderId =
            $this->normalizeSupplierOrderId(
                $supplierOrderId,
            );

        if (
            $provider === null
            || $supplierOrderId === null
            || ! in_array(
                $status,
                [
                    FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
                    FlightOrderPaymentAttempt::STATUS_FAILED,
                ],
                true,
            )
        ) {
            return null;
        }

        if ($supplierPaymentId !== null) {
            $supplierPaymentId =
                $this->normalizeSupplierPaymentId(
                    $supplierPaymentId,
                );

            if ($supplierPaymentId === null) {
                return null;
            }
        }

        $identityHash =
            $this->paymentIdentityHash(
                $provider,
                $supplierOrderId,
            );

        $values = [
            'status' =>
                $status,

            'resolved_at' =>
                now(),

            'updated_at' =>
                now(),
        ];

        if ($supplierPaymentId !== null) {
            $values['supplier_payment_id'] =
                $supplierPaymentId;
        }

        FlightOrderPaymentAttempt::query()
            ->where(
                'payment_identity_hash',
                $identityHash,
            )
            ->where(
                'status',
                FlightOrderPaymentAttempt::STATUS_PROCESSING,
            )
            ->update(
                $values,
            );

        return FlightOrderPaymentAttempt::query()
            ->where(
                'payment_identity_hash',
                $identityHash,
            )
            ->first();
    }

    private function findBySupplierIdentity(
        string $provider,
        string $supplierOrderId,
    ): ?FlightOrderPaymentAttempt {
        $provider =
            $this->normalizeProvider(
                $provider,
            );

        $supplierOrderId =
            $this->normalizeSupplierOrderId(
                $supplierOrderId,
            );

        if (
            $provider === null
            || $supplierOrderId === null
        ) {
            return null;
        }

        return FlightOrderPaymentAttempt::query()
            ->where(
                'payment_identity_hash',
                $this->paymentIdentityHash(
                    $provider,
                    $supplierOrderId,
                ),
            )
            ->first();
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

        return preg_match(
            '/^[a-z0-9_-]{1,64}$/',
            $value,
        ) === 1
            ? $value
            : null;
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

        return strlen($value) <= 255
            && str_starts_with(
                $value,
                'ord_',
            )
            && preg_match(
                '/^[A-Za-z0-9_]+$/',
                $value,
            ) === 1
                ? $value
                : null;
    }

    private function normalizeSupplierPaymentId(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return strlen($value) <= 255
            && str_starts_with(
                $value,
                'pay_',
            )
            && preg_match(
                '/^[A-Za-z0-9_]+$/',
                $value,
            ) === 1
                ? $value
                : null;
    }

    private function normalizePaymentType(
        mixed $value,
    ): ?string {
        return is_string($value)
            && trim($value) === 'balance'
                ? 'balance'
                : null;
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

        return strlen($value) <= 32
            && preg_match(
                '/^[0-9]+(?:\.[0-9]+)?$/',
                $value,
            ) === 1
                ? $value
                : null;
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

        return preg_match(
            '/^[A-Z]{3}$/',
            $value,
        ) === 1
            ? $value
            : null;
    }

    private function isReference(
        string $reference,
    ): bool {
        return strlen($reference) === 64
            && preg_match(
                '/^[A-Za-z0-9]+$/',
                $reference,
            ) === 1;
    }

    private function paymentIdentityHash(
        string $provider,
        string $supplierOrderId,
    ): string {
        return hash(
            'sha256',
            $provider
                . "\0"
                . $supplierOrderId,
        );
    }
}