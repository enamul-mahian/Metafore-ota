<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightSearchProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_search_flights(): void
    {
        $this->postJson(
            route('flights.search'),
            $this->validPayload()
        )->assertUnauthorized();
    }

    public function test_customer_has_flight_search_permission(): void
    {
        $customer = $this->customer();

        $this->assertTrue(
            $customer->can('flights.search')
        );
    }

    public function test_customer_can_search_flights_with_valid_input(): void
    {
        $provider = new class implements FlightSearchProvider
        {
            /**
             * @var array<string, mixed>
             */
            public array $criteria = [];

            public function search(array $criteria): array
            {
                $this->criteria = $criteria;

                return [
                    [
                        'id' => 'offer-1',
                        'provider' => 'fake',
                        'currency' => 'BDT',
                        'total' => 45000,
                    ],
                ];
            }
        };

        $this->app->instance(
            FlightSearchProvider::class,
            $provider
        );

        $response = $this->actingAs($this->customer())
            ->postJson(
                route('flights.search'),
                $this->validPayload()
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.offers.0.id',
                'offer-1'
            )
            ->assertJsonPath(
                'data.offers.0.provider',
                'fake'
            );

        $this->assertSame(
            'DAC',
            $provider->criteria['origin']
        );

        $this->assertSame(
            'DXB',
            $provider->criteria['destination']
        );

        $this->assertSame(
            'economy',
            $provider->criteria['cabin_class']
        );
    }

    public function test_search_rejects_invalid_airports_and_trip_data(): void
    {
        $response = $this->actingAs($this->customer())
            ->postJson(
                route('flights.search'),
                $this->validPayload([
                    'trip_type' => 'invalid',
                    'origin' => 'D1',
                    'destination' => 'D1',
                    'departure_date' => now()
                        ->subDay()
                        ->toDateString(),
                    'cabin_class' => 'vip',
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'trip_type',
                'origin',
                'destination',
                'departure_date',
                'cabin_class',
            ]);
    }

    public function test_round_trip_requires_return_date(): void
    {
        $response = $this->actingAs($this->customer())
            ->postJson(
                route('flights.search'),
                $this->validPayload([
                    'return_date' => null,
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'return_date',
            ]);
    }

    public function test_return_date_must_be_after_departure_date(): void
    {
        $departure = now()
            ->addDays(10)
            ->toDateString();

        $response = $this->actingAs($this->customer())
            ->postJson(
                route('flights.search'),
                $this->validPayload([
                    'departure_date' => $departure,
                    'return_date' => $departure,
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'return_date',
            ]);
    }

    public function test_one_way_search_rejects_return_date(): void
    {
        $response = $this->actingAs($this->customer())
            ->postJson(
                route('flights.search'),
                $this->validPayload([
                    'trip_type' => 'one_way',
                    'return_date' => now()
                        ->addDays(14)
                        ->toDateString(),
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'return_date',
            ]);
    }

    public function test_infants_cannot_exceed_adults(): void
    {
        $response = $this->actingAs($this->customer())
            ->postJson(
                route('flights.search'),
                $this->validPayload([
                    'adults' => 1,
                    'infants' => 2,
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'infants',
            ]);
    }

    public function test_total_passengers_cannot_exceed_nine(): void
    {
        $response = $this->actingAs($this->customer())
            ->postJson(
                route('flights.search'),
                $this->validPayload([
                    'adults' => 5,
                    'children' => 4,
                    'infants' => 1,
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'passengers',
            ]);
    }

    public function test_unconfigured_provider_returns_service_unavailable(): void
    {
        $this->actingAs($this->customer())
            ->postJson(
                route('flights.search'),
                $this->validPayload()
            )
            ->assertStatus(503);
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
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        array $overrides = []
    ): array {
        return array_merge([
            'trip_type' => 'round_trip',
            'origin' => 'dac',
            'destination' => 'dxb',
            'departure_date' => now()
                ->addDays(7)
                ->toDateString(),
            'return_date' => now()
                ->addDays(14)
                ->toDateString(),
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
            'cabin_class' => 'economy',
        ], $overrides);
    }
}
