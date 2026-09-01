<?php

namespace App\Services\Flight;

use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOrderRequestBuilder
{
    /**
     * Build a Duffel hold-order request body from a fully trusted,
     * server-side confirmation-intent snapshot.
     *
     * This builder performs no HTTP request and no persistence.
     *
     * @param  array<string, mixed>  $trustedConfirmationIntent
     * @return array{
     *     data: array{
     *         type: string,
     *         selected_offers: array<int, string>,
     *         passengers: array<int, array<string, string>>
     *     }
     * }
     */
    public function buildHold(
        array $trustedConfirmationIntent
    ): array {
        $offer = data_get(
            $trustedConfirmationIntent,
            'offer',
        );

        $travelers = data_get(
            $trustedConfirmationIntent,
            'travelers',
        );

        if (
            ! is_array($offer)
            || ! is_array($travelers)
            || ! array_is_list($travelers)
        ) {
            $this->failClosed();
        }

        $provider = strtolower(
            trim(
                (string) data_get(
                    $offer,
                    'provider',
                    '',
                ),
            ),
        );

        if ($provider !== 'duffel') {
            $this->failClosed();
        }

        $paymentRequiredBy =
            $offer[
                'payment_required_by'
            ]
            ?? null;

        if (
            ! $this->isFuturePaymentRequiredBy(
                $paymentRequiredBy,
            )
        ) {
            $this->failClosed();
        }

        $offerId = trim(
            (string) data_get(
                $offer,
                'id',
                '',
            ),
        );

        if (! $this->isSupplierId($offerId, 'off_')) {
            $this->failClosed();
        }

        if (
            ! array_key_exists(
                'requires_instant_payment',
                $offer,
            )
            || $offer['requires_instant_payment'] !== false
        ) {
            /*
             * Payment remains outside this boundary.
             * Only an explicitly hold-eligible trusted offer may proceed.
             */
            $this->failClosed();
        }

        $supplierPassengers = data_get(
            $offer,
            'passengers',
        );

        if (
            ! is_array($supplierPassengers)
            || ! array_is_list($supplierPassengers)
            || count($supplierPassengers) < 1
            || count($supplierPassengers) > 9
            || count($supplierPassengers) !== count($travelers)
        ) {
            $this->failClosed();
        }

        $passengers = [];
        $adultIndexes = [];
        $infantPassengerIds = [];

        foreach ($travelers as $index => $traveler) {
            $supplierPassenger =
                $supplierPassengers[$index]
                ?? null;

            if (
                ! is_array($traveler)
                || ! is_array($supplierPassenger)
            ) {
                $this->failClosed();
            }

            $supplierPassengerId = trim(
                (string) data_get(
                    $supplierPassenger,
                    'id',
                    '',
                ),
            );

            if (
                ! $this->isSupplierId(
                    $supplierPassengerId,
                    'pas_',
                )
            ) {
                $this->failClosed();
            }

            $travelerType =
                $this->normalizePassengerType(
                    data_get(
                        $traveler,
                        'type',
                    ),
                );

            $supplierPassengerType =
                $this->normalizePassengerType(
                    data_get(
                        $supplierPassenger,
                        'type',
                    ),
                );

            if (
                $travelerType === null
                || $supplierPassengerType === null
                || $travelerType !== $supplierPassengerType
            ) {
                $this->failClosed();
            }

            $passenger = [
                'id' => $supplierPassengerId,
                'title' =>
                    $this->requiredText(
                        $traveler,
                        'title',
                        20,
                    ),
                'given_name' =>
                    $this->requiredText(
                        $traveler,
                        'given_name',
                        20,
                    ),
                'family_name' =>
                    $this->requiredText(
                        $traveler,
                        'family_name',
                        20,
                    ),
                'born_on' =>
                    $this->requiredDate(
                        $traveler,
                        'date_of_birth',
                    ),
                'gender' =>
                    $this->requiredGender(
                        $traveler,
                    ),
                'email' =>
                    $this->requiredEmail(
                        $traveler,
                    ),
                'phone_number' =>
                    $this->requiredPhone(
                        $traveler,
                    ),
            ];

            $passengerIndex = count($passengers);

            if ($travelerType === 'adult') {
                $adultIndexes[] =
                    $passengerIndex;
            }

            if ($travelerType === 'infant') {
                $infantPassengerIds[] =
                    $supplierPassengerId;
            }

            $passengers[] = $passenger;
        }

        if (
            count($infantPassengerIds)
            > count($adultIndexes)
        ) {
            $this->failClosed();
        }

        foreach (
            $infantPassengerIds
            as $infantIndex => $infantPassengerId
        ) {
            $responsibleAdultIndex =
                $adultIndexes[$infantIndex]
                ?? null;

            if (! is_int($responsibleAdultIndex)) {
                $this->failClosed();
            }

            $passengers[$responsibleAdultIndex]
                ['infant_passenger_id'] =
                    $infantPassengerId;
        }

        return [
            'data' => [
                'type' => 'hold',
                'selected_offers' => [
                    $offerId,
                ],
                'passengers' => $passengers,
            ],
        ];
    }

    private function isFuturePaymentRequiredBy(
        mixed $value
    ): bool {
        if (! is_string($value)) {
            return false;
        }

        $paymentRequiredBy =
            trim($value);

        if ($paymentRequiredBy === '') {
            return false;
        }

        try {
            $deadline =
                new \DateTimeImmutable(
                    $paymentRequiredBy,
                );
        } catch (\Throwable) {
            return false;
        }

        return $deadline->getTimestamp()
            > time();
    }

    private function normalizePassengerType(
        mixed $value
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $type = strtolower(
            trim($value),
        );

        if ($type === 'infant_without_seat') {
            return 'infant';
        }

        if (
            ! in_array(
                $type,
                [
                    'adult',
                    'child',
                    'infant',
                ],
                true,
            )
        ) {
            return null;
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $source
     */
    private function requiredText(
        array $source,
        string $key,
        int $maximumLength
    ): string {
        $value = data_get(
            $source,
            $key,
        );

        if (! is_string($value)) {
            $this->failClosed();
        }

        $value = trim($value);

        if (
            $value === ''
            || mb_strlen($value) > $maximumLength
        ) {
            $this->failClosed();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $source
     */
    private function requiredDate(
        array $source,
        string $key
    ): string {
        $value = data_get(
            $source,
            $key,
        );

        if (! is_string($value)) {
            $this->failClosed();
        }

        $value = trim($value);

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value,
        );

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
        ) {
            $this->failClosed();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $traveler
     */
    private function requiredGender(
        array $traveler
    ): string {
        $gender = data_get(
            $traveler,
            'gender',
        );

        if (! is_string($gender)) {
            $this->failClosed();
        }

        $gender = strtolower(
            trim($gender),
        );

        if (
            ! in_array(
                $gender,
                [
                    'm',
                    'f',
                ],
                true,
            )
        ) {
            $this->failClosed();
        }

        return $gender;
    }

    /**
     * @param array<string, mixed> $traveler
     */
    private function requiredEmail(
        array $traveler
    ): string {
        $email = data_get(
            $traveler,
            'email',
        );

        if (! is_string($email)) {
            $this->failClosed();
        }

        $email = trim($email);

        if (
            $email === ''
            || strlen($email) > 254
            || filter_var(
                $email,
                FILTER_VALIDATE_EMAIL,
            ) === false
        ) {
            $this->failClosed();
        }

        return $email;
    }

    /**
     * @param array<string, mixed> $traveler
     */
    private function requiredPhone(
        array $traveler
    ): string {
        $phone = data_get(
            $traveler,
            'phone_number',
        );

        if (! is_string($phone)) {
            $this->failClosed();
        }

        $phone = trim($phone);

        if (
            preg_match(
                '/^\+[1-9][0-9]{6,14}$/',
                $phone,
            ) !== 1
        ) {
            $this->failClosed();
        }

        return $phone;
    }

    private function isSupplierId(
        string $value,
        string $prefix
    ): bool {
        if (
            $value === ''
            || strlen($value) > 255
            || ! str_starts_with(
                $value,
                $prefix,
            )
        ) {
            return false;
        }

        return preg_match(
            '/^[A-Za-z0-9_]+$/',
            $value,
        ) === 1;
    }

    private function failClosed(): never
    {
        throw new ServiceUnavailableHttpException(
            60,
            'Flight order is currently unavailable.',
        );
    }
}
