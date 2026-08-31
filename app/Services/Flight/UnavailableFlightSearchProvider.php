<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightSearchProvider;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UnavailableFlightSearchProvider implements FlightSearchProvider
{
    /**
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        throw new ServiceUnavailableHttpException(
            60,
            'Flight search provider is not configured.'
        );
    }
}
