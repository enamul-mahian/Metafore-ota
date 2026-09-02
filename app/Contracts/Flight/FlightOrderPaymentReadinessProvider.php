<?php

namespace App\Contracts\Flight;

interface FlightOrderPaymentReadinessProvider
{
    /**
     * Read the latest supplier-owned state required to decide whether a
     * previously-created hold order can proceed toward payment.
     *
     * This contract is strictly read-only.
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
    ): array;
}