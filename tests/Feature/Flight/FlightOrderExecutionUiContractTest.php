<?php

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightOrderExecutionUiContractTest extends TestCase
{
    public function test_flight_page_exposes_guarded_order_execution_endpoint(): void
    {
        $blade =
            $this->bladeSource();

        $this->assertStringContainsString(
            'data-flight-order-execution-url',
            $blade,
        );

        $this->assertStringContainsString(
            "route('flights.bookings.orders.execute')",
            $blade,
        );
    }

    public function test_order_execution_requires_a_separate_explicit_click(): void
    {
        $source =
            $this->executionUiSource();

        $this->assertStringContainsString(
            "'Create flight order'",
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

        $listenerPosition =
            strpos(
                $source,
                "button.addEventListener(",
            );

        $fetchPosition =
            strpos(
                $source,
                'await fetch(',
            );

        $this->assertNotFalse(
            $listenerPosition,
        );

        $this->assertNotFalse(
            $fetchPosition,
        );

        $this->assertGreaterThan(
            $listenerPosition,
            $fetchPosition,
        );
    }

    public function test_execution_request_uses_only_private_confirmation_intent_token(): void
    {
        $source =
            $this->executionUiSource();

        $this->assertStringContainsString(
            'confirmation_intent_token:',
            $source,
        );

        $this->assertStringContainsString(
            '.flightBookingConfirmationIntentToken',
            $source,
        );

        foreach ([
            'booking_draft_token:',
            'provider:',
            'total_amount:',
            'currency:',
            'travelers:',
            'payment:',
            'ticket_number:',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    public function test_execution_token_remains_transient_and_is_discarded_after_ambiguous_or_consumed_attempt(): void
    {
        $source =
            $this->executionUiSource();

        $this->assertStringContainsString(
            'delete resultsElement',
            $source,
        );

        $this->assertStringContainsString(
            '.flightBookingConfirmationIntentToken',
            $source,
        );

        $this->assertStringContainsString(
            "confirmationIntentToken =",
            $source,
        );

        $this->assertStringContainsString(
            "'outcome-uncertain'",
            $source,
        );

        $this->assertStringContainsString(
            "'attempt-consumed'",
            $source,
        );

        $this->assertStringContainsString(
            'Do not retry this confirmation intent',
            $source,
        );

        foreach ([
            'localStorage',
            'sessionStorage',
            'URLSearchParams',
            'location.',
            'innerHTML',
            'setTimeout(',
            '->retry(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    public function test_default_disabled_execution_response_does_not_discard_unconsumed_intent(): void
    {
        $source =
            $this->executionUiSource();

        $this->assertStringContainsString(
            "'execution_disabled'",
            $source,
        );

        $this->assertStringContainsString(
            'confirmation_intent_consumed',
            $source,
        );

        $this->assertStringContainsString(
            "'execution-disabled'",
            $source,
        );

        $this->assertStringContainsString(
            'The short-lived confirmation intent was not consumed.',
            $source,
        );
    }

    public function test_success_requires_created_single_use_normalized_response(): void
    {
        $source =
            $this->executionUiSource();

        $this->assertStringContainsString(
            'response.status !== 201',
            $source,
        );

        $this->assertStringContainsString(
            "payload?.data?.status !== 'created'",
            $source,
        );

        $this->assertStringContainsString(
            'payload?.data?.order_created !== true',
            $source,
        );

        $this->assertStringContainsString(
            'payload?.data?.live_order_creation !== true',
            $source,
        );

        $this->assertStringContainsString(
            "'Order created'",
            $source,
        );

        $this->assertStringContainsString(
            'No payment or ticketing action was performed by this step.',
            $source,
        );
    }

    public function test_confirmation_intent_success_only_renders_execution_action_after_private_token_is_set(): void
    {
        $source =
            $this->confirmationUiSource();

        $tokenPosition =
            strpos(
                $source,
                '.flightBookingConfirmationIntentToken =',
            );

        $executionActionPosition =
            strpos(
                $source,
                'renderFlightOrderExecutionAction(',
            );

        $this->assertNotFalse(
            $tokenPosition,
        );

        $this->assertNotFalse(
            $executionActionPosition,
        );

        $this->assertGreaterThan(
            $tokenPosition,
            $executionActionPosition,
        );

        $this->assertStringNotContainsString(
            '.flightOrderExecutionUrl',
            $source,
        );
    }

    public function test_execution_ui_has_no_direct_supplier_payment_ticketing_or_persistence_boundary(): void
    {
        $source =
            strtolower(
                $this->executionUiSource(),
            );

        foreach ([
            '/air/orders',
            'duffelflightorderprovider',
            'payment_intent',
            'ticket_number',
            'database',
            'localstorage',
            'sessionstorage',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
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

    private function executionUiSource(): string
    {
        $source =
            $this->appSource();

        $start =
            strpos(
                $source,
                'function renderFlightOrderExecutionAction(',
            );

        $end =
            strpos(
                $source,
                'function clearFlightBookingConfirmationIntentState(',
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

    private function confirmationUiSource(): string
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