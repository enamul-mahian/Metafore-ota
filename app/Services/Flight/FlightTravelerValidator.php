<?php

namespace App\Services\Flight;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class FlightTravelerValidator
{
    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<int, array<string, mixed>>  $travelers
     */
    public function validate(
        array $criteria,
        array $travelers,
    ): void {
        $expected = [
            'adult' => (int) (
                $criteria['adults'] ?? 0
            ),
            'child' => (int) (
                $criteria['children'] ?? 0
            ),
            'infant' => (int) (
                $criteria['infants'] ?? 0
            ),
        ];

        $actual = [
            'adult' => 0,
            'child' => 0,
            'infant' => 0,
        ];

        foreach ($travelers as $traveler) {
            $type = (string) $traveler['type'];

            if (array_key_exists($type, $actual)) {
                $actual[$type]++;
            }
        }

        if ($actual !== $expected) {
            throw ValidationException::withMessages([
                'travelers' => (
                    'Traveler count and traveler types '
                    . 'must exactly match the selected '
                    . 'flight search.'
                ),
            ]);
        }

        $departureDate = CarbonImmutable::parse(
            (string) $criteria['departure_date']
        )->startOfDay();

        foreach ($travelers as $index => $traveler) {
            $dateOfBirth = CarbonImmutable::parse(
                (string) $traveler['date_of_birth']
            )->startOfDay();

            $type = (string) $traveler['type'];

            $isValidAge = match ($type) {
                'adult' => $dateOfBirth->lessThanOrEqualTo(
                    $departureDate->subYears(12)
                ),

                'child' => (
                    $dateOfBirth->greaterThan(
                        $departureDate->subYears(12)
                    )
                    && $dateOfBirth->lessThanOrEqualTo(
                        $departureDate->subYears(2)
                    )
                ),

                'infant' => $dateOfBirth->greaterThan(
                    $departureDate->subYears(2)
                ),

                default => false,
            };

            if ($isValidAge) {
                continue;
            }

            $message = match ($type) {
                'adult' => (
                    'Adult traveler must be at least '
                    . '12 years old on departure.'
                ),

                'child' => (
                    'Child traveler must be at least '
                    . '2 and under 12 years old on departure.'
                ),

                'infant' => (
                    'Infant traveler must be under '
                    . '2 years old on departure.'
                ),

                default => 'Traveler age is invalid.',
            };

            throw ValidationException::withMessages([
                "travelers.$index.date_of_birth" => $message,
            ]);
        }
    }
}
