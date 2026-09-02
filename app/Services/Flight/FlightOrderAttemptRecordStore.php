<?php

namespace App\Services\Flight;

use App\Models\FlightOrderAttempt;
use Illuminate\Support\Str;

final class FlightOrderAttemptRecordStore
{
    /**
     * @return array{
     *     reference: string,
     *     attempt: FlightOrderAttempt
     * }|null
     */
    public function createProcessing(
        int $userId,
        string $provider,
        string $supplierOfferId,
    ): ?array {
        if ($userId < 1) {
            return null;
        }

        $provider =
            $this->normalizeProvider(
                $provider,
            );

        $supplierOfferId =
            $this->normalizeSupplierOfferId(
                $supplierOfferId,
            );

        if (
            $provider === null
            || $supplierOfferId === null
        ) {
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

        $attemptIdentityHash =
            $this->attemptIdentityHash(
                $provider,
                $supplierOfferId,
            );

        $now =
            now();

        /*
         * Database uniqueness makes creation race-safe:
         *
         * - reference_hash uniquely identifies the opaque reference digest.
         * - attempt_identity_hash permits only one durable record for the
         *   trusted provider + supplier-offer attempt.
         *
         * An existing supplier attempt never receives a replacement
         * client reference.
         */
        $inserted =
            FlightOrderAttempt::query()
                ->insertOrIgnore([
                    'user_id' =>
                        $userId,

                    'reference_hash' =>
                        $referenceHash,

                    'attempt_identity_hash' =>
                        $attemptIdentityHash,

                    'provider' =>
                        $provider,

                    'supplier_offer_id' =>
                        $supplierOfferId,

                    'status' =>
                        FlightOrderAttempt::STATUS_PROCESSING,

                    'supplier_order_id' =>
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
            FlightOrderAttempt::query()
                ->where(
                    'reference_hash',
                    $referenceHash,
                )
                ->first();

        if (! $attempt instanceof FlightOrderAttempt) {
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
    ): ?FlightOrderAttempt {
        if (
            $userId < 1
            || ! $this->isReference(
                $reference,
            )
        ) {
            return null;
        }

        return FlightOrderAttempt::query()
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

    public function findByProviderAndOffer(
        string $provider,
        string $supplierOfferId,
    ): ?FlightOrderAttempt {
        $provider =
            $this->normalizeProvider(
                $provider,
            );

        $supplierOfferId =
            $this->normalizeSupplierOfferId(
                $supplierOfferId,
            );

        if (
            $provider === null
            || $supplierOfferId === null
        ) {
            return null;
        }

        return FlightOrderAttempt::query()
            ->where(
                'attempt_identity_hash',
                $this->attemptIdentityHash(
                    $provider,
                    $supplierOfferId,
                ),
            )
            ->first();
    }

    public function markCreated(
        string $provider,
        string $supplierOfferId,
        string $supplierOrderId,
    ): ?FlightOrderAttempt {
        $provider =
            $this->normalizeProvider(
                $provider,
            );

        $supplierOfferId =
            $this->normalizeSupplierOfferId(
                $supplierOfferId,
            );

        $supplierOrderId =
            $this->normalizeSupplierOrderId(
                $supplierOrderId,
            );

        if (
            $provider === null
            || $supplierOfferId === null
            || $supplierOrderId === null
        ) {
            return null;
        }

        $attemptIdentityHash =
            $this->attemptIdentityHash(
                $provider,
                $supplierOfferId,
            );

        $updated =
            FlightOrderAttempt::query()
                ->where(
                    'attempt_identity_hash',
                    $attemptIdentityHash,
                )
                ->where(
                    'status',
                    FlightOrderAttempt::STATUS_PROCESSING,
                )
                ->update([
                    'status' =>
                        FlightOrderAttempt::STATUS_CREATED,

                    'supplier_order_id' =>
                        $supplierOrderId,

                    'resolved_at' =>
                        now(),
                ]);

        if ($updated !== 1) {
            return null;
        }

        return FlightOrderAttempt::query()
            ->where(
                'attempt_identity_hash',
                $attemptIdentityHash,
            )
            ->first();
    }

    public function markFailed(
        string $provider,
        string $supplierOfferId,
    ): ?FlightOrderAttempt {
        $provider =
            $this->normalizeProvider(
                $provider,
            );

        $supplierOfferId =
            $this->normalizeSupplierOfferId(
                $supplierOfferId,
            );

        if (
            $provider === null
            || $supplierOfferId === null
        ) {
            return null;
        }

        $attemptIdentityHash =
            $this->attemptIdentityHash(
                $provider,
                $supplierOfferId,
            );

        $updated =
            FlightOrderAttempt::query()
                ->where(
                    'attempt_identity_hash',
                    $attemptIdentityHash,
                )
                ->where(
                    'status',
                    FlightOrderAttempt::STATUS_PROCESSING,
                )
                ->update([
                    'status' =>
                        FlightOrderAttempt::STATUS_FAILED,

                    'supplier_order_id' =>
                        null,

                    'resolved_at' =>
                        now(),
                ]);

        if ($updated !== 1) {
            return null;
        }

        return FlightOrderAttempt::query()
            ->where(
                'attempt_identity_hash',
                $attemptIdentityHash,
            )
            ->first();
    }

    private function normalizeSupplierOrderId(
        string $supplierOrderId,
    ): ?string {
        $supplierOrderId =
            trim(
                $supplierOrderId,
            );

        $controlCharacterMatch =
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $supplierOrderId,
            );

        if (
            $supplierOrderId === ''
            || strlen(
                $supplierOrderId,
            ) > 255
            || $controlCharacterMatch !== 0
        ) {
            return null;
        }

        return $supplierOrderId;
    }
    private function normalizeProvider(
        string $provider,
    ): ?string {
        $provider =
            strtolower(
                trim(
                    $provider,
                ),
            );

        if (
            $provider === ''
            || strlen($provider) > 64
            || preg_match(
                '/^[a-z0-9_-]+$/',
                $provider,
            ) !== 1
        ) {
            return null;
        }

        return $provider;
    }

    private function normalizeSupplierOfferId(
        string $supplierOfferId,
    ): ?string {
        $supplierOfferId =
            trim(
                $supplierOfferId,
            );

        if (
            $supplierOfferId === ''
            || strlen($supplierOfferId) > 255
        ) {
            return null;
        }

        return $supplierOfferId;
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

    private function attemptIdentityHash(
        string $provider,
        string $supplierOfferId,
    ): string {
        return hash(
            'sha256',
            $provider
                . "\0"
                . $supplierOfferId,
        );
    }
}