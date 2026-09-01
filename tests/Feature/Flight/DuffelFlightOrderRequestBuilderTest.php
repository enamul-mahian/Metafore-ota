<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOrderRequestBuilder;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

class DuffelFlightOrderRequestBuilderTest extends TestCase
{
    public function test_complete_trusted_snapshot_builds_hold_request_without_payment(): void
    {
        $result =
            app(DuffelFlightOrderRequestBuilder::class)
                ->buildHold(
                    $this->completeIntent(),
                );

        $this->assertSame(
            'hold',
            data_get(
                $result,
                'data.type',
            ),
        );

        $this->assertSame(
            [
                'off_safe_offer_1',
            ],
            data_get(
                $result,
                'data.selected_offers',
            ),
        );

        $this->assertArrayNotHasKey(
            'payments',
            $result['data'],
        );

        $this->assertSame(
            [
                'id' => 'pas_adult_1',
                'title' => 'mr',
                'given_name' => 'Tony',
                'family_name' => 'Stark',
                'born_on' => '1980-07-24',
                'gender' => 'm',
                'email' => 'tony@example.test',
                'phone_number' => '+14155550101',
            ],
            $result['data']['passengers'][0],
        );

        $this->assertSame(
            [
                'id' => 'pas_child_1',
                'title' => 'miss',
                'given_name' => 'Morgan',
                'family_name' => 'Stark',
                'born_on' => '2015-08-24',
                'gender' => 'f',
                'email' => 'morgan@example.test',
                'phone_number' => '+14155550102',
            ],
            $result['data']['passengers'][1],
        );
    }

    public function test_infant_is_linked_to_a_unique_responsible_adult(): void
    {
        $intent =
            $this->completeIntent();

        $intent['offer']['passengers'][] = [
            'id' => 'pas_infant_1',
            'type' => 'infant',
        ];

        $intent['travelers'][] = [
            'type' => 'infant',
            'title' => 'miss',
            'given_name' => 'Baby',
            'family_name' => 'Stark',
            'date_of_birth' => '2026-01-01',
            'gender' => 'f',
            'email' => 'baby@example.test',
            'phone_number' => '+14155550103',
        ];

        $result =
            app(DuffelFlightOrderRequestBuilder::class)
                ->buildHold($intent);

        $this->assertSame(
            'pas_infant_1',
            $result['data']['passengers'][0]
                ['infant_passenger_id'],
        );

        $this->assertArrayNotHasKey(
            'infant_passenger_id',
            $result['data']['passengers'][1],
        );

        $this->assertArrayNotHasKey(
            'infant_passenger_id',
            $result['data']['passengers'][2],
        );
    }

    public function test_non_duffel_provider_fails_closed(): void
    {
        $intent =
            $this->completeIntent();

        $intent['offer']['provider'] =
            'fixture';

        $this->expectUnavailable();

        app(DuffelFlightOrderRequestBuilder::class)
            ->buildHold($intent);
    }

    public function test_instant_payment_offer_fails_closed(): void
    {
        $intent =
            $this->completeIntent();

        $intent['offer']
            ['requires_instant_payment'] =
                true;

        $this->expectUnavailable();

        app(DuffelFlightOrderRequestBuilder::class)
            ->buildHold($intent);
    }

    public function test_missing_hold_eligibility_flag_fails_closed(): void
    {
        $intent =
            $this->completeIntent();

        unset(
            $intent['offer']
                ['requires_instant_payment'],
        );

        $this->expectUnavailable();

        app(DuffelFlightOrderRequestBuilder::class)
            ->buildHold($intent);
    }

    public function test_current_snapshot_without_supplier_passengers_fails_closed(): void
    {
        $intent =
            $this->completeIntent();

        unset(
            $intent['offer']['passengers'],
        );

        $this->expectUnavailable();

        app(DuffelFlightOrderRequestBuilder::class)
            ->buildHold($intent);
    }

    public function test_supplier_passenger_count_must_match_travelers(): void
    {
        $intent =
            $this->completeIntent();

        array_pop(
            $intent['offer']['passengers'],
        );

        $this->expectUnavailable();

        app(DuffelFlightOrderRequestBuilder::class)
            ->buildHold($intent);
    }

