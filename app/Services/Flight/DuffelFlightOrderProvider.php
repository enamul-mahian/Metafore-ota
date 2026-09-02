<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOrderProvider implements FlightOrderProvider
{
    public function __construct(
        private readonly DuffelFlightOrderRequestBuilder $requestBuilder,
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
            throw $this->supplierUnavailable();
        }

        if ($response->failed()) {
            throw $this->supplierUnavailable();
        }

        $supplierOrder = $response->json(
            'data',
        );

        if (! is_array($supplierOrder)) {
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