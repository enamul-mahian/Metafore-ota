<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOfferRevalidationProvider;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FixtureFlightOfferRevalidationProvider implements FlightOfferRevalidationProvider
{
    /**
     * Revalidate a deterministic development fixture.
     *
     * Fixture offers are deliberately never represented as live or bookable.
     *
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    public function revalidate(array $offer): array
    {
        $provider = data_get(
            $offer,
            'provider',
        );

        $offerId = data_get(
            $offer,
            'id',
        );

        if (
            $provider !== 'fixture'
            || ! is_string($offerId)
            || trim($offerId) === ''
        ) {
            throw new ServiceUnavailableHttpException(
                60,
                'Fixture flight offer revalidation is unavailable for this offer.',
            );
        }

        return [
            'status' => 'demo_only',
            'provider' => 'fixture',
            'bookable' => false,
            'live_revalidation' => false,
            'offer' => $offer,
        ];
    }
}
