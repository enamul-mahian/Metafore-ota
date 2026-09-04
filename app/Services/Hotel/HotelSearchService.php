<?php

namespace App\Services\Hotel;

use App\Contracts\Hotel\HotelSearchProvider;

class HotelSearchService
{
    public function __construct(
        private readonly HotelSearchProvider $provider
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, array{
     *     reference: string,
     *     name: string,
     *     location: string,
     *     summary: string
     * }>
     */
    public function search(array $criteria): array
    {
        $results = $this->provider->search($criteria);
        $normalized = [];

        foreach ($results as $result) {
            if (! is_array($result)) {
                continue;
            }

            $reference = $result['reference'] ?? null;
            $name = $result['name'] ?? null;

            if (
                ! is_string($reference) ||
                trim($reference) === '' ||
                ! is_string($name) ||
                trim($name) === ''
            ) {
                continue;
            }

            $normalized[] = [
                'reference' => $reference,
                'name' => $name,
                'location' => is_string($result['location'] ?? null)
                    ? $result['location']
                    : '',
                'summary' => is_string($result['summary'] ?? null)
                    ? $result['summary']
                    : '',
            ];
        }

        return $normalized;
    }
}
