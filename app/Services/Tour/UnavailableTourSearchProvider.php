<?php

namespace App\Services\Tour;

use App\Contracts\Tour\TourSearchProvider;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UnavailableTourSearchProvider implements TourSearchProvider
{
    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        throw new ServiceUnavailableHttpException(
            60,
            'Tour service is not configured.'
        );
    }
}
