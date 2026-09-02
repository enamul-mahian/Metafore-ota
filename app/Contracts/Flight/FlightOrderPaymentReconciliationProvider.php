<?php

namespace App\Contracts\Flight;

interface FlightOrderPaymentReconciliationProvider
{
    /**
     * Read supplier payment state without creating or retrying a payment.
     *
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
    ): array;
}