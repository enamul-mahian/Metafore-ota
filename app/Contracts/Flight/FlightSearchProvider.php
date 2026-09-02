<?php

namespace App\Contracts\Flight;

interface FlightSearchProvider
{
    /**
     * Search flight offers using provider-neutral criteria.
     *
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array;
}
