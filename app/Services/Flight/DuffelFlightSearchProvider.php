<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightSearchProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightSearchProvider implements FlightSearchProvider
{
    public function search(array $criteria): array
    {
        $accessToken = trim(
            (string) config('flight.duffel.access_token', '')
        );

        if ($accessToken === '') {
            throw new ServiceUnavailableHttpException(
                null,
                'Flight search provider is not configured.'
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'flight.duffel.base_url',
                'https://api.duffel.com'
            ),
            '/'
        );

        $apiVersion = (string) config(
            'flight.duffel.api_version',
            'v2'
        );

        $httpTimeout = max(
            1,
            (int) config(
                'flight.duffel.http_timeout',
                30
            )
        );

        $supplierTimeout = min(
            60000,
            max(
                2000,
                (int) config(
                    'flight.duffel.supplier_timeout_ms',
                    20000
                )
            )
        );

        try {
            $response = Http::baseUrl($baseUrl)
                ->withToken($accessToken)
                ->acceptJson()
                ->withHeaders([
                    'Duffel-Version' => $apiVersion,
                ])
                ->timeout($httpTimeout)
                ->post(
                    '/air/offer_requests'
                    . '?return_offers=true'
                    . '&supplier_timeout='
                    . $supplierTimeout,
                    [
                        'data' => $this->buildPayload(
                            $criteria
                        ),
                    ]
                );
        } catch (ConnectionException $exception) {
            throw new ServiceUnavailableHttpException(
                null,
                'Flight search provider is temporarily unavailable.',
                $exception
            );
        }

        if ($response->failed()) {
            throw new ServiceUnavailableHttpException(
                null,
                'Flight search provider is temporarily unavailable.'
            );
        }

        $offers = $response->json(
            'data.offers',
            []
        );

        if (! is_array($offers)) {
            return [];
        }

        $normalized = [];

        foreach ($offers as $offer) {
            if (! is_array($offer)) {
                continue;
            }

            $normalized[] = $this->normalizeOffer(
                $offer
            );
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    private function buildPayload(array $criteria): array
    {
        $slices = [
            [
                'origin' => $criteria['origin'],
                'destination' => $criteria['destination'],
                'departure_date' => $criteria['departure_date'],
            ],
        ];

        if (
            ($criteria['trip_type'] ?? null) === 'round_trip' &&
            ! empty($criteria['return_date'])
        ) {
            $slices[] = [
                'origin' => $criteria['destination'],
                'destination' => $criteria['origin'],
                'departure_date' => $criteria['return_date'],
            ];
        }

        return [
            'slices' => $slices,
            'passengers' => $this->buildPassengers(
                $criteria
            ),
            'cabin_class' => $criteria['cabin_class'],
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, string>>
     */
    private function buildPassengers(array $criteria): array
    {
        $passengers = [];

        for (
            $index = 0;
            $index < (int) ($criteria['adults'] ?? 0);
            $index++
        ) {
            $passengers[] = [
                'type' => 'adult',
            ];
        }

        for (
            $index = 0;
            $index < (int) ($criteria['children'] ?? 0);
            $index++
        ) {
            $passengers[] = [
                'type' => 'child',
            ];
        }

        for (
            $index = 0;
            $index < (int) ($criteria['infants'] ?? 0);
            $index++
        ) {
            $passengers[] = [
                'type' => 'infant_without_seat',
            ];
        }

        return $passengers;
    }

    /**
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    private function normalizeOffer(array $offer): array
    {
        return [
            'id' => (string) ($offer['id'] ?? ''),
            'provider' => 'duffel',

            'total_amount' => (string) (
                $offer['total_amount'] ?? ''
            ),

            'total_currency' => (string) (
                $offer['total_currency'] ?? ''
            ),

            'expires_at' => $offer['expires_at'] ?? null,

            'requires_instant_payment' => data_get(
                $offer,
                'payment_requirements.requires_instant_payment'
            ),

            'owner' => $this->normalizeCarrier(
                $offer['owner'] ?? null
            ),

            'slices' => $this->normalizeSlices(
                $offer['slices'] ?? []
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSlices(mixed $slices): array
    {
        if (! is_array($slices)) {
            return [];
        }

        $normalized = [];

        foreach ($slices as $slice) {
            if (! is_array($slice)) {
                continue;
            }

            $normalized[] = [
                'origin' => $this->normalizePlace(
                    $slice['origin'] ?? null
                ),

                'destination' => $this->normalizePlace(
                    $slice['destination'] ?? null
                ),

                'duration' => $slice['duration'] ?? null,

                'segments' => $this->normalizeSegments(
                    $slice['segments'] ?? []
                ),
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSegments(mixed $segments): array
    {
        if (! is_array($segments)) {
            return [];
        }

        $normalized = [];

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $normalized[] = [
                'id' => $segment['id'] ?? null,

                'departing_at' => $segment[
                    'departing_at'
                ] ?? null,

                'arriving_at' => $segment[
                    'arriving_at'
                ] ?? null,

                'duration' => $segment['duration'] ?? null,

                'origin_terminal' => $segment[
                    'origin_terminal'
                ] ?? null,

                'destination_terminal' => $segment[
                    'destination_terminal'
                ] ?? null,

                'origin' => $this->normalizePlace(
                    $segment['origin'] ?? null
                ),

                'destination' => $this->normalizePlace(
                    $segment['destination'] ?? null
                ),

                'marketing_carrier' => $this->normalizeCarrier(
                    $segment['marketing_carrier'] ?? null
                ),

                'marketing_carrier_flight_number' => $segment[
                    'marketing_carrier_flight_number'
                ] ?? null,

                'operating_carrier' => $this->normalizeCarrier(
                    $segment['operating_carrier'] ?? null
                ),

                'operating_carrier_flight_number' => $segment[
                    'operating_carrier_flight_number'
                ] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePlace(mixed $place): array
    {
        if (! is_array($place)) {
            return [
                'name' => null,
                'iata_code' => null,
            ];
        }

        return [
            'name' => $place['name'] ?? null,
            'iata_code' => $place['iata_code'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCarrier(mixed $carrier): array
    {
        if (! is_array($carrier)) {
            return [
                'name' => null,
                'iata_code' => null,
                'logo_symbol_url' => null,
            ];
        }

        return [
            'name' => $carrier['name'] ?? null,
            'iata_code' => $carrier['iata_code'] ?? null,
            'logo_symbol_url' => $carrier[
                'logo_symbol_url'
            ] ?? null,
        ];
    }
}
