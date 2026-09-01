<?php

use App\Services\Flight\UnavailableFlightSearchProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Flight Search Provider
    |--------------------------------------------------------------------------
    |
    | This value selects the provider used for flight searches. Until a real
    | supplier adapter is configured, the unavailable provider keeps the
    | application in a safe and predictable state.
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
    |
    | Each configured provider must implement FlightSearchProvider.
    | Supplier-specific adapters can be registered here later without
    | changing the controller or flight search service.
    |
    */

    'providers' => [
        'unavailable' => UnavailableFlightSearchProvider::class,
    ],

];
