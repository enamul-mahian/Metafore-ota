<?php

namespace App\Services\Tour;

use App\Contracts\Tour\TourSearchProvider;

class TourSearchService
{
    public function __construct(
        private readonly TourSearchProvider $provider
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, array{
     *     reference: string,
     *     title: string,
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
            $title = $result['title'] ?? null;

            if (
                ! is_string($reference) ||
                trim($reference) === '' ||
                ! is_string($title) ||
                trim($title) === ''
            ) {
                continue;
            }

            $normalized[] = [
                'reference' => $reference,
                'title' => $title,
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
