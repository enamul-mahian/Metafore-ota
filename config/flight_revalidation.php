<?php

use App\Services\Flight\FixtureFlightOfferRevalidationProvider;
use App\Services\Flight\UnavailableFlightOfferRevalidationProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Flight Offer Revalidation Providers
    |--------------------------------------------------------------------------
    |
    | Provider selection is based only on the provider value contained in the
    | trusted server-side offer stored in the encrypted booking draft.
    |
    | Fixture remains demo-only and non-bookable.
    | Duffel intentionally remains unavailable until its dedicated adapter is
    | implemented and verified separately.
    |
    */

    'fallback' => UnavailableFlightOfferRevalidationProvider::class,

    'providers' => [
        'unavailable' => UnavailableFlightOfferRevalidationProvider::class,
        'fixture' => FixtureFlightOfferRevalidationProvider::class,
        'duffel' => UnavailableFlightOfferRevalidationProvider::class,
    ],

];
