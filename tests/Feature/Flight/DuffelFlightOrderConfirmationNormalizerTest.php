<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOrderConfirmationNormalizer;
use Tests\TestCase;

final class DuffelFlightOrderConfirmationNormalizerTest extends TestCase
{
    public function test_normalizes_customer_safe_full_itinerary_without_supplier_identity_or_pii(): void
    {
        $result =
            $this->normalizer()
                ->normalize([
                    'id' => 'ord_private_supplier_order',

                    'booking_reference' => 'RZPNX8',

                    'passengers' => [
                        [
                            'given_name' => 'Private',

                            'family_name' => 'Passenger',
                        ],
                    ],

                    'payments' => [
                        [
                            'id' => 'pay_private_supplier_payment',
                        ],
                    ],

                    'private_supplier_field' => 'do-not-return',

                    'slices' => [
                        [
                            'id' => 'sli_private_outbound',

                            'segments' => [
                                [
                                    'id' => 'seg_private_1',

                                    'origin' => [
                                        'id' => 'arp_private_lhr',

                                        'iata_code' => 'LHR',

                                        'name' => 'Heathrow Airport',
                                    ],

                                    'destination' => [
                                        'id' => 'arp_private_jfk',

                                        'iata_code' => 'JFK',

                                        'name' => 'John F. Kennedy International Airport',
                                    ],

                                    'departing_at' => '2026-10-10T10:00:00',

                                    'arriving_at' => '2026-10-10T13:30:00',

                                    'origin_terminal' => '5',

                                    'destination_terminal' => '4',

                                    'operating_carrier' => [
                                        'id' => 'arl_private_operating',

                                        'iata_code' => 'BA',

                                        'name' => 'British Airways',

                                        'logo_lockup_url' => 'https://private.example.test/logo.svg',
                                    ],

                                    'operating_carrier_flight_number' => '4321',

                                    'marketing_carrier' => [
                                        'id' => 'arl_private_marketing',

                                        'iata_code' => 'BA',

                                        'name' => 'British Airways',
                                    ],

                                    'marketing_carrier_flight_number' => '1234',
                                ],
                            ],
                        ],

                        [
                            'id' => 'sli_private_return',

                            'segments' => [
                                [
                                    'id' => 'seg_private_2',

                                    'origin' => [
                                        'id' => 'arp_private_jfk',

                                        'iata_code' => 'jfk',

                                        'name' => 'John F. Kennedy International Airport',
                                    ],

                                    'destination' => [
                                        'id' => 'arp_private_lhr',

                                        'iata_code' => 'lhr',

                                        'name' => 'Heathrow Airport',
                                    ],

                                    'departing_at' => '2026-10-20T18:00:00',

                                    'arriving_at' => '2026-10-21T06:15:00',

                                    'operating_carrier' => [
                                        'id' => 'arl_private_return',

                                        'name' => 'Example Regional Airways',
                                    ],

                                    'operating_carrier_flight_number' => '789',

                                    'marketing_carrier' => [
                                        'name' => 'Example Marketing Airline',
                                    ],

                                    'marketing_carrier_flight_number' => '456',
                                ],
                            ],
                        ],
                    ],
                ]);

        $this->assertSame(
            [
                'booking_reference' => 'RZPNX8',

                'itinerary' => [
                    [
                        'segments' => [
                            [
                                'origin' => [
                                    'iata_code' => 'LHR',

                                    'name' => 'Heathrow Airport',
                                ],

                                'destination' => [
                                    'iata_code' => 'JFK',

                                    'name' => 'John F. Kennedy International Airport',
                                ],

                                'departing_at' => '2026-10-10T10:00:00',

                                'arriving_at' => '2026-10-10T13:30:00',

                                'origin_terminal' => '5',

                                'destination_terminal' => '4',

                                'operating_carrier' => [
                                    'name' => 'British Airways',
                                ],

                                'operating_carrier_flight_number' => '4321',

                                'marketing_carrier' => [
                                    'name' => 'British Airways',
                                ],

                                'marketing_carrier_flight_number' => '1234',
                            ],
                        ],
                    ],

                    [
                        'segments' => [
                            [
                                'origin' => [
                                    'iata_code' => 'JFK',

                                    'name' => 'John F. Kennedy International Airport',
                                ],

                                'destination' => [
                                    'iata_code' => 'LHR',

                                    'name' => 'Heathrow Airport',
                                ],

                                'departing_at' => '2026-10-20T18:00:00',

                                'arriving_at' => '2026-10-21T06:15:00',

                                'origin_terminal' => null,

                                'destination_terminal' => null,

                                'operating_carrier' => [
                                    'name' => 'Example Regional Airways',
                                ],

                                'operating_carrier_flight_number' => '789',

                                'marketing_carrier' => [
                                    'name' => 'Example Marketing Airline',
                                ],

                                'marketing_carrier_flight_number' => '456',
                            ],
                        ],
                    ],
                ],
            ],
            $result,
        );

        $serialized =
            json_encode(
                $result,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringNotContainsString(
            'ord_private_supplier_order',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'pay_private_supplier_payment',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'Private',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'seg_private',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'arl_private',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'do-not-return',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'private.example.test',
            $serialized,
        );
    }

    public function test_every_segment_requires_full_operating_carrier_name(): void
    {
        $order =
            $this->minimalOrder();

        unset(
            $order[
                'slices'
            ][0][
                'segments'
            ][0][
                'operating_carrier'
            ][
                'name'
            ],
        );

        $this->assertNull(
            $this->normalizer()
                ->normalize(
                    $order,
                ),
        );
    }

    public function test_malformed_or_empty_itinerary_fails_closed(): void
    {
        $missingSlices = [
            'booking_reference' => 'ABC123',
        ];

        $this->assertNull(
            $this->normalizer()
                ->normalize(
                    $missingSlices,
                ),
        );

        $emptySegments =
            $this->minimalOrder();

        $emptySegments[
            'slices'
        ][0][
            'segments'
        ] = [];

        $this->assertNull(
            $this->normalizer()
                ->normalize(
                    $emptySegments,
                ),
        );

        $invalidAirport =
            $this->minimalOrder();

        $invalidAirport[
            'slices'
        ][0][
            'segments'
        ][0][
            'origin'
        ][
            'iata_code'
        ] = 'INVALID';

        $this->assertNull(
            $this->normalizer()
                ->normalize(
                    $invalidAirport,
                ),
        );
    }

    public function test_invalid_booking_reference_or_datetime_fails_closed(): void
    {
        $invalidReference =
            $this->minimalOrder();

        $invalidReference[
            'booking_reference'
        ] = "ABC\n123";

        $this->assertNull(
            $this->normalizer()
                ->normalize(
                    $invalidReference,
                ),
        );

        $invalidDateTime =
            $this->minimalOrder();

        $invalidDateTime[
            'slices'
        ][0][
            'segments'
        ][0][
            'departing_at'
        ] = 'not-a-datetime';

        $this->assertNull(
            $this->normalizer()
                ->normalize(
                    $invalidDateTime,
                ),
        );
    }

    private function normalizer(): DuffelFlightOrderConfirmationNormalizer
    {
        return app(
            DuffelFlightOrderConfirmationNormalizer::class,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalOrder(): array
    {
        return [
            'booking_reference' => 'ABC123',

            'slices' => [
                [
                    'segments' => [
                        [
                            'origin' => [
                                'iata_code' => 'DAC',

                                'name' => 'Hazrat Shahjalal International Airport',
                            ],

                            'destination' => [
                                'iata_code' => 'DXB',

                                'name' => 'Dubai International Airport',
                            ],

                            'departing_at' => '2026-11-01T08:30:00',

                            'arriving_at' => '2026-11-01T12:15:00',

                            'operating_carrier' => [
                                'name' => 'Example Airways',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
