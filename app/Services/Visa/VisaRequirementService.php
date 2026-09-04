<?php

namespace App\Services\Visa;

use App\Contracts\Visa\VisaInformationProvider;

class VisaRequirementService
{
    public function __construct(
        private readonly VisaInformationProvider $provider
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{
     *     summary: string,
     *     requirements: array<int, string>,
     *     documents: array<int, string>
     * }
     */
    public function requirements(array $criteria): array
    {
        $response = $this->provider->requirements($criteria);

        return [
            'summary' => is_string($response['summary'] ?? null)
                ? $response['summary']
                : '',
            'requirements' => $this->stringList(
                $response['requirements'] ?? null
            ),
            'documents' => $this->stringList(
                $response['documents'] ?? null
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter(
            $items,
            static fn (mixed $item): bool => is_string($item) && trim($item) !== ''
        ));
    }
}
