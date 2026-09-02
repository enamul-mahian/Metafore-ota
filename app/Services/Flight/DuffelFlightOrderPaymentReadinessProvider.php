<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderPaymentReadinessProvider;
use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOrderPaymentReadinessProvider implements FlightOrderPaymentReadinessProvider
{
    /**
     * Read the latest Duffel order state before any future payment attempt.
     *
     * Supplier order identity is server-owned and is never accepted from
     * browser request data by this provider.
     *
     * This method performs GET only. It does not create an order, create a
     * payment, issue a ticket, or mutate local FlightOrderAttempt state.
     *
     * @return array{
     *     total_amount: string,
     *     total_currency: string,
     *     awaiting_payment: bool,
     *     payment_required_by: string|null
     * }
     */
    public function readPaymentReadiness(
        string $supplierOrderId,
    ): array {
        $supplierOrderId =
            $this->normalizeSupplierOrderId(
                $supplierOrderId,
            );

        if ($supplierOrderId === null) {
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

        $apiVersion =
            trim(
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

        $httpTimeout =
            max(
                1,
                (int) config(
                    'flight.duffel.http_timeout',
                    30,
                ),
            );

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
                        '/air/orders/'
                            . $supplierOrderId,
                    );
        } catch (ConnectionException) {
            throw $this->supplierUnavailable();
        }

        if ($response->failed()) {
            throw $this->supplierUnavailable();
        }

        $order =
            $response->json(
                'data',
            );

        if (! is_array($order)) {
            throw $this->supplierUnavailable();
        }

        $responseOrderId =
            $this->normalizeSupplierOrderId(
                $order['id']
                    ?? null,
            );

        if (
            $responseOrderId === null
            || ! hash_equals(
                $supplierOrderId,
                $responseOrderId,
            )
        ) {
            throw $this->supplierUnavailable();
        }

        $totalAmount =
            $this->normalizeAmount(
                $order['total_amount']
                    ?? null,
            );

        $totalCurrency =
            $this->normalizeCurrency(
                $order['total_currency']
                    ?? null,
            );

        $paymentStatus =
            $order['payment_status']
                ?? null;

        if (
            $totalAmount === null
            || $totalCurrency === null
            || ! is_array(
                $paymentStatus,
            )
        ) {
            throw $this->supplierUnavailable();
        }

        $awaitingPayment =
            $paymentStatus[
                'awaiting_payment'
            ]
                ?? null;

        if (! is_bool($awaitingPayment)) {
            throw $this->supplierUnavailable();
        }

        $rawPaymentRequiredBy =
            $paymentStatus[
                'payment_required_by'
            ]
                ?? null;

        $paymentRequiredBy =
            $this->normalizePaymentRequiredBy(
                $rawPaymentRequiredBy,
            );

        if (
            $rawPaymentRequiredBy !== null
            && $paymentRequiredBy === null
        ) {
            throw $this->supplierUnavailable();
        }

        if (
            $awaitingPayment
            && $paymentRequiredBy === null
        ) {
            throw $this->supplierUnavailable();
        }

        return [
            'total_amount' =>
                $totalAmount,

            'total_currency' =>
                $totalCurrency,

            'awaiting_payment' =>
                $awaitingPayment,

            'payment_required_by' =>
                $paymentRequiredBy,
        ];
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
            || preg_match(
                '/[\x00-\x1F\x7F]/',
                $value,
            ) !== 0
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

    private function supplierUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            60,
            'Duffel flight order payment readiness is temporarily unavailable.',
        );
    }
}