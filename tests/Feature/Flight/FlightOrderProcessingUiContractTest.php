<?php

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightOrderProcessingUiContractTest extends TestCase
{
    public function test_ui_requires_consumed_safe_processing_shape(): void
    {
        $source =
            $this->executionSource();

        $this->assertStringContainsString(
            'response.status === 202',
            $source,
        );

        $this->assertStringContainsString(
            "payload?.data?.status === 'processing'",
            $source,
        );

        $this->assertStringContainsString(
            'payload?.data?.live_order_creation === true',
            $source,
        );

        $this->assertStringContainsString(
            'payload?.data?.order_created === false',
            $source,
        );

        $this->assertStringContainsString(
            'confirmationIntentConsumed === true',
            $source,
        );

        $this->assertStringContainsString(
            "'Order processing'",
            $source,
        );
    }

    public function test_processing_is_after_token_discard_and_before_generic_non_ok_handling(): void
    {
        $source =
            $this->executionSource();

        $consumed =
            strpos(
                $source,
                'const confirmationIntentConsumed =',
            );

        $discard =
            strpos(
                $source,
                'discardConfirmationIntent();',
                $consumed === false
                    ? 0
                    : $consumed,
            );

        $processing =
            strpos(
                $source,
                'response.status === 202',
            );

        $generic =
            strpos(
                $source,
                'if (!response.ok)',
            );

        $this->assertNotFalse(
            $consumed,
        );

        $this->assertNotFalse(
            $discard,
        );

        $this->assertNotFalse(
            $processing,
        );

        $this->assertNotFalse(
            $generic,
        );

        $this->assertGreaterThan(
            $consumed,
            $discard,
        );

        $this->assertGreaterThan(
            $discard,
            $processing,
        );

        $this->assertGreaterThan(
            $processing,
            $generic,
        );
    }

    public function test_processing_has_no_retry_polling_or_token_persistence(): void
    {
        $source =
            $this->executionSource();

        $this->assertStringContainsString(
            'Do not retry this confirmation intent',
            $source,
        );

        $this->assertStringContainsString(
            'Review or reconciliation is required',
            $source,
        );

        foreach ([
            'setTimeout(',
            'setInterval(',
            'localStorage',
            'sessionStorage',
            'URLSearchParams',
            '/air/orders',
            'DuffelFlightOrderProvider',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    public function test_processing_exposes_manual_attempt_recovery_contract(): void
    {
        $blade =
            file_get_contents(
                resource_path(
                    'views/flights/search.blade.php',
                ),
            );

        $this->assertIsString(
            $blade,
        );

        $this->assertStringContainsString(
            'data-flight-order-attempt-status-url-template',
            $blade,
        );

        $this->assertStringContainsString(
            "route('flights.bookings.orders.attempts.show'",
            $blade,
        );

        $this->assertStringContainsString(
            'data-flight-order-reconciliation-url-template',
            $blade,
        );

        $this->assertStringContainsString(
            "route('flights.bookings.orders.attempts.reconcile'",
            $blade,
        );

        $source =
            $this->executionSource();

        foreach ([
            'payload?.data?.attempt_reference',
            '/^[A-Za-z0-9]{64}$/',
            '.flightOrderAttemptReference',
            'renderFlightOrderAttemptRecoveryAction(',
            "'Check order status'",
        ] as $required) {
            $this->assertStringContainsString(
                $required,
                $source,
            );
        }

        $this->assertStringNotContainsString(
            'textContent = attemptReference',
            $source,
        );
    }

    public function test_manual_recovery_checks_local_status_before_reconciliation(): void
    {
        $source =
            $this->recoverySource();

        foreach ([
            'flightOrderAttemptStatusUrlTemplate',
            'flightOrderReconciliationUrlTemplate',
            'await fetch(',
            'statusUrl,',
            "'GET'",
            'reconciliationUrl,',
            "'POST'",
            "'X-CSRF-TOKEN'",
            "'Check order status'",
            "attemptStatus === 'created'",
            "attemptStatus === 'failed'",
            "localStatus !== 'processing'",
            'reconciliationResponse.status === 202',
        ] as $required) {
            $this->assertStringContainsString(
                $required,
                $source,
            );
        }

        $statusFetch =
            strpos(
                $source,
                'statusUrl,',
            );

        $reconciliationFetch =
            strpos(
                $source,
                'reconciliationUrl,',
            );

        $this->assertNotFalse(
            $statusFetch,
        );

        $this->assertNotFalse(
            $reconciliationFetch,
        );

        $this->assertGreaterThan(
            $statusFetch,
            $reconciliationFetch,
        );

        foreach ([
            'setTimeout(',
            'setInterval(',
            'localStorage',
            'sessionStorage',
            'URLSearchParams',
            '/air/orders',
            'supplier_offer_id',
            'supplier_order_id',
            'reference_hash',
            'attempt_identity_hash',
            'provider:',
            'body:',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function recoverySource(): string
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

        $start =
            strpos(
                $source,
                'function renderFlightOrderAttemptRecoveryAction(',
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
    private function executionSource(): string
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
}