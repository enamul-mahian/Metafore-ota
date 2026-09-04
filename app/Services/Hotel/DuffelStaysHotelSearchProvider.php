<?php

namespace App\Services\Hotel;

use App\Contracts\Hotel\HotelSearchProvider;
use App\Contracts\Travel\DestinationResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelStaysHotelSearchProvider implements HotelSearchProvider
{
    public function __construct(
        private readonly DestinationResolver $destinationResolver
    ) {}

    public function search(array $criteria): array
    {
        $configuration = $this->configuration();
        $search = $this->searchCriteria($criteria);
        $destination = $this->destinationResolver->resolve(
            $search['destination']
        );

        try {
            $response = Http::baseUrl($configuration['base_url'])
                ->withToken($configuration['access_token'])
                ->acceptJson()
                ->withHeaders([
                    'Duffel-Version' => $configuration['api_version'],
                ])
                ->connectTimeout($configuration['connect_timeout'])
                ->timeout($configuration['http_timeout'])
                ->post('/stays/search', [
                    'data' => [
                        'rooms' => $search['rooms'],
                        'location' => [
                            'radius' => $configuration['search_radius_km'],
                            'geographic_coordinates' => [
                                'latitude' => $destination['latitude'],
                                'longitude' => $destination['longitude'],
                            ],
                        ],
                        'guests' => array_fill(
                            0,
                            $search['adults'],
                            ['type' => 'adult']
                        ),
                        'check_in_date' => $search['check_in'],
                        'check_out_date' => $search['check_out'],
                    ],
                ]);
        } catch (ConnectionException) {
            throw $this->temporarilyUnavailable();
        }

        if ($response->failed()) {
            throw $this->temporarilyUnavailable();
        }

        $payload = $response->json();

        if (
            ! is_array($payload)
            || ! is_array(data_get($payload, 'data.results'))
        ) {
            throw $this->malformed();
        }

        $hotels = [];

        foreach (data_get($payload, 'data.results') as $result) {
            $hotels[] = $this->normalizeResult($result);
        }

        return $hotels;
    }

    /**
     * @return array{
     *     base_url: string,
     *     access_token: string,
     *     api_version: string,
     *     connect_timeout: int,
     *     http_timeout: int,
     *     search_radius_km: int
     * }
     */
    private function configuration(): array
    {
        $baseUrl = rtrim(trim((string) config(
            'travel_services.services.hotels.duffel.base_url',
            ''
        )), '/');
        $accessToken = trim((string) config(
            'travel_services.services.hotels.duffel.access_token',
            ''
        ));
        $apiVersion = trim((string) config(
            'travel_services.services.hotels.duffel.api_version',
            ''
        ));

        if (
            $baseUrl === ''
            || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || parse_url($baseUrl, PHP_URL_SCHEME) !== 'https'
            || $accessToken === ''
            || $apiVersion === ''
        ) {
            throw new ServiceUnavailableHttpException(
                null,
                'Hotel search provider is not configured.'
            );
        }

        return [
            'base_url' => $baseUrl,
            'access_token' => $accessToken,
            'api_version' => $apiVersion,
            'connect_timeout' => $this->boundedInteger(
                'connect_timeout',
                1,
                10
            ),
            'http_timeout' => $this->boundedInteger(
                'http_timeout',
                1,
                60
            ),
            'search_radius_km' => $this->boundedInteger(
                'search_radius_km',
                1,
                100
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{
     *     destination: string,
     *     check_in: string,
     *     check_out: string,
     *     adults: int,
     *     rooms: int
     * }
     */
    private function searchCriteria(array $criteria): array
    {
        $destination = $criteria['destination'] ?? null;
        $checkIn = $criteria['check_in'] ?? null;
        $checkOut = $criteria['check_out'] ?? null;

        if (
            ! is_string($destination)
            || trim($destination) === ''
            || ! is_string($checkIn)
            || $checkIn === ''
            || ! is_string($checkOut)
            || $checkOut === ''
        ) {
            throw $this->invalidCriteria();
        }

        return [
            'destination' => trim($destination),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => $this->criteriaInteger($criteria, 'adults', 1, 9),
            'rooms' => $this->criteriaInteger($criteria, 'rooms', 1, 5),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function criteriaInteger(
        array $criteria,
        string $key,
        int $minimum,
        int $maximum,
    ): int {
        $value = filter_var(
            $criteria[$key] ?? null,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => $minimum,
                    'max_range' => $maximum,
                ],
            ]
        );

        if ($value === false) {
            throw $this->invalidCriteria();
        }

        return $value;
    }

    private function boundedInteger(string $key, int $minimum, int $maximum): int
    {
        $value = filter_var(
            config("travel_services.services.hotels.duffel.{$key}"),
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => $minimum,
                    'max_range' => $maximum,
                ],
            ]
        );

        if ($value === false) {
            throw new ServiceUnavailableHttpException(
                null,
                'Hotel search provider is not configured.'
            );
        }

        return $value;
    }

    /**
     * @return array{
     *     reference: string,
     *     name: string,
     *     location: string,
     *     summary: string
     * }
     */
    private function normalizeResult(mixed $result): array
    {
        if (! is_array($result) || ! is_array($result['accommodation'] ?? null)) {
            throw $this->malformed();
        }

        $reference = $result['id'] ?? null;
        $accommodation = $result['accommodation'];
        $name = $accommodation['name'] ?? null;

        if (
            ! is_string($reference)
            || trim($reference) === ''
            || ! is_string($name)
            || trim($name) === ''
        ) {
            throw $this->malformed();
        }

        $description = $this->optionalString(
            $accommodation,
            'description'
        );
        $address = data_get($accommodation, 'location.address');

        if ($address !== null && ! is_array($address)) {
            throw $this->malformed();
        }

        $locationParts = [];

        if (is_array($address)) {
            foreach (['city_name', 'region', 'country_code'] as $key) {
                $value = $this->optionalString($address, $key);

                if ($value !== '') {
                    $locationParts[] = $value;
                }
            }
        }

        return [
            'reference' => trim($reference),
            'name' => trim($name),
            'location' => implode(', ', array_unique($locationParts)),
            'summary' => $description,
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function optionalString(array $source, string $key): string
    {
        $value = $source[$key] ?? null;

        if ($value === null) {
            return '';
        }

        if (! is_string($value)) {
            throw $this->malformed();
        }

        return trim($value);
    }

    private function invalidCriteria(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Hotel search criteria are invalid.'
        );
    }

    private function temporarilyUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Hotel search provider is temporarily unavailable.'
        );
    }

    private function malformed(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Hotel search provider returned an invalid response.'
        );
    }
}
