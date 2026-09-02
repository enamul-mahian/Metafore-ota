<?php

namespace App\Contracts\Flight;

interface FlightOrderReconciliationProvider
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
    ): array;
}