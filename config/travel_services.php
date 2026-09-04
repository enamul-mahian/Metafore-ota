<?php

use App\Contracts\Hotel\HotelSearchProvider;
use App\Contracts\Tour\TourSearchProvider;
use App\Contracts\Visa\VisaInformationProvider;
use App\Services\Hotel\UnavailableHotelSearchProvider;
use App\Services\Tour\UnavailableTourSearchProvider;
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
            ],
            'provider_requirements' => [
                'unavailable' => [],
            ],
            'credentials' => [
                'api_key' => env('HOTEL_API_KEY'),
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
