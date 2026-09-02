<?php

namespace App\Contracts\Flight;

interface FlightOrderPaymentProvider
{
    /**
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
    ): array;
}