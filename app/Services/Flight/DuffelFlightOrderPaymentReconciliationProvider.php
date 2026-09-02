<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderPaymentReconciliationProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOrderPaymentReconciliationProvider implements FlightOrderPaymentReconciliationProvider
{
    /**
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     supplier_payment_id: string|null
     * }
     */
    public function reconcilePayment(
        string $supplierOrderId,
        ?string $supplierPaymentId,
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
            throw $this->unavailable();
        }

        if ($supplierPaymentId !== null) {
            $supplierPaymentId =
                $this->normalizeSupplierPaymentId(
                    $supplierPaymentId,
                );

            if ($supplierPaymentId === null) {
                throw $this->unavailable();
            }

            return $this->readKnownPayment(
                $supplierOrderId,
                $supplierPaymentId,
                $amount,
                $currency,
            );
        }

        return $this->findPaymentForOrder(
            $supplierOrderId,
            $amount,
            $currency,
        );
    }

    /**
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     supplier_payment_id: string|null
     * }
     */
    private function readKnownPayment(
        string $supplierOrderId,
        string $supplierPaymentId,
        string $amount,
        string $currency,
    ): array {
        try {
            $response =
                $this->request()
                    ->get(
                        '/air/payments/'
                            . $supplierPaymentId,
                    );
        } catch (ConnectionException $exception) {
            throw $this->unavailable(
                $exception,
            );
        }

        $this->requireSuccessful(
            $response,
        );

        $payment =
            $response->json(
                'data',
            );

        if (! is_array($payment)) {
            throw $this->unavailable();
        }

        return $this->normalizeExactPayment(
            $payment,
            $supplierOrderId,
            $amount,
            $currency,
            $supplierPaymentId,
        );
    }

    /**
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     supplier_payment_id: string|null
     * }
     */
    private function findPaymentForOrder(
        string $supplierOrderId,
        string $amount,
        string $currency,
    ): array {
        try {
            $response =
                $this->request()
                    ->get(
                        '/air/payments',
                        [
                            'order_id' =>
                                $supplierOrderId,

                            'limit' =>
                                200,
                        ],
                    );
        } catch (ConnectionException $exception) {
            throw $this->unavailable(
                $exception,
            );
        }

        $this->requireSuccessful(
            $response,
        );

        $payments =
            $response->json(
                'data',
            );

        if (
            ! is_array($payments)
            || ! array_is_list($payments)
        ) {
            throw $this->unavailable();
        }

        $matches = [];

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                throw $this->unavailable();
            }

            if (
                $this->matchesIdentity(
                    $payment,
                    $supplierOrderId,
                    $amount,
                    $currency,
                )
            ) {
                $matches[] =
                    $payment;
            }
        }

        if (count($matches) === 0) {
            return [
                'status' =>
                    'processing',

                'supplier_payment_id' =>
                    null,
            ];
        }

        if (count($matches) !== 1) {
            throw $this->unavailable();
        }

        return $this->normalizeExactPayment(
            $matches[0],
            $supplierOrderId,
            $amount,
            $currency,
            null,
        );
    }

    private function request(): PendingRequest
    {
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

        $timeout =
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
            throw $this->unavailable();
        }

        return Http::baseUrl(
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
                $timeout,
            );
    }

    private function requireSuccessful(
        Response $response,
    ): void {
        if (! $response->successful()) {
            throw $this->unavailable();
        }
    }

    /**
     * @param array<string, mixed> $payment
     *
     * @return array{
     *     status: 'processing'|'succeeded'|'failed',
     *     supplier_payment_id: string
     * }
     */
    private function normalizeExactPayment(
        array $payment,
        string $supplierOrderId,
        string $amount,
        string $currency,
        ?string $expectedPaymentId,
    ): array {
        if (
            ! $this->matchesIdentity(
                $payment,
                $supplierOrderId,
                $amount,
                $currency,
            )
        ) {
            throw $this->unavailable();
        }

        $paymentId =
            $this->normalizeSupplierPaymentId(
                $payment['id']
                    ?? null,
            );

        if ($paymentId === null) {
            throw $this->unavailable();
        }

        if (
            $expectedPaymentId !== null
            && ! hash_equals(
                $expectedPaymentId,
                $paymentId,
            )
        ) {
            throw $this->unavailable();
        }

        $status =
            $payment['status']
                ?? null;

        if (! is_string($status)) {
            throw $this->unavailable();
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

        throw $this->unavailable();
    }

    /**
     * @param array<string, mixed> $payment
     */
    private function matchesIdentity(
        array $payment,
        string $supplierOrderId,
        string $amount,
        string $currency,
    ): bool {
        $paymentOrderId =
            $this->normalizeSupplierOrderId(
                $payment['order_id']
                    ?? null,
            );

        $paymentAmount =
            $this->normalizeAmount(
                $payment['amount']
                    ?? null,
            );

        $paymentCurrency =
            $this->normalizeCurrency(
                $payment['currency']
                    ?? null,
            );

        $paymentType =
            $payment['type']
                ?? null;

        return $paymentOrderId !== null
            && hash_equals(
                $supplierOrderId,
                $paymentOrderId,
            )
            && $paymentAmount !== null
            && hash_equals(
                $amount,
                $paymentAmount,
            )
            && $paymentCurrency !== null
            && hash_equals(
                $currency,
                $paymentCurrency,
            )
            && $paymentType === 'balance';
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

    private function unavailable(
        ?\Throwable $previous = null,
    ): ServiceUnavailableHttpException {
        return new ServiceUnavailableHttpException(
            null,
            'Flight payment reconciliation is unavailable.',
            $previous,
        );
    }
}