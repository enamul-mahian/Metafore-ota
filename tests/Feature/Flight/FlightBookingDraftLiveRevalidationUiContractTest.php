<?php

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightBookingDraftLiveRevalidationUiContractTest extends TestCase
{
    public function test_frontend_consumes_server_review_revalidation_metadata(): void
    {
        $source = $this->appSource();

        $this->assertStringContainsString(
            'async function reviewFlightBookingDraft(bookingDraftToken)',
            $source,
        );

        $this->assertStringContainsString(
            'payload.data.revalidation',
            $source,
        );

        $this->assertStringContainsString(
            'payload.data.offer',
            $source,
        );

        $this->assertStringContainsString(
            'revalidation.price_changed === true',
            $source,
        );

        $this->assertStringContainsString(
            'revalidation.live_revalidation === true',
            $source,
        );

        $this->assertStringContainsString(
            "revalidationStatus === 'demo_only'",
            $source,
        );

        $this->assertStringContainsString(
            "revalidationProvider === 'fixture'",
            $source,
        );

        $this->assertStringContainsString(
            'await reviewFlightBookingDraft(payload.data.booking_draft_token);',
            $source,
        );
    }

    public function test_price_change_live_and_demo_states_are_explicit(): void
    {
        $source = $this->revalidationUiSource();

        $this->assertStringContainsString(
            "'price-changed'",
            $source,
        );

        $this->assertStringContainsString(
            "'live-revalidated'",
            $source,
        );

        $this->assertStringContainsString(
            "'demo-only'",
            $source,
        );

        $this->assertStringContainsString(
            'Fare changed during live revalidation.',
            $source,
        );

        $this->assertStringContainsString(
            'Latest trusted fare:',
            $source,
        );

        $this->assertStringContainsString(
            'Live fare revalidation completed.',
            $source,
        );

        $this->assertStringContainsString(
            'Demo fare review completed.',
            $source,
        );

        $this->assertStringContainsString(
            'demo-only, not live, and not bookable',
            $source,
        );

        $this->assertStringContainsString(
            'This is not a supplier booking, ticket, payment, or confirmed reservation.',
            $source,
        );
    }

    public function test_revalidation_ui_uses_safe_dom_without_token_or_storage_exposure(): void
    {
        $source = $this->appSource();
        $uiSource = $this->revalidationUiSource();

        $this->assertStringContainsString(
            'document.createElement',
            $uiSource,
        );

        $this->assertStringContainsString(
            'textContent',
            $uiSource,
        );

        $this->assertStringContainsString(
            "'aria-live'",
            $uiSource,
        );

        $this->assertStringNotContainsString(
            'innerHTML',
            $source,
        );

        $this->assertStringNotContainsString(
            'localStorage',
            $source,
        );

        $this->assertStringNotContainsString(
            'sessionStorage',
            $source,
        );

        $this->assertStringNotContainsString(
            'booking_draft_token',
            $uiSource,
        );

        $this->assertStringNotContainsString(
            'selection_token',
            $uiSource,
        );

        $this->assertStringNotContainsString(
            'travelers',
            $uiSource,
        );

        $this->assertStringNotContainsString(
            'fetch(',
            $uiSource,
        );
    }

    public function test_revalidation_ui_does_not_add_order_payment_or_ticketing_actions(): void
    {
        $uiSource = $this->revalidationUiSource();

        foreach ([
            '/air/orders',
            'payment_intent',
            'ticket_number',
            'create order',
            'confirm booking',
            'continue to payment',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                strtolower($uiSource),
            );
        }
    }

    private function appSource(): string
    {
        $source = file_get_contents(
            resource_path(
                'js/app.js',
            ),
        );

        $this->assertIsString(
            $source,
        );

        return $source;
    }

    private function revalidationUiSource(): string
    {
        $source = $this->appSource();

        $startMarker =
            '// BEGIN FLIGHT BOOKING REVALIDATION REVIEW UI';

        $endMarker =
            '// END FLIGHT BOOKING REVALIDATION REVIEW UI';

        $start = strpos(
            $source,
            $startMarker,
        );

        $end = strpos(
            $source,
            $endMarker,
        );

        $this->assertNotFalse(
            $start,
        );

        $this->assertNotFalse(
            $end,
        );

        $this->assertGreaterThan(
            $start,
            $end,
        );

        return substr(
            $source,
            $start,
            ($end - $start) + strlen($endMarker),
        );
    }
}
