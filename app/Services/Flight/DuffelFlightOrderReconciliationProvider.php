<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderReconciliationProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOrderReconciliationProvider implements FlightOrderReconciliationProvider
{
    /**
     * @return array{
     *     status: 'processing'
     * }|array{
     *     status: 'created',
     *     supplier_order_id: string
     * }
     */
    public function readBySupplierOfferId(
        string $supplierOfferId,
    ): array {
        $supplierOfferId =
            $this->normalizeSupplierOfferId(
                $supplierOfferId,
            );

        if ($supplierOfferId === null) {
            throw $this->supplierUnavailable();
        }

        $accessToken =
            trim(
                (string) config(
                    'flight.duffel.access_token',
                    '',
                ),
            );

        if ($accessToken === '') {
            throw $this->supplierUnavailable();
        }

        $baseUrl =
            rtrim(
                trim(
                    (string) config(
                        'flight.duffel.base_url',
                        'https://api.duffel.com',
                    ),
                ),
                '/',
            );

        if ($baseUrl === '') {
            throw $this->supplierUnavailable();
        }

        $apiVersion =
            trim(
                (string) config(
                    'flight.duffel.api_version',
                    'v2',
                ),
            );

        if ($apiVersion === '') {
            throw $this->supplierUnavailable();
        }

        $httpTimeout =
            (int) config(
                'flight.duffel.http_timeout',
                30,
            );

        if ($httpTimeout < 1) {
            throw $this->supplierUnavailable();
        }

        try {
            $response =
                Http::baseUrl(
                    $baseUrl,
                )
                    ->withToken(
                        $accessToken,
                    )
                    ->acceptJson()
                    ->withHeaders([
                        'Duffel-Version' =>
                            $apiVersion,
                    ])
                    ->timeout(
                        $httpTimeout,
                    )
                    ->get(
                        '/air/orders',
                        [
                            'offer_id' =>
                                $supplierOfferId,

                            /*
                             * Two is intentional:
                             *
                             * - 0 = still unresolved
                             * - 1 = exact candidate
                             * - 2 = enough evidence to fail closed as ambiguous
                             *
                             * Never fetch unbounded order history.
                             */
                            'limit' =>
                                2,
                        ],
                    );
        } catch (ConnectionException) {
            throw $this->supplierUnavailable();
        }

        if ($response->failed()) {
            throw $this->supplierUnavailable();
        }

        $supplierOrders =
            $response->json(
                'data',
            );

        if (
            ! is_array(
                $supplierOrders,
            )
            || ! array_is_list(
                $supplierOrders,
            )
        ) {
            throw $this->supplierUnavailable();
        }

        $supplierOrderCount =
            count(
                $supplierOrders,
            );

        /*
         * Listing absence does NOT prove order creation failed.
         *
         * The durable attempt must remain processing.
         */
        if ($supplierOrderCount === 0) {
            return [
                'status' =>
                    'processing',
            ];
        }

        if ($supplierOrderCount !== 1) {
            throw $this->supplierUnavailable();
        }

        $supplierOrder =
            $supplierOrders[0];

        if (! is_array($supplierOrder)) {
            throw $this->supplierUnavailable();
        }

        $responseOfferId =
            $supplierOrder['offer_id']
            ?? null;

        if (! is_string($responseOfferId)) {
            throw $this->supplierUnavailable();
        }

        $responseOfferId =
            trim(
                $responseOfferId,
            );

        if (
            ! hash_equals(
                $supplierOfferId,
                $responseOfferId,
            )
        ) {
            throw $this->supplierUnavailable();
        }

        $supplierOrderId =
            $supplierOrder['id']
            ?? null;

        if (! is_string($supplierOrderId)) {
            throw $this->supplierUnavailable();
        }

        $supplierOrderId =
            $this->normalizeSupplierOrderId(
                $supplierOrderId,
            );

        if ($supplierOrderId === null) {
            throw $this->supplierUnavailable();
        }

        /*
         * This provider performs supplier READ only.
         *
         * It must not mutate FlightOrderAttempt state. A separate
         * reconciliation service may later consume this normalized result.
         */
        return [
            'status' =>
                'created',

            'supplier_order_id' =>
                $supplierOrderId,
        ];
    }

    private function normalizeSupplierOfferId(
        string $supplierOfferId,
    ): ?string {
        $supplierOfferId =
            trim(
                $supplierOfferId,
            );

        $controlCharacterMatch =
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $supplierOfferId,
            );

        if (
            $supplierOfferId === ''
            || strlen(
                $supplierOfferId,
            ) > 255
            || $controlCharacterMatch !== 0
        ) {
            return null;
        }

        return $supplierOfferId;
    }

    private function normalizeSupplierOrderId(
        string $supplierOrderId,
    ): ?string {
        $supplierOrderId =
            trim(
                $supplierOrderId,
            );

        if (
            strlen(
                $supplierOrderId,
            ) > 255
        ) {
            return null;
        }

        if (
            preg_match(
                '/^ord_[A-Za-z0-9]+$/',
                $supplierOrderId,
            ) !== 1
        ) {
            return null;
        }

        return $supplierOrderId;
    }

    private function supplierUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Flight order reconciliation is temporarily unavailable.',
        );
    }
}