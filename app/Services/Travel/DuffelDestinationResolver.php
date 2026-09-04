<?php

namespace App\Services\Travel;

use App\Contracts\Travel\DestinationResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelDestinationResolver implements DestinationResolver
{
    public function resolve(string $destination): array
    {
        $query = trim($destination);

        if ($query === '') {
            throw $this->unresolved();
        }

        $configuration = $this->configuration();

        try {
            $response = Http::baseUrl($configuration['base_url'])
                ->withToken($configuration['access_token'])
                ->acceptJson()
                ->withHeaders([
                    'Duffel-Version' => $configuration['api_version'],
                ])
                ->connectTimeout($configuration['connect_timeout'])
                ->timeout($configuration['http_timeout'])
                ->get('/places/suggestions', [
                    'query' => $query,
                ]);
        } catch (ConnectionException) {
            throw new ServiceUnavailableHttpException(
                null,
                'Hotel destination service is temporarily unavailable.'
            );
        }

        if ($response->failed()) {
            throw new ServiceUnavailableHttpException(
                null,
                'Hotel destination service is temporarily unavailable.'
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
            throw $this->malformed();
        }

        $cities = [];

        foreach ($payload['data'] as $place) {
            if (! is_array($place)) {
                throw $this->malformed();
            }

            $type = $place['type'] ?? null;

            if (! is_string($type) || ! in_array($type, ['city', 'airport'], true)) {
                throw $this->malformed();
            }

            if ($type !== 'city') {
                continue;
            }

            $name = $place['name'] ?? null;
            $latitude = $place['latitude'] ?? null;
            $longitude = $place['longitude'] ?? null;

            if (
                ! is_string($name)
                || trim($name) === ''
                || ! $this->validCoordinate($latitude, -90, 90)
                || ! $this->validCoordinate($longitude, -180, 180)
            ) {
                throw $this->unresolved();
            }

            $cities[] = [
                'name' => trim($name),
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ];
        }

        if (count($cities) !== 1) {
            throw $this->unresolved();
        }

        return $cities[0];
    }

    /**
     * @return array{
     *     base_url: string,
     *     access_token: string,
     *     api_version: string,
     *     connect_timeout: int,
     *     http_timeout: int
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
                'Hotel destination resolver is not configured.'
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
        ];
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
                'Hotel destination resolver is not configured.'
            );
        }

        return $value;
    }

    private function validCoordinate(mixed $value, int $minimum, int $maximum): bool
    {
        return (is_int($value) || is_float($value))
            && is_finite((float) $value)
            && $value >= $minimum
            && $value <= $maximum;
    }

    private function unresolved(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Hotel destination could not be resolved.'
        );
    }

    private function malformed(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Hotel destination service returned an invalid response.'
        );
    }
}
