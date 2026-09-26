<?php

namespace App\Services\Flight;

/**
 * Turns untrusted query-string input (homepage search form, sign-in return
 * link) into safe values for pre-filling the flight search form. Anything
 * unknown or malformed is dropped; the search endpoint still validates fully.
 */
final class FlightSearchPrefill
{
    /**
     * Search fields that may be pre-filled, with the pattern each must match.
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        'trip_type' => '/^(round_trip|one_way)$/',
        'origin' => '/^[A-Za-z]{3}$/',
        'destination' => '/^[A-Za-z]{3}$/',
        'departure_date' => '/^\d{4}-\d{2}-\d{2}$/',
        'return_date' => '/^\d{4}-\d{2}-\d{2}$/',
        'cabin_class' => '/^(economy|premium_economy|business|first)$/',
        'adults' => '/^[1-9]$/',
        'children' => '/^[0-8]$/',
        'infants' => '/^[0-8]$/',
    ];

    /**
     * Keep only known search fields whose values match the expected format.
     *
     * @param  array<array-key, mixed>  $input
     * @return array{
     *     trip_type?: string,
     *     origin?: string,
     *     destination?: string,
     *     departure_date?: string,
     *     return_date?: string,
     *     cabin_class?: string,
     *     adults?: string,
     *     children?: string,
     *     infants?: string
     * }
     */
    public static function fromInput(array $input): array
    {
        $criteria = [];

        foreach (self::PATTERNS as $field => $pattern) {
            $value = $input[$field] ?? null;

            if (! is_string($value) || preg_match($pattern, $value) !== 1) {
                continue;
            }

            $criteria[$field] = in_array($field, ['origin', 'destination'], true)
                ? strtoupper($value)
                : $value;
        }

        return $criteria;
    }

    /**
     * Whether the pre-filled criteria are complete enough to search on load.
     *
     * @param  array<string, string>  $criteria
     */
    public static function canAutoSearch(array $criteria): bool
    {
        return isset($criteria['origin'], $criteria['destination'], $criteria['departure_date']);
    }
}
