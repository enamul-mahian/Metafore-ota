<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightSearchProvider;
use DateInterval;
use DateTimeImmutable;

final class FixtureFlightSearchProvider implements FlightSearchProvider
{
    /**
     * Return deterministic demo offers for local development.
     *
     * This provider never performs an external HTTP request and its
     * results must never be represented as live airline inventory.
     *
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        return [
            $this->buildOffer(
                criteria: $criteria,
                offerIndex: 1,
                carrierName: 'Eagle Global Hub LTD Demo Air',
                flightNumber: 'D101',
                departureMinuteOffset: 0,
                priceMultiplier: 1.00
            ),

            $this->buildOffer(
                criteria: $criteria,
                offerIndex: 2,
                carrierName: 'Sandbox Airways',
                flightNumber: 'S202',
                departureMinuteOffset: 105,
                priceMultiplier: 1.09
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function buildOffer(
        array $criteria,
        int $offerIndex,
        string $carrierName,
        string $flightNumber,
        int $departureMinuteOffset,
        float $priceMultiplier
    ): array {
        $carrier = [
            'name' => $carrierName,
            'iata_code' => null,
            'logo_symbol_url' => null,
        ];

        return [
            'id' => $this->offerId(
                $criteria,
                $offerIndex
            ),

            'provider' => 'fixture',

            'total_amount' => $this->price(
                $criteria,
                $priceMultiplier
            ),

            'total_currency' => 'BDT',

            'expires_at' => null,

            'requires_instant_payment' => false,

            'owner' => $carrier,

            'slices' => $this->buildSlices(
                criteria: $criteria,
                carrier: $carrier,
                flightNumber: $flightNumber,
                departureMinuteOffset: $departureMinuteOffset,
                offerIndex: $offerIndex
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $carrier
     * @return array<int, array<string, mixed>>
     */
    private function buildSlices(
        array $criteria,
        array $carrier,
        string $flightNumber,
        int $departureMinuteOffset,
        int $offerIndex
    ): array {
        $slices = [
            $this->buildSlice(
                origin: (string) $criteria['origin'],
                destination: (string) $criteria['destination'],
                departureDate: (string) $criteria['departure_date'],
                carrier: $carrier,
                flightNumber: $flightNumber,
                departureMinuteOffset: $departureMinuteOffset,
                segmentKey: "{$offerIndex}-outbound"
            ),
        ];

        if (
            ($criteria['trip_type'] ?? null) === 'round_trip' &&
            ! empty($criteria['return_date'])
        ) {
            $slices[] = $this->buildSlice(
                origin: (string) $criteria['destination'],
                destination: (string) $criteria['origin'],
                departureDate: (string) $criteria['return_date'],
                carrier: $carrier,
                flightNumber: $flightNumber,
                departureMinuteOffset: $departureMinuteOffset + 35,
                segmentKey: "{$offerIndex}-return"
            );
        }

        return $slices;
    }

    /**
     * @param  array<string, mixed>  $carrier
     * @return array<string, mixed>
     */
    private function buildSlice(
        string $origin,
        string $destination,
        string $departureDate,
        array $carrier,
        string $flightNumber,
        int $departureMinuteOffset,
        string $segmentKey
    ): array {
        $departure = (new DateTimeImmutable(
            "{$departureDate} 08:30:00"
        ))->modify(
            "+{$departureMinuteOffset} minutes"
        );

        $arrival = $departure->add(
            new DateInterval('PT2H45M')
        );

        return [
            'origin' => $this->place($origin),
            'destination' => $this->place($destination),
            'duration' => 'PT2H45M',

            'segments' => [
                [
                    'id' => "fixture-segment-{$segmentKey}",

                    'departing_at' => $departure->format(
                        'Y-m-d\TH:i:s'
                    ),

                    'arriving_at' => $arrival->format(
                        'Y-m-d\TH:i:s'
                    ),

                    'duration' => 'PT2H45M',
                    'origin_terminal' => null,
                    'destination_terminal' => null,

                    'origin' => $this->place($origin),
                    'destination' => $this->place($destination),

                    'marketing_carrier' => $carrier,

                    'marketing_carrier_flight_number' => $flightNumber,

                    'operating_carrier' => $carrier,

                    'operating_carrier_flight_number' => $flightNumber,
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function place(string $iataCode): array
    {
        return [
            'name' => $iataCode,
            'iata_code' => $iataCode,
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function price(
        array $criteria,
        float $offerMultiplier
    ): string {
        $passengerCount = max(
            1,
            (int) ($criteria['adults'] ?? 0) +
            (int) ($criteria['children'] ?? 0) +
            (int) ($criteria['infants'] ?? 0)
        );

        $cabinMultiplier = match (
            $criteria['cabin_class'] ?? 'economy'
        ) {
            'premium_economy' => 1.35,
            'business' => 2.20,
            'first' => 3.40,
            default => 1.00,
        };

        $tripMultiplier = (
            ($criteria['trip_type'] ?? null) === 'round_trip'
        )
            ? 1.82
            : 1.00;

        $amount = 8500
            * $passengerCount
            * $cabinMultiplier
            * $tripMultiplier
            * $offerMultiplier;

        return number_format(
            $amount,
            2,
            '.',
            ''
        );
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function offerId(
        array $criteria,
        int $offerIndex
    ): string {
        $hash = hash(
            'sha256',
            serialize($criteria)."|{$offerIndex}"
        );

        return 'fixture-'.substr(
            $hash,
            0,
            16
        );
    }
}
