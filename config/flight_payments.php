<?php

return [
    /*
     * Payment execution is disabled unless a supported payment type is
     * explicitly configured server-side.
     *
     * Delivery-critical hold-order payment currently supports only
     * Duffel balance. Do not accept payment type from browser input.
     */
    'duffel' => [
        'type' =>
            env(
                'DUFFEL_PAYMENT_TYPE',
            ),
    ],
];