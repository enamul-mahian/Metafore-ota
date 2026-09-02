<?php

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightBookingConfirmationIntentUiContractTest extends TestCase
{
    public function test_flight_page_exposes_confirmation_intent_endpoint(): void
    {
        $blade = $this->bladeSource();

        $this->assertStringContainsString(
            'data-flight-booking-confirmation-intent-url',
            $blade,
        );

        $this->assertStringContainsString(
            "route('flights.bookings.confirmation-intents.store')",
            $blade,
        );
    }

    public function test_live_revalidated_fare_requires_explicit_user_acknowledgement(): void
    {
        $source = $this->appSource();

        $this->assertStringContainsString(
            'renderFlightBookingConfirmationIntentAction',
            $source,
        );

        $this->assertStringContainsString(
            "revalidation.status !== 'revalidated'",
            $source,
        );

        $this->assertStringContainsString(
            'revalidation.live_revalidation !== true',
            $source,
        );

        $this->assertStringContainsString(
            "'Acknowledge latest fare'",
            $source,
        );

        $this->assertStringContainsString(
            "button.addEventListener(",
            $source,
        );

        $this->assertStringContainsString(
            "'click'",
            $source,
        );

        $this->assertStringContainsString(
            'accept_revalidated_fare:',
            $source,
        );

        $this->assertStringContainsString(
            'true,',
            $source,
        );

        $this->assertStringContainsString(
            'acknowledged_total_amount:',
            $source,
        );

        $this->assertStringContainsString(
            'acknowledged_currency:',
            $source,
        );
    }

    public function test_acknowledged_fare_comes_from_server_review_offer(): void
    {
        $source =
            $this->confirmationIntentUiSource();

        $this->assertStringContainsString(
            'reviewData?.offer',
            $source,
        );

        $this->assertStringContainsString(
            'offer.total_amount',
            $source,
        );

        $this->assertStringContainsString(
            'offer.currency',
            $source,
        );

        $this->assertStringContainsString(
            'acknowledgedTotalAmount',
            $source,
        );

        $this->assertStringContainsString(
            'acknowledgedCurrency',
            $source,
        );

        $this->assertStringNotContainsString(
            'querySelector('
                . "'[name=\"acknowledged_total_amount\"]'",
            $source,
        );

        $this->assertStringNotContainsString(
            'querySelector('
                . "'[name=\"acknowledged_currency\"]'",
            $source,
        );
    }

    public function test_fare_change_requires_another_explicit_acknowledgement(): void
    {
        $source =
            $this->confirmationIntentUiSource();

        $this->assertStringContainsString(
            "payload?.data?.status === 'fare_changed'",
            $source,
        );

        $this->assertStringContainsString(
            'payload?.data?.offer',
            $source,
        );

        $this->assertStringContainsString(
            "'Acknowledge updated fare'",
            $source,
        );

        $this->assertStringContainsString(
            'The fare changed again.',
            $source,
        );

        $this->assertStringContainsString(
            'explicitly acknowledge it again',
            $source,
        );

        $this->assertStringContainsString(
            'button.disabled =',
            $source,
        );
    }

    public function test_confirmation_intent_token_remains_private_transient_browser_state(): void
    {
        $source =
            $this->confirmationIntentUiSource();

        $this->assertStringContainsString(
            'confirmation_intent_token',
            $source,
        );

        $this->assertStringContainsString(
            '.flightBookingConfirmationIntentToken',
            $source,
        );

        $this->assertStringContainsString(
            '.flightBookingConfirmationIntentExpiresInSeconds',
            $source,
        );

        $this->assertStringNotContainsString(
            'textContent = confirmationIntentToken',
            $source,
        );

        $this->assertStringNotContainsString(
            'location.',
            $source,
        );

        $this->assertStringNotContainsString(
            'URLSearchParams',
            $source,
        );
    }

    public function test_confirmation_intent_ui_preserves_safe_pre_order_boundary(): void
    {
        $app =
            $this->appSource();

        $source =
            $this->confirmationIntentUiSource();

        $this->assertStringContainsString(
            'document.createElement',
            $source,
        );

        $this->assertStringContainsString(
            'textContent',
            $source,
        );

        $this->assertStringNotContainsString(
            'innerHTML',
            $app,
        );

        $this->assertStringNotContainsString(
            'localStorage',
            $app,
        );

        $this->assertStringNotContainsString(
            'sessionStorage',
            $app,
        );

        foreach ([
            '/air/orders',
            'payment_intent',
            'ticket_number',
            'continue to payment',
            'confirm supplier booking',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                strtolower($source),
            );
        }

        $this->assertStringContainsString(
            'does not create a supplier booking, ticket, payment, or confirmed reservation',
            strtolower($source),
        );
    }

    private function appSource(): string
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/app.js',
                ),
            );

        $this->assertIsString(
            $source,
        );

        return $source;
    }

    private function bladeSource(): string
    {
        $source =
            file_get_contents(
                resource_path(
                    'views/flights/search.blade.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        return $source;
    }

    private function confirmationIntentUiSource(): string
    {
        $source =
            $this->appSource();

        $start =
            strpos(
                $source,
                'function clearFlightBookingConfirmationIntentState(',
            );

        $end =
            strpos(
                $source,
                'async function reviewFlightBookingDraft(',
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
            $end - $start,
        );
    }
}
