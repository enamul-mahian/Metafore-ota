<?php

namespace App\Services\Flight;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOrderConfirmationProvider
{
    public function __construct(
        private readonly DuffelFlightOrderConfirmationNormalizer $normalizer,
    ) {}

    /**
     * Retrieve an existing Duffel order using a GET-only boundary.
     *
     * Only customer-safe booking confirmation fields are returned.
     * Supplier identifiers, passenger PII, payment data and arbitrary
     * supplier response fields are deliberately excluded.
     *
     * @return array{
     *     status: 'confirmed',
     *     provider: 'duffel',
     *     booking_reference: string,
     *     itinerary: array<int, array{
     *         segments: array<int, array{
     *             origin: array{
     *                 iata_code: string,
     *                 name: string
     *             },
     *             destination: array{
     *                 iata_code: string,
     *                 name: string
     *             },
     *             departing_at: string,
     *             arriving_at: string,
     *             origin_terminal: string|null,
     *             destination_terminal: string|null,
     *             operating_carrier: array{
     *                 name: string
     *             },
     *             operating_carrier_flight_number: string|null,
     *             marketing_carrier: array{
     *                 name: string|null
     *             },
     *             marketing_carrier_flight_number: string|null
     *         }>
     *     }>
     * }
     */
    public function retrieve(
        string $supplierOrderId,
    ): array {
        $supplierOrderId = trim(
            $supplierOrderId,
        );

        if (! $this->isOrderId($supplierOrderId)) {
            throw $this->supplierUnavailable();
        }

        $accessToken = trim(
            (string) config(
                'flight.duffel.access_token',
                '',
            ),
        );

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
            $accessToken === ''
            || $baseUrl === ''
            || $apiVersion === ''
        ) {
            throw $this->supplierUnavailable();
        }

        $httpTimeout = max(
            1,
            (int) config(
                'flight.duffel.http_timeout',
                30,
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
                ->get(
                    '/air/orders/'.
                    rawurlencode(
                        $supplierOrderId,
                    ),
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

        $responseOrderId =
            is_string(
                $supplierOrder['id']
                    ?? null,
            )
                ? trim(
                    $supplierOrder['id'],
                )
                : '';

        if (
            ! $this->isOrderId(
                $responseOrderId,
            )
            || ! hash_equals(
                $supplierOrderId,
                $responseOrderId,
            )
        ) {
            throw $this->supplierUnavailable();
        }

        $confirmation =
            $this->normalizer->normalize(
                $supplierOrder,
            );

        if (! is_array($confirmation)) {
            throw $this->supplierUnavailable();
        }

        return [
            'status' => 'confirmed',

            'provider' => 'duffel',

            'booking_reference' => $confirmation['booking_reference'],

            'itinerary' => $confirmation['itinerary'],
        ];
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

    private function supplierUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            60,
            'Duffel flight booking confirmation is temporarily unavailable.',
        );
    }
}
