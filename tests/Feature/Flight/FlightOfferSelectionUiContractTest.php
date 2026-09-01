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
            )
            ->assertSee(
                'data-flight-booking-draft-url',
                false
            )
            ->assertSee(
                route(
                    'flights.bookings.drafts.store'
                ),
                false
            );
    }

    public function test_flight_frontend_contains_secure_selection_and_traveler_review_contract(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertIsString(
            $javascript
        );

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
            'travelers,',
            $javascript
        );

        $this->assertStringContainsString(
            'Traveler details are stored only in a short-lived encrypted server-side booking draft after successful validation.',
            $javascript
        );

        $this->assertStringNotContainsString(
            'Traveler details entered here are not saved',
            $javascript
        );

        $this->assertStringNotContainsString(
            '.innerHTML =',
            $javascript
        );
    }

    public function test_successful_traveler_validation_connects_to_secure_booking_draft(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertIsString(
            $javascript
        );

        $this->assertMatchesRegularExpression(
            '/async\s+function\s+createFlightBookingDraft\s*\(/',
            $javascript
        );

        $this->assertMatchesRegularExpression(
            '/\.dataset\s*\.flightBookingDraftUrl/',
            $javascript
        );

        $this->assertStringContainsString(
            'booking_draft_token',
            $javascript
        );

        $this->assertMatchesRegularExpression(
            '/\.dataset\s*\.bookingDraftToken\s*=/',
            $javascript
        );

        $this->assertMatchesRegularExpression(
            '/selection_token\s*:/',
            $javascript
        );

        $this->assertMatchesRegularExpression(
            '/\btravelers\s*,/',
            $javascript
        );

        $this->assertMatchesRegularExpression(
            '/credentials\s*:\s*[\'"]same-origin[\'"]/',
            $javascript
        );

        $this->assertMatchesRegularExpression(
            '/await\s+createFlightBookingDraft\s*\(/',
            $javascript
        );

        $this->assertStringContainsString(
            'This is not a supplier booking, ticket, payment, or confirmed reservation.',
            $javascript
        );

        $this->assertMatchesRegularExpression(
            '/notice\s*\.textContent\s*=/',
            $javascript
        );

        $this->assertStringNotContainsString(
            '.innerHTML =',
            $javascript
        );
    }
}
