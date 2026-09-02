<?php

namespace App\Services\Flight;

final class DuffelFlightOrderConfirmationNormalizer
{
    /**
     * Normalize only customer-safe confirmation fields from a Duffel order.
     *
     * Supplier IDs, passengers, payment data and arbitrary upstream fields
     * are deliberately excluded from this contract.
     *
     * @param  array<string, mixed>  $supplierOrder
     * @return array{
     *     booking_reference: string,
     *     itinerary: array<int, array{
     *         segments: array<int, array{
     *             origin: array{
     *                 iata_code: string,
     *                 name: string
     *             },
     *             destination: array{
     *                 iata_code: string,
     *                 name: string
     *             },
     *             departing_at: string,
     *             arriving_at: string,
     *             origin_terminal: string|null,
     *             destination_terminal: string|null,
     *             operating_carrier: array{
     *                 name: string
     *             },
     *             operating_carrier_flight_number: string|null,
     *             marketing_carrier: array{
     *                 name: string|null
     *             },
     *             marketing_carrier_flight_number: string|null
     *         }>
     *     }>
     * }|null
     */
    public function normalize(
        array $supplierOrder,
    ): ?array {
        $bookingReference =
            $this->safeString(
                $supplierOrder[
                    'booking_reference'
                ]
                    ?? null,
                64,
            );

        $slices =
            $supplierOrder[
                'slices'
            ]
                ?? null;

        if (
            $bookingReference === null
            || ! is_array($slices)
            || ! array_is_list($slices)
            || count($slices) < 1
            || count($slices) > 10
        ) {
            return null;
        }

        $itinerary = [];

        foreach ($slices as $slice) {
            if (! is_array($slice)) {
                return null;
            }

            $segments =
                $slice[
                    'segments'
                ]
                    ?? null;

            if (
                ! is_array($segments)
                || ! array_is_list($segments)
                || count($segments) < 1
                || count($segments) > 20
            ) {
                return null;
            }

            $safeSegments = [];

            foreach ($segments as $segment) {
                if (! is_array($segment)) {
                    return null;
                }

                $origin =
                    $this->airport(
                        $segment[
                            'origin'
                        ]
                            ?? null,
                    );

                $destination =
                    $this->airport(
                        $segment[
                            'destination'
                        ]
                            ?? null,
                    );

                $departingAt =
                    $this->dateTime(
                        $segment[
                            'departing_at'
                        ]
                            ?? null,
                    );

                $arrivingAt =
                    $this->dateTime(
                        $segment[
                            'arriving_at'
                        ]
                            ?? null,
                    );

                $operatingCarrier =
                    $segment[
                        'operating_carrier'
                    ]
                        ?? null;

                if (
                    ! is_array(
                        $operatingCarrier,
                    )
                ) {
                    return null;
                }

                $operatingCarrierName =
                    $this->safeString(
                        $operatingCarrier[
                            'name'
                        ]
                            ?? null,
                        255,
                    );

                if (
                    $origin === null
                    || $destination === null
                    || $departingAt === null
                    || $arrivingAt === null
                    || $operatingCarrierName === null
                ) {
                    return null;
                }

                $marketingCarrier =
                    $segment[
                        'marketing_carrier'
                    ]
                        ?? null;

                $marketingCarrierName =
                    is_array(
                        $marketingCarrier,
                    )
                        ? $this->safeString(
                            $marketingCarrier[
                                'name'
                            ]
                                ?? null,
                            255,
                        )
                        : null;

                $safeSegments[] = [
                    'origin' => $origin,

                    'destination' => $destination,

                    'departing_at' => $departingAt,

                    'arriving_at' => $arrivingAt,

                    'origin_terminal' => $this->safeString(
                        $segment[
                            'origin_terminal'
                        ]
                            ?? null,
                        32,
                    ),

                    'destination_terminal' => $this->safeString(
                        $segment[
                            'destination_terminal'
                        ]
                            ?? null,
                        32,
                    ),

                    'operating_carrier' => [
                    'name' => $operatingCarrierName,
                    ],

                    'operating_carrier_flight_number' => $this->safeString(
                        $segment[
                            'operating_carrier_flight_number'
                        ]
                            ?? null,
                        32,
                    ),

                    'marketing_carrier' => [
                    'name' => $marketingCarrierName,
                    ],

                    'marketing_carrier_flight_number' => $this->safeString(
                        $segment[
                            'marketing_carrier_flight_number'
                        ]
                            ?? null,
                        32,
                    ),
                ];
            }

            $itinerary[] = [
                'segments' => $safeSegments,
            ];
        }

        return [
            'booking_reference' => $bookingReference,

            'itinerary' => $itinerary,
        ];
    }

    /**
     * @return array{
     *     iata_code: string,
     *     name: string
     * }|null
     */
    private function airport(
        mixed $value,
    ): ?array {
        if (! is_array($value)) {
            return null;
        }

        $iataCode =
            $this->safeString(
                $value[
                    'iata_code'
                ]
                    ?? null,
                3,
            );

        $name =
            $this->safeString(
                $value[
                    'name'
                ]
                    ?? null,
                255,
            );

        if (
            $iataCode === null
            || $name === null
        ) {
            return null;
        }

        $iataCode =
            strtoupper(
                $iataCode,
            );

        if (
            preg_match(
                '/^[A-Z]{3}$/',
                $iataCode,
            ) !== 1
        ) {
            return null;
        }

        return [
            'iata_code' => $iataCode,

            'name' => $name,
        ];
    }

    private function dateTime(
        mixed $value,
    ): ?string {
        $value =
            $this->safeString(
                $value,
                64,
            );

        if ($value === null) {
            return null;
        }

        if (
            preg_match(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?$/',
                $value,
            ) !== 1
        ) {
            return null;
        }

        return $value;
    }

    private function safeString(
        mixed $value,
        int $maxLength,
    ): ?string {
        if (
            ! is_string($value)
            || $maxLength < 1
        ) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        if (
            $value === ''
            || strlen($value) > $maxLength
        ) {
            return null;
        }

        $controlCharacters =
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $value,
            );

        if ($controlCharacters !== 0) {
            return null;
        }

        return $value;
    }
}
