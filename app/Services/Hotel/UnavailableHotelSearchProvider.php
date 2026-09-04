<?php

namespace App\Services\Hotel;

use App\Contracts\Hotel\HotelSearchProvider;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UnavailableHotelSearchProvider implements HotelSearchProvider
{
    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        throw new ServiceUnavailableHttpException(
            60,
            'Hotel service is not configured.'
        );
    }
}
