<?php

declare(strict_types=1);

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightBookingDraftReviewUiContractTest extends TestCase
{
    public function test_flight_frontend_exposes_secure_booking_draft_review_endpoint(): void
    {
        $blade = file_get_contents(
            resource_path('views/flights/search.blade.php')
        );

        $this->assertIsString($blade);

        $this->assertStringContainsString(
            'data-flight-booking-draft-review-url="{{ route('
                . "'flights.bookings.drafts.review'"
                . ') }}"',
            $blade,
        );
    }

    public function test_successful_booking_draft_creation_connects_to_secure_review(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertIsString($javascript);

        $this->assertStringContainsString(
            'reviewFlightBookingDraft',
            $javascript,
        );

        $this->assertStringContainsString(
            'flightBookingDraftReviewUrl',
            $javascript,
        );

        $this->assertStringContainsString(
            'booking_draft_token: bookingDraftToken',
            $javascript,
        );

        $this->assertStringContainsString(
            'await reviewFlightBookingDraft(payload.data.booking_draft_token);',
            $javascript,
        );

        $this->assertStringContainsString(
            'response.status === 410',
            $javascript,
        );

        $this->assertStringContainsString(
            'delete resultsElement.dataset.bookingDraftToken',
            $javascript,
        );

        $this->assertStringContainsString(
            'server-trusted fare and route data',
            $javascript,
        );

        $this->assertStringContainsString(
            'not a supplier booking, ticket, payment, or confirmed reservation',
            $javascript,
        );
    }

    public function test_booking_draft_review_frontend_keeps_token_private_and_uses_safe_dom_rendering(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertIsString($javascript);

        $this->assertMatchesRegularExpression(
            '/JSON\.stringify\(\s*\{\s*booking_draft_token:\s*bookingDraftToken\s*,?\s*\}\s*\)/s',
            $javascript,
        );

        $this->assertStringContainsString(
            '.textContent =',
            $javascript,
        );

        $this->assertStringNotContainsString(
            '.innerHTML =',
            $javascript,
        );

        $this->assertDoesNotMatchRegularExpression(
            '/localStorage[^\r\n]*bookingDraftToken/i',
            $javascript,
        );

        $this->assertDoesNotMatchRegularExpression(
            '/sessionStorage[^\r\n]*bookingDraftToken/i',
            $javascript,
        );

        $this->assertDoesNotMatchRegularExpression(
            '/URLSearchParams[^\r\n]*bookingDraftToken/i',
            $javascript,
        );

        $this->assertDoesNotMatchRegularExpression(
            '/(?:location\.href|window\.location)[^\r\n]*bookingDraftToken/i',
            $javascript,
        );
    }
}