<?php

namespace App\Services\Tour;

use App\Contracts\Tour\TourSearchProvider;
use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class ViatorTourSearchProvider implements TourSearchProvider
{
    public function search(array $criteria): array
    {
        $configuration = $this->configuration();
        $search = $this->searchCriteria($criteria);

        $body = [
            'searchTerm' => $search['destination'],
            'searchTypes' => [
                [
                    'searchType' => 'PRODUCTS',
                    'pagination' => [
                        'start' => 1,
                        'count' => $configuration['search_count'],
                    ],
                ],
            ],
            'currency' => $configuration['currency'],
        ];

        if ($search['travel_date'] !== null) {
            $body['productFiltering'] = [
                'dateRange' => [
                    'from' => $search['travel_date'],
                    'to' => $search['travel_date'],
                ],
            ];
        }

        try {
            $response = Http::baseUrl($configuration['base_url'])
                ->withHeaders([
                    'exp-api-key' => $configuration['api_key'],
                    'Accept-Language' => $configuration['locale'],
                    'Accept' => 'application/json;version='
                        .$configuration['api_version'],
                    'Content-Type' => 'application/json',
                ])
                ->connectTimeout($configuration['connect_timeout'])
                ->timeout($configuration['http_timeout'])
                ->post('/search/freetext', $body);
        } catch (ConnectionException) {
            throw $this->temporarilyUnavailable();
        }

        if ($response->failed()) {
            throw $this->temporarilyUnavailable();
        }

        $payload = $response->json();

        if (
            ! is_array($payload)
            || ! is_array($payload['products'] ?? null)
        ) {
            throw $this->malformed();
        }

        $results = [];

        foreach ($payload['products'] as $product) {
            $results[] = $this->normalizeProduct($product);
        }

        return $results;
    }

    /**
     * @return array{
     *     base_url: string,
     *     api_key: string,
     *     api_version: string,
     *     locale: string,
     *     currency: string,
     *     connect_timeout: int,
     *     http_timeout: int,
     *     search_count: int
     * }
     */
    private function configuration(): array
    {
        $baseUrl = rtrim(trim((string) config(
            'travel_services.services.tours.viator.base_url',
            ''
        )), '/');

        $apiKey = trim((string) config(
            'travel_services.services.tours.viator.api_key',
            ''
        ));

        $apiVersion = trim((string) config(
            'travel_services.services.tours.viator.api_version',
            ''
        ));

        $locale = trim((string) config(
            'travel_services.services.tours.viator.locale',
            ''
        ));

        $currency = strtoupper(trim((string) config(
            'travel_services.services.tours.viator.currency',
            ''
        )));

        if (
            $baseUrl === ''
            || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || parse_url($baseUrl, PHP_URL_SCHEME) !== 'https'
            || $apiKey === ''
            || $apiVersion !== '2.0'
            || preg_match('/^[a-z]{2}-[A-Z]{2}$/', $locale) !== 1
            || preg_match('/^[A-Z]{3}$/', $currency) !== 1
        ) {
            throw new ServiceUnavailableHttpException(
                null,
                'Tour search provider is not configured.'
            );
        }

        return [
            'base_url' => $baseUrl,
            'api_key' => $apiKey,
            'api_version' => $apiVersion,
            'locale' => $locale,
            'currency' => $currency,
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
            'search_count' => $this->boundedInteger(
                'search_count',
                1,
                50
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{
     *     destination: string,
     *     travel_date: string|null,
     *     travelers: int
     * }
     */
    private function searchCriteria(array $criteria): array
    {
        $destination = $criteria['destination'] ?? null;
        $travelDate = $criteria['travel_date'] ?? null;

        if (
            ! is_string($destination)
            || trim($destination) === ''
            || (
                $travelDate !== null
                && ! is_string($travelDate)
            )
        ) {
            throw $this->invalidCriteria();
        }

        if (
            is_string($travelDate)
            && ! $this->validDate($travelDate)
        ) {
            throw $this->invalidCriteria();
        }

        return [
            'destination' => trim($destination),
            'travel_date' => $travelDate,
            'travelers' => $this->criteriaInteger(
                $criteria,
                'travelers',
                1,
                12
            ),
        ];
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        return $date !== false
            && $date->format('Y-m-d') === $value;
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

    private function boundedInteger(
        string $key,
        int $minimum,
        int $maximum,
    ): int {
        $value = filter_var(
            config(
                "travel_services.services.tours.viator.{$key}"
            ),
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
                'Tour search provider is not configured.'
            );
        }

        return $value;
    }

    /**
     * @return array{
     *     reference: string,
     *     title: string,
     *     location: string,
     *     summary: string
     * }
     */
    private function normalizeProduct(mixed $product): array
    {
        if (! is_array($product)) {
            throw $this->malformed();
        }

        $reference = $product['productCode'] ?? null;
        $title = $product['title'] ?? null;
        $description = $product['description'] ?? null;

        if (
            ! is_string($reference)
            || trim($reference) === ''
            || ! is_string($title)
            || trim($title) === ''
            || (
                $description !== null
                && ! is_string($description)
            )
        ) {
            throw $this->malformed();
        }

        return [
            'reference' => trim($reference),
            'title' => trim($title),
            'location' => '',
            'summary' => is_string($description)
                ? trim($description)
                : '',
        ];
    }

    private function invalidCriteria(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Tour search criteria are invalid.'
        );
    }

    private function temporarilyUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Tour search provider is temporarily unavailable.'
        );
    }

    private function malformed(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Tour search provider returned an invalid response.'
        );
    }
}
