<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderReconciliationProvider;
use App\Models\FlightOrderAttempt;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderReconciliationService
{
    public function __construct(
        private readonly FlightOrderAttemptRecordStore $attemptStore,
        private readonly DuffelFlightOrderReconciliationProvider $duffelProvider,
    ) {
    }

    /**
     * Reconcile one user-owned durable attempt using only server-side
     * provider and supplier-offer identity.
     *
     * The caller supplies only user identity + opaque attempt reference.
     *
     * @return array{
     *     status: string,
     *     provider: string
     * }|null
     */
    public function reconcile(
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

        $localResult =
            $this->localResult(
                $attempt,
            );

        if ($localResult === null) {
            return null;
        }

        /*
         * Terminal durable state is authoritative.
         *
         * Never issue supplier reads for already-resolved attempts.
         */
        if (
            $localResult['status']
            !== FlightOrderAttempt::STATUS_PROCESSING
        ) {
            return $localResult;
        }

        /*
         * Provider comes only from the durable server-side attempt.
         *
         * The current live reconciliation implementation is Duffel.
         * Unsupported providers fail closed before supplier HTTP.
         */
        $provider =
            $this->providerFor(
                $localResult['provider'],
            );

        if (
            ! $provider instanceof
                FlightOrderReconciliationProvider
        ) {
            return null;
        }

        $supplierOfferId =
            $this->normalizeSupplierIdentity(
                $attempt->supplier_offer_id,
            );

        if ($supplierOfferId === null) {
            return null;
        }

        $supplierResult =
            $provider
                ->readBySupplierOfferId(
                    $supplierOfferId,
                );

        $supplierStatus =
            $supplierResult['status']
                ?? null;

        /*
         * Listing absence or an unresolved supplier result does not prove
         * failure. Preserve processing and re-read durable state so a
         * concurrent terminal resolver remains authoritative.
         */
        if ($supplierStatus === 'processing') {
            return $this->latestLocalResult(
                $userId,
                $attemptReference,
            );
        }

        if ($supplierStatus !== 'created') {
            throw $this->supplierUnavailable();
        }

        $supplierOrderId =
            $this->normalizeSupplierIdentity(
                $supplierResult[
                    'supplier_order_id'
                ]
                    ?? null,
            );

        if ($supplierOrderId === null) {
            throw $this->supplierUnavailable();
        }

        /*
         * markCreated performs the atomic processing-only transition.
         *
         * No manual model save/update may bypass that terminal guard.
         */
        $createdAttempt =
            $this->attemptStore
                ->markCreated(
                    $localResult['provider'],
                    $supplierOfferId,
                    $supplierOrderId,
                );

        if ($createdAttempt instanceof FlightOrderAttempt) {
            return $this->localResult(
                $createdAttempt,
            );
        }

        /*
         * A null transition can mean another actor won the terminal-state
         * race. Re-read the same user-owned attempt and never overwrite it.
         */
        return $this->latestLocalResult(
            $userId,
            $attemptReference,
        );
    }

    private function providerFor(
        string $provider,
    ): ?FlightOrderReconciliationProvider {
        if ($provider !== 'duffel') {
            return null;
        }

        return $this->duffelProvider;
    }

    /**
     * @return array{
     *     status: string,
     *     provider: string
     * }|null
     */
    private function latestLocalResult(
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

        return $this->localResult(
            $attempt,
        );
    }

    /**
     * @return array{
     *     status: string,
     *     provider: string
     * }|null
     */
    private function localResult(
        FlightOrderAttempt $attempt,
    ): ?array {
        $status =
            is_string(
                $attempt->status,
            )
                ? trim(
                    $attempt->status,
                )
                : '';

        $provider =
            is_string(
                $attempt->provider,
            )
                ? strtolower(
                    trim(
                        $attempt->provider,
                    ),
                )
                : '';

        if (
            ! in_array(
                $status,
                [
                    FlightOrderAttempt::STATUS_PROCESSING,
                    FlightOrderAttempt::STATUS_CREATED,
                    FlightOrderAttempt::STATUS_FAILED,
                ],
                true,
            )
            || $provider === ''
            || strlen(
                $provider,
            ) > 64
            || preg_match(
                '/^[a-z0-9_-]+$/',
                $provider,
            ) !== 1
        ) {
            return null;
        }

        return [
            'status' =>
                $status,

            'provider' =>
                $provider,
        ];
    }

    private function normalizeSupplierIdentity(
        mixed $identity,
    ): ?string {
        if (! is_string($identity)) {
            return null;
        }

        $identity =
            trim(
                $identity,
            );

        $controlCharacterMatch =
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $identity,
            );

        if (
            $identity === ''
            || strlen(
                $identity,
            ) > 255
            || $controlCharacterMatch !== 0
        ) {
            return null;
        }

        return $identity;
    }

    private function supplierUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Flight order reconciliation is temporarily unavailable.',
        );
    }
}