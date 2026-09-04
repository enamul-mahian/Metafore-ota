<?php

namespace App\Services\Visa;

use App\Contracts\Visa\VisaInformationProvider;
use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class SherpaVisaInformationProvider implements VisaInformationProvider
{
    public function requirements(array $criteria): array
    {
        $configuration = $this->configuration();
        $trip = $this->tripCriteria($criteria);

        $payload = [
            'data' => [
                'type' => 'TRIP',
                'attributes' => [
                    'locale' => $configuration['locale'],
                    'currency' => $configuration['currency'],
                    'traveller' => [
                        'passports' => [
                            $trip['nationality'],
                        ],
                    ],
                    'travelNodes' => [
                        [
                            'type' => 'ORIGIN',
                            'locationCode' => $trip['origin_country'],
                            'departure' => [
                                'date' => $trip['departure_date'],
                                'time' => $trip['departure_time'],
                                'travelMode' => 'AIR',
                            ],
                        ],
                        [
                            'type' => 'DESTINATION',
                            'locationCode' => $trip['destination_country'],
                            'arrival' => [
                                'date' => $trip['arrival_date'],
                                'time' => $trip['arrival_time'],
                                'travelMode' => 'AIR',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::baseUrl($configuration['base_url'])
                ->withHeaders([
                    'x-api-key' => $configuration['api_key'],
                    'Content-Type' => 'application/vnd.api+json',
                    'Accept' => 'application/vnd.api+json',
                ])
                ->connectTimeout($configuration['connect_timeout'])
                ->timeout($configuration['http_timeout'])
                ->post(
                    '/v3/trips?include=procedure',
                    $payload
                );
        } catch (ConnectionException) {
            throw $this->temporarilyUnavailable();
        }

        if ($response->failed()) {
            throw $this->temporarilyUnavailable();
        }

        $responsePayload = $response->json();

        if (! is_array($responsePayload)) {
            throw $this->malformed();
        }

        $attributes = data_get(
            $responsePayload,
            'data.attributes'
        );

        if (! is_array($attributes)) {
            throw $this->malformed();
        }

        $summary = $this->optionalString(
            $attributes,
            'headline'
        );

        $informationGroups =
            $attributes['informationGroups'] ?? [];

        if (! is_array($informationGroups)) {
            throw $this->malformed();
        }

        $included = $responsePayload['included'] ?? [];

        if (! is_array($included)) {
            throw $this->malformed();
        }

        $proceduresById = $this->procedureIndex(
            $included
        );

        $procedureReferences =
            $this->visaProcedureReferences(
                $informationGroups
            );

        $requirements = [];
        $documents = [];

        foreach ($procedureReferences as $reference) {
            $procedure =
                $proceduresById[$reference] ?? null;

            if ($procedure === null) {
                continue;
            }

            $procedureAttributes =
                $procedure['attributes'] ?? null;

            if (! is_array($procedureAttributes)) {
                throw $this->malformed();
            }

            $title = $this->optionalString(
                $procedureAttributes,
                'title'
            );

            $description = $this->optionalString(
                $procedureAttributes,
                'description'
            );

            if ($title !== '' || $description !== '') {
                $requirements[] = match (true) {
                    $title !== '' && $description !== '' => $title.': '.$description,

                    $title !== '' => $title,

                    default => $description,
                };
            }

            $documentTypes =
                $procedureAttributes['documentTypes']
                    ?? [];

            if (! is_array($documentTypes)) {
                throw $this->malformed();
            }

            foreach ($documentTypes as $documentType) {
                if (
                    ! is_string($documentType)
                    || trim($documentType) === ''
                ) {
                    throw $this->malformed();
                }

                $documents[] = trim($documentType);
            }
        }

        return [
            'summary' => $summary,
            'requirements' => array_values(
                array_unique($requirements)
            ),
            'documents' => array_values(
                array_unique($documents)
            ),
        ];
    }

    /**
     * @return array{
     *     base_url: string,
     *     api_key: string,
     *     locale: string,
     *     currency: string,
     *     connect_timeout: int,
     *     http_timeout: int
     * }
     */
    private function configuration(): array
    {
        $baseUrl = rtrim(trim((string) config(
            'travel_services.services.visa.sherpa.base_url',
            ''
        )), '/');

        $apiKey = trim((string) config(
            'travel_services.services.visa.sherpa.api_key',
            ''
        ));

        $locale = trim((string) config(
            'travel_services.services.visa.sherpa.locale',
            ''
        ));

        $currency = strtoupper(trim((string) config(
            'travel_services.services.visa.sherpa.currency',
            ''
        )));

        if (
            $baseUrl === ''
            || filter_var(
                $baseUrl,
                FILTER_VALIDATE_URL
            ) === false
            || parse_url(
                $baseUrl,
                PHP_URL_SCHEME
            ) !== 'https'
            || $apiKey === ''
            || preg_match(
                '/^[a-z]{2}-[A-Z]{2}$/',
                $locale
            ) !== 1
            || ! in_array(
                $currency,
                ['USD', 'CAD', 'GBP', 'EUR'],
                true
            )
        ) {
            throw new ServiceUnavailableHttpException(
                null,
                'Visa information provider is not configured.'
            );
        }

        return [
            'base_url' => $baseUrl,
            'api_key' => $apiKey,
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
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{
     *     nationality: string,
     *     origin_country: string,
     *     destination_country: string,
     *     departure_date: string,
     *     departure_time: string,
     *     arrival_date: string,
     *     arrival_time: string
     * }
     */
    private function tripCriteria(array $criteria): array
    {
        $nationality = $this->iso3(
            $criteria['nationality'] ?? null
        );

        $origin = $this->iso3(
            $criteria['origin_country'] ?? null
        );

        $destination = $this->iso3(
            $criteria['destination_country'] ?? null
        );

        $departureDate =
            $criteria['departure_date'] ?? null;

        $departureTime =
            $criteria['departure_time'] ?? null;

        $arrivalDate =
            $criteria['arrival_date'] ?? null;

        $arrivalTime =
            $criteria['arrival_time'] ?? null;

        if (
            $origin === $destination
            || ! is_string($departureDate)
            || ! is_string($departureTime)
            || ! is_string($arrivalDate)
            || ! is_string($arrivalTime)
            || ! $this->validDate($departureDate)
            || ! $this->validDate($arrivalDate)
            || ! $this->validTime($departureTime)
            || ! $this->validTime($arrivalTime)
        ) {
            throw $this->invalidCriteria();
        }

        $departure = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $departureDate.' '.$departureTime
        );

        $arrival = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $arrivalDate.' '.$arrivalTime
        );

        if (
            $departure === false
            || $arrival === false
            || $arrival < $departure
        ) {
            throw $this->invalidCriteria();
        }

        return [
            'nationality' => $nationality,
            'origin_country' => $origin,
            'destination_country' => $destination,
            'departure_date' => $departureDate,
            'departure_time' => $departureTime,
            'arrival_date' => $arrivalDate,
            'arrival_time' => $arrivalTime,
        ];
    }

    private function iso3(mixed $value): string
    {
        if (
            ! is_string($value)
            || preg_match(
                '/^[A-Za-z]{3}$/',
                trim($value)
            ) !== 1
        ) {
            throw $this->invalidCriteria();
        }

        return strtoupper(trim($value));
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

    private function validTime(string $value): bool
    {
        $time = DateTimeImmutable::createFromFormat(
            '!H:i',
            $value
        );

        return $time !== false
            && $time->format('H:i') === $value;
    }

    /**
     * @param  array<int, mixed>  $included
     * @return array<string, array<string, mixed>>
     */
    private function procedureIndex(array $included): array
    {
        $index = [];

        foreach ($included as $item) {
            if (! is_array($item)) {
                throw $this->malformed();
            }

            $id = $item['id'] ?? null;
            $type = $item['type'] ?? null;

            if (
                ! is_string($id)
                || trim($id) === ''
                || ! is_string($type)
                || trim($type) === ''
            ) {
                throw $this->malformed();
            }

            if ($type === 'PROCEDURE') {
                $index[$id] = $item;
            }
        }

        return $index;
    }

    /**
     * @param  array<int, mixed>  $informationGroups
     * @return array<int, string>
     */
    private function visaProcedureReferences(
        array $informationGroups
    ): array {
        $references = [];

        foreach ($informationGroups as $group) {
            if (! is_array($group)) {
                throw $this->malformed();
            }

            $type = $group['type'] ?? null;

            if (! is_string($type)) {
                throw $this->malformed();
            }

            if ($type !== 'VISA_REQUIREMENTS') {
                continue;
            }

            $groupings = $group['groupings'] ?? [];

            if (! is_array($groupings)) {
                throw $this->malformed();
            }

            foreach ($groupings as $grouping) {
                if (! is_array($grouping)) {
                    throw $this->malformed();
                }

                $data = $grouping['data'] ?? [];

                if (! is_array($data)) {
                    throw $this->malformed();
                }

                foreach ($data as $reference) {
                    if (! is_array($reference)) {
                        throw $this->malformed();
                    }

                    $referenceType =
                        $reference['type'] ?? null;

                    $referenceId =
                        $reference['id'] ?? null;

                    if (
                        ! is_string($referenceType)
                        || ! is_string($referenceId)
                        || trim($referenceId) === ''
                    ) {
                        throw $this->malformed();
                    }

                    if ($referenceType === 'PROCEDURE') {
                        $references[] =
                            trim($referenceId);
                    }
                }
            }
        }

        return array_values(
            array_unique($references)
        );
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function optionalString(
        array $source,
        string $key
    ): string {
        $value = $source[$key] ?? null;

        if ($value === null) {
            return '';
        }

        if (! is_string($value)) {
            throw $this->malformed();
        }

        return trim($value);
    }

    private function boundedInteger(
        string $key,
        int $minimum,
        int $maximum
    ): int {
        $value = filter_var(
            config(
                "travel_services.services.visa.sherpa.{$key}"
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
                'Visa information provider is not configured.'
            );
        }

        return $value;
    }

    private function invalidCriteria(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Visa trip information is invalid.'
        );
    }

    private function temporarilyUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Visa information provider is temporarily unavailable.'
        );
    }

    private function malformed(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            null,
            'Visa information provider returned an invalid response.'
        );
    }
}
