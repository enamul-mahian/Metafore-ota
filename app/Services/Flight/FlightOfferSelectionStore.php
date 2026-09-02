<?php

namespace App\Services\Flight;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Str;

final class FlightOfferSelectionStore
{
    private const TOKEN_LENGTH = 64;

    private const TTL_MINUTES = 15;

    public function __construct(
        private readonly CacheFactory $cache,
    ) {
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<int, array<string, mixed>>  $offers
     * @return array<int, array<string, mixed>>
     */
    public function attachSelectionTokens(
        int|string $userId,
        array $criteria,
        array $offers,
    ): array {
        return array_values(
            array_map(
                function (array $offer) use (
                    $userId,
                    $criteria,
                ): array {
                    $token = Str::random(
                        self::TOKEN_LENGTH
                    );

                    $this->cache
                        ->store()
                        ->put(
                            $this->key(
                                $userId,
                                $token
                            ),
                            [
                                'criteria' => $criteria,
                                'offer' => $offer,
                            ],
                            now()->addMinutes(
                                self::TTL_MINUTES
                            ),
                        );

                    return [
                        ...$offer,
                        'selection_token' => $token,
                    ];
                },
                $offers,
            )
        );
    }

    /**
     * @return array{
     *     criteria: array<string, mixed>,
     *     offer: array<string, mixed>
     * }|null
     */
    public function get(
        int|string $userId,
        string $token,
    ): ?array {
        $selection = $this->cache
            ->store()
            ->get(
                $this->key(
                    $userId,
                    $token
                )
            );

        return is_array($selection)
            ? $selection
            : null;
    }

    private function key(
        int|string $userId,
        string $token,
    ): string {
        return sprintf(
            'flight_offer_selection:%s:%s',
            $userId,
            hash('sha256', $token),
        );
    }
}
