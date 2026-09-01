<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use App\Services\Flight\FlightOfferSelectionStore;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FlightTravelerValidationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.default',
            'array'
        );

        $this->seed(
            RolePermissionSeeder::class
        );
    }

    public function test_guest_cannot_validate_travelers(): void
    {
        $this->postJson(
            route('flights.travelers.validate'),
            [
                'selection_token' => str_repeat(
                    'a',
                    64
                ),
                'travelers' => [],
            ],
        )->assertUnauthorized();
    }

    public function test_valid_travelers_match_selected_search(): void
    {
        $customer = $this->customer();

        $departure = CarbonImmutable::now()
            ->addDays(30)
            ->startOfDay();

        $criteria = $this->criteria(
            $departure,
            adults: 1,
            children: 1,
            infants: 1,
        );

        $token = $this->selectionToken(
            $customer,
            $criteria,
        );

        $response = $this
            ->actingAs($customer)
            ->postJson(
                route(
                    'flights.travelers.validate'
                ),
                [
                    'selection_token' => $token,
                    'travelers' => [
                        $this->traveler(
                            'adult',
                            'mr',
                            'Adam',
                            'Rahman',
                            $departure
                                ->subYears(30)
                                ->toDateString(),
                        ),
                        $this->traveler(
                            'child',
                            'miss',
                            'Nora',
                            'Rahman',
                            $departure
                                ->subYears(7)
                                ->toDateString(),
                        ),
                        $this->traveler(
                            'infant',
                            'mstr',
                            'Noah',
                            'Rahman',
                            $departure
                                ->subMonths(8)
                                ->toDateString(),
                        ),
                    ],
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.valid',
                true
            )
            ->assertJsonPath(
                'data.traveler_count',
                3
            )
            ->assertJsonMissingPath(
                'data.travelers'
            );
    }

    public function test_traveler_mix_must_match_search_counts(): void
    {
        $customer = $this->customer();

        $departure = CarbonImmutable::now()
            ->addDays(30)
            ->startOfDay();

        $criteria = $this->criteria(
            $departure,
            adults: 2,
            children: 0,
            infants: 0,
        );

        $token = $this->selectionToken(
            $customer,
            $criteria,
        );

        $this
            ->actingAs($customer)
            ->postJson(
                route(
                    'flights.travelers.validate'
                ),
                [
                    'selection_token' => $token,
                    'travelers' => [
                        $this->traveler(
                            'adult',
                            'mr',
                            'Adam',
                            'Rahman',
                            $departure
                                ->subYears(30)
                                ->toDateString(),
                        ),
                    ],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'travelers',
            ]);
    }

    public function test_traveler_age_must_match_passenger_type(): void
    {
        $customer = $this->customer();

        $departure = CarbonImmutable::now()
            ->addDays(30)
            ->startOfDay();

        $criteria = $this->criteria(
            $departure,
            adults: 1,
            children: 1,
            infants: 0,
        );

        $token = $this->selectionToken(
            $customer,
            $criteria,
        );

        $this
            ->actingAs($customer)
            ->postJson(
                route(
                    'flights.travelers.validate'
                ),
                [
                    'selection_token' => $token,
                    'travelers' => [
                        $this->traveler(
                            'adult',
                            'mr',
                            'Adam',
                            'Rahman',
                            $departure
                                ->subYears(30)
                                ->toDateString(),
                        ),
                        $this->traveler(
                            'child',
                            'ms',
                            'Sara',
                            'Rahman',
                            $departure
                                ->subYears(30)
                                ->toDateString(),
                        ),
                    ],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'travelers.1.date_of_birth',
            ]);
    }

    public function test_invalid_names_and_future_birth_date_are_rejected(): void
    {
        $customer = $this->customer();

        $departure = CarbonImmutable::now()
            ->addDays(30)
            ->startOfDay();

        $criteria = $this->criteria(
            $departure,
            adults: 1,
            children: 0,
            infants: 0,
        );

        $token = $this->selectionToken(
            $customer,
            $criteria,
        );

        $this
            ->actingAs($customer)
            ->postJson(
                route(
                    'flights.travelers.validate'
                ),
                [
                    'selection_token' => $token,
                    'travelers' => [
                        $this->traveler(
                            'adult',
                            'mr',
                            '1234',
                            'Rahman9',
                            CarbonImmutable::now()
                                ->addDay()
                                ->toDateString(),
                        ),
                    ],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'travelers.0.given_name',
                'travelers.0.family_name',
                'travelers.0.date_of_birth',
            ]);
    }

    public function test_selection_token_is_scoped_to_customer(): void
    {
        $firstCustomer = $this->customer();
        $secondCustomer = $this->customer();

        $departure = CarbonImmutable::now()
            ->addDays(30)
            ->startOfDay();

        $criteria = $this->criteria(
            $departure,
            adults: 1,
            children: 0,
            infants: 0,
        );

        $token = $this->selectionToken(
            $firstCustomer,
            $criteria,
        );

        $this
            ->actingAs($secondCustomer)
            ->postJson(
                route(
                    'flights.travelers.validate'
                ),
                [
                    'selection_token' => $token,
                    'travelers' => [
                        $this->traveler(
                            'adult',
                            'mr',
                            'Adam',
                            'Rahman',
                            $departure
                                ->subYears(30)
                                ->toDateString(),
                        ),
                    ],
                ],
            )
            ->assertStatus(410);
    }

    public function test_unknown_selection_token_requires_new_search(): void
    {
        $customer = $this->customer();

        $this
            ->actingAs($customer)
            ->postJson(
                route(
                    'flights.travelers.validate'
                ),
                [
                    'selection_token' => str_repeat(
                        'x',
                        64
                    ),
                    'travelers' => [
                        [
                            'type' => 'adult',
                            'title' => 'mr',
                            'given_name' => 'Adam',
                            'family_name' => 'Rahman',
                            'date_of_birth' => '1990-01-01',
                        ],
                    ],
                ],
            )
            ->assertStatus(410)
            ->assertJsonPath(
                'message',
                (
                    'This flight offer selection '
                    . 'has expired or is no longer '
                    . 'available. Please search again.'
                )
            );
    }

    private function customer(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function criteria(
        CarbonImmutable $departure,
        int $adults,
        int $children,
        int $infants,
    ): array {
        return [
            'trip_type' => 'one_way',
            'origin' => 'DAC',
            'destination' => 'CXB',
            'departure_date' => $departure
                ->toDateString(),
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'cabin_class' => 'economy',
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function selectionToken(
        User $customer,
        array $criteria,
    ): string {
        $offers = app(
            FlightOfferSelectionStore::class
        )->attachSelectionTokens(
            $customer->getAuthIdentifier(),
            $criteria,
            [
                [
                    'id' => 'validation-offer',
                    'provider' => 'fixture',
                    'total_amount' => '15000.00',
                    'total_currency' => 'BDT',
                ],
            ],
        );

        return $offers[0]['selection_token'];
    }

    /**
     * @return array<string, string>
     */
    private function traveler(
        string $type,
        string $title,
        string $givenName,
        string $familyName,
        string $dateOfBirth,
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'given_name' => $givenName,
            'family_name' => $familyName,
            'date_of_birth' => $dateOfBirth,
        ];
    }
}
