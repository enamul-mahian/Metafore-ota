<?php

namespace App\Services\Flight;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOrderConfirmationProvider
{
    /**
     * @return array{
     *     status: 'confirmed',
     *     provider: 'duffel',
     *     booking_reference: string
     * }
     */
    public function retrieve(
        string $supplierOrderId,
    ): array {
        $supplierOrderId =
            trim($supplierOrderId);

        if (
            ! str_starts_with(
                $supplierOrderId,
                'ord_',
            )
            || strlen($supplierOrderId) > 255
            || preg_match(
                '/^[A-Za-z0-9_]+$/',
                $supplierOrderId,
            ) !== 1
        ) {
            throw $this->unavailable();
        }

        $accessToken =
            trim(
                (string) config(
                    'flight.duffel.access_token',
                    '',
                ),
            );

        $baseUrl =
            rtrim(
                trim(
                    (string) config(
                        'flight.duffel.base_url',
                        '',
                    ),
                ),
                '/',
            );

        $apiVersion =
            trim(
                (string) config(
                    'flight.duffel.api_version',
                    '',
                ),
            );

        $httpTimeout =
            max(
                1,
                (int) config(
                    'flight.duffel.http_timeout',
                    10,
                ),
            );

        if (
            $accessToken === ''
            || $baseUrl === ''
            || $apiVersion === ''
        ) {
            throw $this->unavailable();
        }

        try {
            $response =
                Http::baseUrl(
                    $baseUrl,
                )
                    ->withToken(
                        $accessToken,
                    )
                    ->withHeaders([
                        'Accept' =>
                            'application/json',

                        'Duffel-Version' =>
                            $apiVersion,
                    ])
                    ->timeout(
                        $httpTimeout,
                    )
                    ->get(
                        '/air/orders/' .
                        $supplierOrderId,
                    );
        } catch (ConnectionException) {
            throw $this->unavailable();
        }

        if (! $response->successful()) {
            throw $this->unavailable();
        }

        $order =
            $response->json(
                'data',
            );

        if (! is_array($order)) {
            throw $this->unavailable();
        }

        $responseOrderId =
            $order['id']
                ?? null;

        $bookingReference =
            $order['booking_reference']
                ?? null;

        $awaitingPayment =
            data_get(
                $order,
                'payment_status.awaiting_payment',
            );

        if (
            ! is_string($responseOrderId)
            || trim($responseOrderId)
                !== $supplierOrderId
            || ! is_string($bookingReference)
            || trim($bookingReference) === ''
            || strlen(
                trim($bookingReference),
            ) > 64
            || $awaitingPayment !== false
        ) {
            throw $this->unavailable();
        }

        return [
            'status' =>
                'confirmed',

            'provider' =>
                'duffel',

            'booking_reference' =>
                trim(
                    $bookingReference,
                ),
        ];
    }

    private function unavailable(
    ): ServiceUnavailableHttpException {
        return new ServiceUnavailableHttpException(
            null,
            'Flight booking confirmation is temporarily unavailable.',
        );
    }
}