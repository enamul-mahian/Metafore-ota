<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightSearchProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FlightOfferSelectionControllerTest extends TestCase
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

    public function test_guest_cannot_select_flight_offer(): void
    {
        $this->postJson(
            route('flights.offers.select'),
            [
                'selection_token' => str_repeat(
                    'a',
                    64
                ),
            ],
        )->assertUnauthorized();
    }

    public function test_search_returns_short_lived_selection_token(): void
    {
        $customer = $this->customer();

        $this->bindFakeProvider();

        $response = $this
            ->actingAs($customer)
            ->postJson(
                route('flights.search'),
                $this->criteria(),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.offers.0.id',
                'offer-secure-1'
            );

        $token = $response->json(
            'data.offers.0.selection_token'
        );

        $this->assertIsString($token);
        $this->assertSame(
            64,
            strlen($token)
        );
    }

    public function test_customer_can_resolve_own_server_stored_offer(): void
    {
        $customer = $this->customer();

        $this->bindFakeProvider();

        $search = $this
            ->actingAs($customer)
            ->postJson(
                route('flights.search'),
                $this->criteria(),
            )
            ->assertOk();

        $token = $search->json(
            'data.offers.0.selection_token'
        );

        $this
            ->actingAs($customer)
            ->postJson(
                route('flights.offers.select'),
                [
                    'selection_token' => $token,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'data.offer.id',
                'offer-secure-1'
            )
            ->assertJsonPath(
                'data.offer.total_amount',
                '45000.00'
            )
            ->assertJsonPath(
                'data.offer.owner.name',
                'Server Trusted Air'
            )
            ->assertJsonPath(
                'data.criteria.origin',
                'DAC'
            )
            ->assertJsonMissingPath(
                'data.offer.selection_token'
            );
    }

    public function test_client_cannot_override_server_stored_offer_data(): void
    {
        $customer = $this->customer();

        $this->bindFakeProvider();

        $search = $this
            ->actingAs($customer)
            ->postJson(
                route('flights.search'),
                $this->criteria(),
            )
            ->assertOk();

        $token = $search->json(
            'data.offers.0.selection_token'
        );

        $this
            ->actingAs($customer)
            ->postJson(
                route('flights.offers.select'),
                [
                    'selection_token' => $token,
                    'total_amount' => '1.00',
                    'owner' => [
                        'name' => 'Tampered Air',
                    ],
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'data.offer.total_amount',
                '45000.00'
            )
            ->assertJsonPath(
                'data.offer.owner.name',
                'Server Trusted Air'
            );
    }

    public function test_selection_token_is_scoped_to_user(): void
    {
        $firstCustomer = $this->customer();
        $secondCustomer = $this->customer();

        $this->bindFakeProvider();

        $search = $this
            ->actingAs($firstCustomer)
            ->postJson(
                route('flights.search'),
                $this->criteria(),
            )
            ->assertOk();

        $token = $search->json(
            'data.offers.0.selection_token'
        );

        $this
            ->actingAs($secondCustomer)
            ->postJson(
                route('flights.offers.select'),
                [
                    'selection_token' => $token,
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
                route('flights.offers.select'),
                [
                    'selection_token' => str_repeat(
                        'x',
                        64
                    ),
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
    private function criteria(): array
    {
        return [
            'trip_type' => 'round_trip',
            'origin' => 'DAC',
            'destination' => 'CXB',
            'departure_date' => now()
                ->addDays(10)
                ->toDateString(),
            'return_date' => now()
                ->addDays(15)
                ->toDateString(),
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
            'cabin_class' => 'economy',
        ];
    }

    private function bindFakeProvider(): void
    {
        $provider = new class implements FlightSearchProvider
        {
            public function search(
                array $criteria,
            ): array {
                return [
                    [
                        'id' => 'offer-secure-1',
                        'provider' => 'fake',
                        'total_amount' => '45000.00',
                        'total_currency' => 'BDT',
                        'expires_at' => null,
                        'requires_instant_payment' => false,
                        'owner' => [
                            'name' => 'Server Trusted Air',
                            'iata_code' => null,
                            'logo_symbol_url' => null,
                        ],
                        'slices' => [],
                    ],
                ];
            }
        };

        $this->app->instance(
            FlightSearchProvider::class,
            $provider,
        );
    }
}
