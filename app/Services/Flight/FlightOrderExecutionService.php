<?php

namespace App\Services\Flight;

use App\Exceptions\Flight\FlightOrderProcessingException;

final class FlightOrderExecutionService
{
    public function __construct(
        private readonly FlightBookingConfirmationIntentStore $confirmationIntentStore,
        private readonly FlightOrderService $orderService,
        private readonly FlightOrderAttemptRecordStore $orderAttemptRecordStore,
    ) {
    }

    /**
     * Consume one user-scoped confirmation intent and delegate its
     * server-trusted snapshot to the provider-neutral order service.
     *
     * This service performs no direct supplier HTTP. On an explicit
     * asynchronous processing outcome, it persists only the minimal
     * user-owned durable attempt identity needed for reconciliation.
     *
     * A consumed execution attempt is intentionally single-use even when
     * the downstream provider returns unavailable. This prevents replay of
     * an order-creation attempt whose supplier outcome may be uncertain.
     *
     * @return array{
     *     status: string,
     *     provider: string|null,
     *     live_order_creation: bool,
     *     order_created: bool,
     *     confirmation_intent_consumed: bool
     * }
     */
    public function execute(
        int $userId,
        string $confirmationIntentToken,
    ): array {
        $trustedIntent =
            $this->confirmationIntentStore->take(
                $userId,
                $confirmationIntentToken,
            );

        if (! is_array($trustedIntent)) {
            return $this->unavailableResult(
                false,
                null,
            );
        }

        $provider =
            $this->trustedProvider(
                $trustedIntent,
            );

        if ($provider === null) {
            return $this->unavailableResult(
                true,
                null,
            );
        }

        /*
         * FlightOrderService currently resolves providers from a
         * top-level provider key.
         *
         * That key is derived here only from the encrypted,
         * server-trusted offer/revalidation snapshot.
         *
         * No client-provided provider value is accepted.
         */
        $trustedIntent['provider'] =
            $provider;

        try {
            $result =
                $this->orderService
                    ->createFromTrustedConfirmationIntent(
                        $trustedIntent,
                    );
        } catch (FlightOrderProcessingException $exception) {
            $supplierOfferId =
                $exception->supplierOfferId();

            if ($supplierOfferId === null) {
                throw $exception;
            }

            try {
                $processingRecord =
                    $this->orderAttemptRecordStore
                        ->createProcessing(
                            $userId,
                            $exception->provider(),
                            $supplierOfferId,
                        );
            } catch (\Throwable $persistenceException) {
                /*
                 * The supplier outcome remains uncertain even when
                 * durable persistence is temporarily unavailable.
                 * Keep the externally safe processing semantics and
                 * never turn this into a retry instruction.
                 */
                report(
                    $persistenceException,
                );

                throw $exception;
            }

            $attemptReference =
                is_array($processingRecord)
                    ? ($processingRecord['reference'] ?? null)
                    : null;

            if (
                ! is_string($attemptReference)
                || $attemptReference === ''
            ) {
                throw $exception;
            }

            throw $exception
                ->withAttemptReference(
                    $attemptReference,
                );
        }

        $status =
            $result['status']
                ?? null;

        $resultProvider =
            $result['provider']
                ?? null;

        return [
            'status' =>
                is_string($status)
                && $status !== ''
                    ? $status
                    : 'unavailable',

            'provider' =>
                is_string($resultProvider)
                && $resultProvider !== ''
                    ? $resultProvider
                    : null,

            'live_order_creation' =>
                (
                    $result[
                        'live_order_creation'
                    ]
                    ?? false
                ) === true,

            'order_created' =>
                (
                    $result[
                        'order_created'
                    ]
                    ?? false
                ) === true,

            'confirmation_intent_consumed' =>
                true,
        ];
    }

    /**
     * @param array<string, mixed> $trustedIntent
     */
    private function trustedProvider(
        array $trustedIntent,
    ): ?string {
        $offerProvider =
            $this->normalizeProvider(
                data_get(
                    $trustedIntent,
                    'offer.provider',
                ),
            );

        $revalidationProvider =
            $this->normalizeProvider(
                data_get(
                    $trustedIntent,
                    'revalidation.provider',
                ),
            );

        $revalidationStatus =
            data_get(
                $trustedIntent,
                'revalidation.status',
            );

        $liveRevalidation =
            data_get(
                $trustedIntent,
                'revalidation.live_revalidation',
                false,
            );

        if (
            $offerProvider === null
            || $revalidationProvider === null
            || ! hash_equals(
                $offerProvider,
                $revalidationProvider,
            )
            || $revalidationStatus !== 'revalidated'
            || $liveRevalidation !== true
        ) {
            return null;
        }

        return $offerProvider;
    }

    private function normalizeProvider(
        mixed $provider,
    ): ?string {
        if (! is_string($provider)) {
            return null;
        }

        $provider =
            strtolower(
                trim(
                    $provider,
                ),
            );

        return $provider !== ''
            ? $provider
            : null;
    }

    /**
     * @return array{
     *     status: string,
     *     provider: string|null,
     *     live_order_creation: bool,
     *     order_created: bool,
     *     confirmation_intent_consumed: bool
     * }
     */
    private function unavailableResult(
        bool $confirmationIntentConsumed,
        ?string $provider,
    ): array {
        return [
            'status' => 'unavailable',
            'provider' => $provider,
            'live_order_creation' => false,
            'order_created' => false,
            'confirmation_intent_consumed' =>
                $confirmationIntentConsumed,
        ];
    }
}