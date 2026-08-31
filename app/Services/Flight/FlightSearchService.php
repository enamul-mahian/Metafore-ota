<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightSearchProvider;

class FlightSearchService
{
    public function __construct(
        private readonly FlightSearchProvider $provider
    ) {
    }

    /**
     * Search flight offers.
     *
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        return $this->provider->search($criteria);
    }
}
