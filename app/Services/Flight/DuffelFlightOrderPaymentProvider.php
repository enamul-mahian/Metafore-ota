<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderPaymentProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class DuffelFlightOrderPaymentProvider implements FlightOrderPaymentProvider
{
    /**
     * Performs at most one POST /air/payments call.
     *
     * Connection failures, timeout-like responses, rate limiting and
     * supplier 5xx outcomes are treated as ambiguous processing outcomes.
     * This provider never retries the POST.
     *
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     supplier_payment_id: string|null
     * }
     */
    public function createPayment(
        string $supplierOrderId,
        string $amount,
        string $currency,
        string $paymentType,
    ): array {
        $supplierOrderId =
            $this->normalizeSupplierOrderId(
                $supplierOrderId,
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
            $supplierOrderId === null
            || $amount === null
            || $currency === null
            || $paymentType !== 'balance'
        ) {
            return $this->failed();
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

        $httpTimeout =
            max(
                1,
                (int) config(
                    'flight.duffel.http_timeout',
                    30,
                ),
            );

        if (
            $accessToken === ''
            || $baseUrl === ''
            || $apiVersion === ''
        ) {
            return $this->processing();
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
                    ->asJson()
                    ->withHeaders([
                        'Duffel-Version' =>
                            $apiVersion,
                    ])
                    ->timeout(
                        $httpTimeout,
                    )
                    ->post(
                        '/air/payments',
                        [
                            'data' => [
                                'order_id' =>
                                    $supplierOrderId,

                                'payment' => [
                                    'type' =>
                                        'balance',

                                    'amount' =>
                                        $amount,

                                    'currency' =>
                                        $currency,
                                ],
                            ],
                        ],
                    );
        } catch (ConnectionException) {
            return $this->processing();
        }

        $httpStatus =
            $response->status();

        if (
            $httpStatus === 408
            || $httpStatus === 429
            || $httpStatus >= 500
        ) {
            return $this->processing();
        }

        if (! $response->successful()) {
            return $this->failed();
        }

        $payment =
            $response->json(
                'data',
            );

        if (! is_array($payment)) {
            return $this->processing();
        }

        $paymentId =
            $this->normalizeSupplierPaymentId(
                $payment['id']
                    ?? null,
            );

        $responseOrderId =
            $this->normalizeSupplierOrderId(
                $payment['order_id']
                    ?? null,
            );

        $responseAmount =
            $this->normalizeAmount(
                $payment['amount']
                    ?? null,
            );

        $responseCurrency =
            $this->normalizeCurrency(
                $payment['currency']
                    ?? null,
            );

        $responseType =
            $payment['type']
                ?? null;

        $status =
            $payment['status']
                ?? null;

        if (
            $paymentId === null
            || $responseOrderId === null
            || ! hash_equals(
                $supplierOrderId,
                $responseOrderId,
            )
            || $responseAmount === null
            || ! hash_equals(
                $amount,
                $responseAmount,
            )
            || $responseCurrency === null
            || ! hash_equals(
                $currency,
                $responseCurrency,
            )
            || $responseType !== 'balance'
            || ! is_string($status)
        ) {
            return $this->processing();
        }

        if ($status === 'succeeded') {
            return [
                'status' =>
                    'succeeded',

                'supplier_payment_id' =>
                    $paymentId,
            ];
        }

        if ($status === 'pending') {
            return [
                'status' =>
                    'processing',

                'supplier_payment_id' =>
                    $paymentId,
            ];
        }

        if (
            $status === 'failed'
            || $status === 'cancelled'
        ) {
            return [
                'status' =>
                    'failed',

                'supplier_payment_id' =>
                    $paymentId,
            ];
        }

        return $this->processing(
            $paymentId,
        );
    }

    /**
     * @return array{
     *     status: 'processing',
     *     supplier_payment_id: string|null
     * }
     */
    private function processing(
        ?string $supplierPaymentId = null,
    ): array {
        return [
            'status' =>
                'processing',

            'supplier_payment_id' =>
                $supplierPaymentId,
        ];
    }

    /**
     * @return array{
     *     status: 'failed',
     *     supplier_payment_id: null
     * }
     */
    private function failed(): array
    {
        return [
            'status' =>
                'failed',

            'supplier_payment_id' =>
                null,
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
}