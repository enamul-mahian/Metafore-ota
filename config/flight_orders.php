<?php

use App\Services\Flight\DuffelFlightOrderProvider;
use App\Services\Flight\UnavailableFlightOrderProvider;

return [
    /*
     * Live supplier order creation is intentionally disabled by default.
     * Enabling this flag alone does not connect any controller or UI flow.
     */
    'duffel' => [
        'live_order_creation_enabled' => env(
            'DUFFEL_LIVE_ORDER_CREATION_ENABLED',
            false,
        ),
    ],

    'providers' => [
        'fixture' => UnavailableFlightOrderProvider::class,
        'duffel' => DuffelFlightOrderProvider::class,
    ],
];