<?php

use App\Services\Flight\DuffelFlightOrderProvider;
use App\Services\Flight\UnavailableFlightOrderProvider;

return [
    /*
     * The authenticated execution endpoint is independently
     * disabled by default. Exposing the route does not enable
     * supplier order creation.
     */
    'http_execution_enabled' => env(
        'FLIGHT_ORDER_HTTP_EXECUTION_ENABLED',
        false,
    ),

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