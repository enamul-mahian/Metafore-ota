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