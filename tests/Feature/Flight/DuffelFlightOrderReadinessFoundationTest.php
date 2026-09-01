<?php

namespace Tests\Feature\Flight;

use App\Http\Requests\Flight\ValidateFlightTravelersRequest;
use App\Services\Flight\DuffelFlightOfferRevalidationProvider;
use App\Services\Flight\DuffelFlightOrderRequestBuilder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DuffelFlightOrderReadinessFoundationTest extends TestCase
{
    public function test_traveler_request_accepts_valid_order_ready_fields(): void
    {
        $request =
            new ValidateFlightTravelersRequest();

        $rules =
            $request->rules();

        $requiredKeys = [
            'travelers.*.gender',
            'travelers.*.email',
            'travelers.*.phone_number',
        ];

        $focusedRules = [];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $rules,
            );

            $focusedRules[$key] =
                $rules[$key];
        }

        $validator =
            Validator::make(
                [
                    'travelers' => [
                        [
                            'gender' => 'm',
                            'email' =>
                                'tony@example.com',
                            'phone_number' =>
                                '+14155550101',
                        ],
                    ],
                ],
                $focusedRules,
            );

        $this->assertFalse(
            $validator->fails(),
            json_encode(
                $validator
                    ->errors()
                    ->toArray(),
                JSON_THROW_ON_ERROR,
            ),
        );

        $validated =
            $validator->validated();

        $this->assertSame(
            'm',
            data_get(
                $validated,
                'travelers.0.gender',
            ),
        );

        $this->assertSame(
            'tony@example.com',
            data_get(
                $validated,
                'travelers.0.email',
            ),
        );

        $this->assertSame(
            '+14155550101',
            data_get(
                $validated,
                'travelers.0.phone_number',
            ),
        );
    }

    public function test_traveler_request_rejects_invalid_order_ready_fields(): void
    {
        $rules =
            (new ValidateFlightTravelersRequest())
                ->rules();

        $focusedRules = [
            'travelers.*.gender' =>
                $rules['travelers.*.gender'],

            'travelers.*.email' =>
                $rules['travelers.*.email'],

            'travelers.*.phone_number' =>
                $rules['travelers.*.phone_number'],
        ];

        $validator =
            Validator::make(
                [
                    'travelers' => [
                        [
                            'gender' => 'x',
                            'email' =>
                                'not-an-email',
                            'phone_number' =>
                                '01700000000',
                        ],
                    ],
                ],
                $focusedRules,
            );

        $this->assertTrue(
            $validator->fails(),
        );

        $this->assertArrayHasKey(
            'travelers.0.gender',
            $validator
                ->errors()
                ->toArray(),
        );

        $this->assertArrayHasKey(
            'travelers.0.email',
            $validator
                ->errors()
                ->toArray(),
        );

        $this->assertArrayHasKey(
            'travelers.0.phone_number',
            $validator
                ->errors()
                ->toArray(),
        );
    }

    public function test_duffel_revalidation_carries_only_safe_supplier_passenger_ids(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        Http::fake([
            '*' => Http::response(
                [
                    'data' => [
                        'id' =>
                            'off_trusted_1',
                        'total_amount' =>
                            '125.50',
                        'total_currency' =>
                            'USD',
                        'expires_at' =>
                            '2030-01-01T00:00:00Z',
                        'payment_requirements' => [
                            'requires_instant_payment' =>
                                false,
                        ],
                        'passengers' => [
                            [
                                'id' =>
                                    'pas_adult_1',
                                'type' =>
                                    'adult',
                            ],
                            [
                                'id' =>
                                    'pas_child_1',
                                'type' =>
                                    'child',
                            ],
                            [
                                'id' =>
                                    'pas_infant_1',
                                'type' =>
                                    'infant_without_seat',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $result =
            app(
                DuffelFlightOfferRevalidationProvider::class
            )->revalidate(
                $this->trustedOffer(),
            );

        $this->assertSame(
            [
                [
                    'id' => 'pas_adult_1',
                    'type' => 'adult',
                ],
                [
                    'id' => 'pas_child_1',
                    'type' => 'child',
                ],
                [
                    'id' => 'pas_infant_1',
                    'type' => 'infant',
                ],
            ],
            data_get(
                $result,
                'offer.passengers',
            ),
        );

        $this->assertFalse(
            data_get(
                $result,
                'offer.requires_instant_payment',
            ),
        );

        Http::assertSentCount(1);
    }

    public function test_duffel_revalidation_drops_stale_passengers_when_supplier_passengers_are_unsafe(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        Http::fake([
            '*' => Http::response(
                [
                    'data' => [
                        'id' =>
                            'off_trusted_1',
                        'total_amount' =>
                            '125.50',
                        'total_currency' =>
                            'USD',
                        'expires_at' =>
                            '2030-01-01T00:00:00Z',
                        'payment_requirements' => [
                            'requires_instant_payment' =>
                                false,
                        ],
                        'passengers' => [
                            [
                                'id' =>
                                    'client-controlled-id',
                                'type' =>
                                    'adult',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $trustedOffer =
            $this->trustedOffer();

        $trustedOffer['passengers'] = [
            [
                'id' => 'pas_stale_1',
                'type' => 'adult',
            ],
        ];

        $result =
            app(
                DuffelFlightOfferRevalidationProvider::class
            )->revalidate(
                $trustedOffer,
            );

        $this->assertArrayNotHasKey(
            'passengers',
            $result['offer'],
        );

        Http::assertSentCount(1);
    }

    public function test_order_builder_normalizes_duffel_infant_without_seat_type(): void
    {
        $intent = [
            'offer' => [
                'id' =>
                    'off_safe_offer_1',
                'provider' =>
                    'duffel',
                'total_amount' =>
                    '125.50',
                'currency' =>
                    'USD',
                'requires_instant_payment' =>
                    false,
                'passengers' => [
                    [
                        'id' =>
                            'pas_adult_1',
                        'type' =>
                            'adult',
                    ],
                    [
                        'id' =>
                            'pas_infant_1',
                        'type' =>
                            'infant_without_seat',
                    ],
                ],
            ],

            'travelers' => [
                [
                    'type' =>
                        'adult',
                    'title' =>
                        'mr',
                    'given_name' =>
                        'Tony',
                    'family_name' =>
                        'Stark',
                    'date_of_birth' =>
                        '1980-07-24',
                    'gender' =>
                        'm',
                    'email' =>
                        'tony@example.com',
                    'phone_number' =>
                        '+14155550101',
                ],
                [
                    'type' =>
                        'infant',
                    'title' =>
                        'miss',
                    'given_name' =>
                        'Baby',
                    'family_name' =>
                        'Stark',
                    'date_of_birth' =>
                        '2026-01-01',
                    'gender' =>
                        'f',
                    'email' =>
                        'baby@example.com',
                    'phone_number' =>
                        '+14155550102',
                ],
            ],
        ];

        $result =
            app(
                DuffelFlightOrderRequestBuilder::class
            )->buildHold(
                $intent,
            );

        $this->assertSame(
            'pas_infant_1',
            data_get(
                $result,
                'data.passengers.0.infant_passenger_id',
            ),
        );

        $this->assertSame(
            'pas_infant_1',
            data_get(
                $result,
                'data.passengers.1.id',
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function trustedOffer(): array
    {
        return [
            'id' =>
                'off_trusted_1',
            'provider' =>
                'duffel',
            'total_amount' =>
                '125.50',
            'currency' =>
                'USD',
            'owner' => [
                'code' =>
                    'BA',
                'name' =>
                    'British Airways',
            ],
        ];
    }
}
