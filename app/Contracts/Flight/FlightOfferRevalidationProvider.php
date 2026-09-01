<?php

namespace App\Contracts\Flight;

interface FlightOfferRevalidationProvider
{
    /**
     * Revalidate a trusted server-side normalized offer.
     *
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    public function revalidate(array $offer): array;
}