    public function test_supplier_passenger_type_must_match_traveler_type(): void
    {
        $intent =
            $this->completeIntent();

        $intent['offer']['passengers'][1]
            ['type'] =
                'adult';

        $this->expectUnavailable();

        app(DuffelFlightOrderRequestBuilder::class)
            ->buildHold($intent);
    }

    public function test_missing_order_required_contact_data_fails_closed(): void
    {
        foreach (
            [
                'gender',
                'email',
                'phone_number',
            ]
            as $requiredField
        ) {
            $intent =
                $this->completeIntent();

            unset(
                $intent['travelers'][0]
                    [$requiredField],
            );

            try {
                app(DuffelFlightOrderRequestBuilder::class)
                    ->buildHold($intent);

                $this->fail(
                    "Missing {$requiredField} should fail closed.",
                );
            } catch (
                ServiceUnavailableHttpException $exception
            ) {
                $this->assertSame(
                    'Flight order is currently unavailable.',
                    $exception->getMessage(),
                );
            }
        }
    }

    public function test_invalid_supplier_passenger_id_fails_closed(): void
    {
        $intent =
            $this->completeIntent();

        $intent['offer']['passengers'][0]
            ['id'] =
                'client-controlled-id';

        $this->expectUnavailable();

        app(DuffelFlightOrderRequestBuilder::class)
            ->buildHold($intent);
    }

    public function test_invalid_offer_id_fails_closed(): void
    {
        $intent =
            $this->completeIntent();

        $intent['offer']['id'] =
            'not-a-duffel-offer';

        $this->expectUnavailable();

        app(DuffelFlightOrderRequestBuilder::class)
            ->buildHold($intent);
    }

    public function test_output_whitelists_only_order_contract_fields(): void
    {
        $intent =
            $this->completeIntent();

        $intent['client_payload'] = [
            'payments' => [
                'forbidden',
            ],
            'provider_class' =>
                'Arbitrary\\Injected\\Provider',
        ];

        $intent['offer']['raw_supplier_payload'] = [
            'secret' => 'do-not-echo',
        ];

        $intent['travelers'][0]['internal_secret'] =
            'do-not-echo';

        $result =
            app(DuffelFlightOrderRequestBuilder::class)
                ->buildHold($intent);

        $this->assertSame(
            [
                'type',
                'selected_offers',
                'passengers',
            ],
            array_keys(
                $result['data'],
            ),
        );

        $serialized = json_encode(
            $result,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'provider_class',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'raw_supplier_payload',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'internal_secret',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'do-not-echo',
            $serialized,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function completeIntent(): array
    {
        return [
            'offer' => [
                'id' => 'off_safe_offer_1',
                'provider' => 'duffel',
                'total_amount' => '125.50',
                'currency' => 'USD',
                'requires_instant_payment' =>
                    false,
                'passengers' => [
                    [
                        'id' => 'pas_adult_1',
                        'type' => 'adult',
                    ],
                    [
                        'id' => 'pas_child_1',
                        'type' => 'child',
                    ],
                ],
            ],

            'travelers' => [
                [
                    'type' => 'adult',
                    'title' => 'mr',
                    'given_name' => 'Tony',
                    'family_name' => 'Stark',
                    'date_of_birth' =>
                        '1980-07-24',
                    'gender' => 'm',
                    'email' =>
                        'tony@example.test',
                    'phone_number' =>
                        '+14155550101',
                ],
                [
                    'type' => 'child',
                    'title' => 'miss',
                    'given_name' => 'Morgan',
                    'family_name' => 'Stark',
                    'date_of_birth' =>
                        '2015-08-24',
                    'gender' => 'f',
                    'email' =>
                        'morgan@example.test',
                    'phone_number' =>
                        '+14155550102',
                ],
            ],

            'revalidation' => [
                'status' => 'revalidated',
                'provider' => 'duffel',
                'live_revalidation' => true,
                'price_changed' => false,
            ],
        ];
    }

    private function expectUnavailable(): void
    {
        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        $this->expectExceptionMessage(
            'Flight order is currently unavailable.',
        );
    }
}
