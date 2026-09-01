<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FlightOfferSelectionUiContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            RolePermissionSeeder::class
        );
    }

    public function test_flight_page_exposes_secure_offer_selection_endpoint(): void
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $customer->assignRole('customer');

        $this
            ->actingAs($customer)
            ->get(route('flights.index'))
            ->assertOk()
            ->assertSee(
                'data-flight-select-url',
                false
            )
            ->assertSee(
                route('flights.offers.select'),
                false
            )
            ->assertSee(
                'data-flight-traveler-validation-url',
                false
            )
            ->assertSee(
                route('flights.travelers.validate'),
                false
            );
    }

    public function test_flight_frontend_contains_secure_selection_and_draft_review_contract(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertIsString($javascript);

        $this->assertStringContainsString(
            'Select Flight',
            $javascript
        );

        $this->assertStringContainsString(
            'selection_token:',
            $javascript
        );

        $this->assertStringContainsString(
            'flight-traveler-review',
            $javascript
        );

        $this->assertStringContainsString(
            'Traveler details entered here are not saved',
            $javascript
        );

        $this->assertStringContainsString(
            'flightTravelerValidationUrl',
            $javascript
        );

        $this->assertStringContainsString(
            'Validate travelers',
            $javascript
        );

        $this->assertStringContainsString(
            'validateFlightTravelers',
            $javascript
        );

        $this->assertStringContainsString(
            'selection_token:',
            $javascript
        );

        $this->assertStringContainsString(
            'travelers,',
            $javascript
        );

        $this->assertStringNotContainsString(
            '.innerHTML =',
            $javascript
        );
    }
}
