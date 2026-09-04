<?php

use App\Contracts\Hotel\HotelSearchProvider;
use App\Contracts\Tour\TourSearchProvider;
use App\Contracts\Travel\DestinationResolver;
use App\Contracts\Visa\VisaInformationProvider;
use App\Services\Hotel\DuffelStaysHotelSearchProvider;
use App\Services\Hotel\UnavailableHotelSearchProvider;
use App\Services\Tour\UnavailableTourSearchProvider;
use App\Services\Travel\DuffelDestinationResolver;
use App\Services\Visa\UnavailableVisaInformationProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Travel Service Capabilities
    |--------------------------------------------------------------------------
    |
    | A service is customer-visible only when it is enabled, has a registered
    | provider adapter, satisfies that adapter's configuration requirements,
    | and has a real application route. Credentials remain server-side.
    |
    */

    'services' => [
        'flights' => [
            'label' => 'Flights',
            'enabled' => true,
            'provider_required' => false,
            'route_name' => 'flights.index',
            'permission' => 'flights.search',
            'unavailable_label' => 'Not Configured',
        ],

        'hotels' => [
            'label' => 'Hotels',
            'enabled' => (bool) env('HOTELS_ENABLED', false),
            'provider' => env('HOTEL_PROVIDER', 'unavailable'),
            'contract' => HotelSearchProvider::class,
            'route_name' => 'hotels.index',
            'permission' => 'hotels.search',
            'unavailable_label' => 'Not Configured',
            'providers' => [
                'unavailable' => UnavailableHotelSearchProvider::class,
                'duffel' => DuffelStaysHotelSearchProvider::class,
            ],
            'provider_dependencies' => [
                'duffel' => [
                    DestinationResolver::class => DuffelDestinationResolver::class,
                ],
            ],
            'provider_requirements' => [
                'unavailable' => [],
                'duffel' => [
                    'duffel.base_url',
                    'duffel.access_token',
                    'duffel.api_version',
                    'duffel.connect_timeout',
                    'duffel.http_timeout',
                    'duffel.search_radius_km',
                ],
            ],
            'provider_rules' => [
                'duffel' => [
                    'duffel.base_url' => ['required', 'url', 'starts_with:https://'],
                    'duffel.access_token' => ['required', 'string'],
                    'duffel.api_version' => ['required', 'in:v2'],
                    'duffel.connect_timeout' => ['required', 'integer', 'between:1,10'],
                    'duffel.http_timeout' => ['required', 'integer', 'between:1,60'],
                    'duffel.search_radius_km' => ['required', 'integer', 'between:1,100'],
                ],
            ],
            'credentials' => [
                'api_key' => env('HOTEL_API_KEY'),
            ],
            'duffel' => [
                'base_url' => env(
                    'DUFFEL_API_BASE_URL',
                    'https://api.duffel.com'
                ),
                'access_token' => env('DUFFEL_ACCESS_TOKEN'),
                'api_version' => env('DUFFEL_API_VERSION', 'v2'),
                'connect_timeout' => env('DUFFEL_CONNECT_TIMEOUT', '5'),
                'http_timeout' => env('DUFFEL_HTTP_TIMEOUT', '30'),
                'search_radius_km' => env(
                    'DUFFEL_STAYS_SEARCH_RADIUS_KM',
                    '5'
                ),
            ],
        ],

        'tours' => [
            'label' => 'Tours',
            'enabled' => (bool) env('TOURS_ENABLED', false),
            'provider' => env('TOUR_PROVIDER', 'unavailable'),
            'contract' => TourSearchProvider::class,
            'route_name' => 'tours.index',
            'permission' => 'tours.search',
            'unavailable_label' => 'Not Configured',
            'providers' => [
                'unavailable' => UnavailableTourSearchProvider::class,
            ],
            'provider_requirements' => [
                'unavailable' => [],
            ],
            'credentials' => [
                'api_key' => env('TOUR_API_KEY'),
            ],
        ],

        'visa' => [
            'label' => 'Visa',
            'enabled' => (bool) env('VISA_ENABLED', false),
            'provider' => env('VISA_PROVIDER', 'unavailable'),
            'contract' => VisaInformationProvider::class,
            'route_name' => 'visa.index',
            'permission' => 'visa.view',
            'unavailable_label' => 'Not Configured',
            'providers' => [
                'unavailable' => UnavailableVisaInformationProvider::class,
            ],
            'provider_requirements' => [
                'unavailable' => [],
            ],
            'credentials' => [
                'api_key' => env('VISA_API_KEY'),
            ],
        ],
    ],
];
