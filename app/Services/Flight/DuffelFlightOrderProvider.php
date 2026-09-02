<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderProvider;
use App\Exceptions\Flight\FlightOrderProcessingException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOrderProvider implements FlightOrderProvider
{
    public function __construct(
        private readonly DuffelFlightOrderRequestBuilder $requestBuilder,
        private readonly FlightOrderAttemptStore $attemptStore,
    ) {
    }

    /**
     * Create a Duffel hold order only from the trusted server-side
     * confirmation-intent snapshot.
     *
     * Live supplier order creation remains disabled by default.
     *
     * @param  array<string, mixed>  $trustedConfirmationIntent
     * @return array{
     *     status: string,
     *     live_order_creation: bool,
     *     order_created: bool
     * }
     */
    public function createFromTrustedConfirmationIntent(
        array $trustedConfirmationIntent
    ): array {
        if (
            config(
                'flight_orders.duffel.live_order_creation_enabled',
                false,
            ) !== true
        ) {
            return $this->unavailableResult();
        }

        $this->assertDuffelIntent(
            $trustedConfirmationIntent,
        );

        /*
         * The builder is the only request-body boundary.
         * It validates the trusted offer/passenger snapshot,
         * requires hold eligibility, emits type=hold,
         * and deliberately omits payments.
         */
        $requestBody =
            $this->requestBuilder->buildHold(
                $trustedConfirmationIntent,
            );

        $accessToken = trim(
            (string) config(
                'flight.duffel.access_token',
                '',
            ),
        );

        if ($accessToken === '') {
            throw $this->supplierUnavailable();
        }

        $baseUrl = rtrim(
            trim(
                (string) config(
                    'flight.duffel.base_url',
                    'https://api.duffel.com',
                ),
            ),
            '/',
        );

        $apiVersion = trim(
            (string) config(
                'flight.duffel.api_version',
                'v2',
            ),
        );

        if (
            $baseUrl === ''
            || $apiVersion === ''
        ) {
            throw $this->supplierUnavailable();
        }

        /*
         * Duffel order creation can take up to 120 seconds.
         * Keep an explicit safety floor above that boundary.
         *
         * No automatic retry is used because order creation
         * is not universally safe to replay.
         */
        $httpTimeout = max(
            130,
            (int) config(
                'flight.duffel.http_timeout',
                130,
            ),
        );

        /*
         * Claim immediately before the supplier HTTP boundary.
         *
         * The identity is the exact trusted offer ID emitted by the
         * request builder into selected_offers. This means multiple
         * confirmation-intent tokens for the same supplier offer cannot
         * result in multiple local POST /air/orders attempts.
         *
         * Local failures before this point do not burn the claim.
         * Once claimed, the marker is deliberately retained even when
         * the supplier result is uncertain.
         */
        $attemptOfferId =
            $this->attemptOfferId(
                $requestBody,
            );

        if (
            ! $this->attemptStore->claim(
                'duffel',
                $attemptOfferId,
            )
        ) {
            throw $this->supplierUnavailable();
        }

        try {
            $response = Http::baseUrl(
                $baseUrl,
            )
                ->withToken(
                    $accessToken,
                )
                ->acceptJson()
                ->withHeaders([
                    'Duffel-Version' => $apiVersion,
                ])
                ->timeout(
                    $httpTimeout,
                )
                ->post(
                    '/air/orders',
                    $requestBody,
                );
        } catch (ConnectionException) {
            /*
             * Do not release the attempt claim.
             * The supplier outcome can be uncertain.
             */
            throw $this->supplierUnavailable();
        }

        /*
         * HTTP 202 means Duffel accepted the create-order request but the
         * final order outcome is not available yet.
         *
         * Keep the existing provider + trusted-offer attempt claim.
         * Do not parse the response as a completed order and do not retry.
         */
        if ($response->status() === 202) {
            throw (new FlightOrderProcessingException(
                'duffel',
            ))->withSupplierOfferId(
                $attemptOfferId,
            );
        }
        if ($response->failed()) {
            /*
             * Do not release the attempt claim.
             * The current boundary must not blindly replay order creation.
             */
            throw $this->supplierUnavailable();
        }

        $supplierOrder = $response->json(
            'data',
        );

        if (! is_array($supplierOrder)) {
            /*
             * Includes accepted/ambiguous responses that do not yet
             * contain a normalized Duffel order object.
             *
             * Do not release the attempt claim.
             */
            throw $this->supplierUnavailable();
        }

        $supplierOrderId = trim(
            (string) (
                $supplierOrder['id']
                ?? ''
            ),
        );

        if (! $this->isOrderId($supplierOrderId)) {
            throw $this->supplierUnavailable();
        }

        /*
         * Do not expose the raw Duffel response, supplier request body,
         * passenger PII, token, payment data, or supplier internals.
         *
         * The current FlightOrderProvider contract intentionally returns
         * only these safe normalized state fields.
         */
        return [
            'status' => 'created',
            'live_order_creation' => true,
            'order_created' => true,

            /*
             * Internal server-only durability bridge.
             *
             * Controllers must never expose this supplier identity.
             */
            'supplier_order_id' =>
                $supplierOrderId,
        ];
    }

    /**
     * @param array<string, mixed> $trustedConfirmationIntent
     */
    private function assertDuffelIntent(
        array $trustedConfirmationIntent,
    ): void {
        $provider = data_get(
            $trustedConfirmationIntent,
            'provider',
        );

        if (
            ! is_string($provider)
            || strtolower(trim($provider)) !== 'duffel'
        ) {
            throw $this->supplierUnavailable();
        }
    }

    /**
     * @param array<string, mixed> $requestBody
     */
    private function attemptOfferId(
        array $requestBody,
    ): string {
        $offerId =
            data_get(
                $requestBody,
                'data.selected_offers.0',
            );

        if (! is_string($offerId)) {
            throw $this->supplierUnavailable();
        }

        $offerId =
            trim(
                $offerId,
            );

        if (
            $offerId === ''
            || strlen($offerId) > 255
        ) {
            throw $this->supplierUnavailable();
        }

        return $offerId;
    }

    private function isOrderId(
        string $value,
    ): bool {
        if (
            $value === ''
            || strlen($value) > 255
            || ! str_starts_with(
                $value,
                'ord_',
            )
        ) {
            return false;
        }

        return preg_match(
            '/^[A-Za-z0-9_]+$/',
            $value,
        ) === 1;
    }

    /**
     * @return array{
     *     status: string,
     *     live_order_creation: bool,
     *     order_created: bool
     * }
     */
    private function unavailableResult(): array
    {
        return [
            'status' => 'unavailable',
            'live_order_creation' => false,
            'order_created' => false,
        ];
    }

    private function supplierUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            60,
            'Duffel flight order is temporarily unavailable.',
        );
    }
}