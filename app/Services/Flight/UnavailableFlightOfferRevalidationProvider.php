<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOfferRevalidationProvider;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class UnavailableFlightOfferRevalidationProvider implements FlightOfferRevalidationProvider
{
    /**
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    public function revalidate(array $offer): array
    {
        throw new ServiceUnavailableHttpException(
            60,
            'Flight offer revalidation is not available for this provider.',
        );
    }
}
