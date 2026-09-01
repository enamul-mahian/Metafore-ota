<?php

use App\Services\Flight\UnavailableFlightOrderProvider;

return [
    'providers' => [
        'fixture' => UnavailableFlightOrderProvider::class,
        'duffel' => UnavailableFlightOrderProvider::class,
    ],
];
