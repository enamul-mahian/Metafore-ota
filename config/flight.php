<?php

use App\Services\Flight\DuffelFlightSearchProvider;
use App\Services\Flight\UnavailableFlightSearchProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Flight Search Provider
    |--------------------------------------------------------------------------
    |
    | Keep "unavailable" as the safe default. A real supplier is enabled only
    | when its environment-backed credentials have been configured locally.
    |
    */

    'search_provider' => env(
        'FLIGHT_SEARCH_PROVIDER',
        'unavailable'
    ),

    /*
    |--------------------------------------------------------------------------
    | Available Flight Search Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'unavailable' => UnavailableFlightSearchProvider::class,
        'duffel' => DuffelFlightSearchProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Duffel
    |--------------------------------------------------------------------------
    |
    | Secrets must remain in environment variables. Never commit a real
    | Duffel access token.
    |
    */

    'duffel' => [
        'base_url' => env(
            'DUFFEL_API_BASE_URL',
            'https://api.duffel.com'
        ),

        'access_token' => env('DUFFEL_ACCESS_TOKEN'),

        'api_version' => env(
            'DUFFEL_API_VERSION',
            'v2'
        ),

        'http_timeout' => (int) env(
            'DUFFEL_HTTP_TIMEOUT',
            30
        ),

        'supplier_timeout_ms' => (int) env(
            'DUFFEL_SUPPLIER_TIMEOUT_MS',
            20000
        ),
    ],

];
