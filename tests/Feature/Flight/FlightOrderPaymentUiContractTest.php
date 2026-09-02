<?php

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightOrderPaymentUiContractTest extends TestCase
{
    public function test_payment_ui_is_explicit_and_uses_server_routes_only(): void
    {
        $source = $this->paymentSource();

        $this->assertStringContainsString(
            "'Pay now'",
            $source,
        );

        $this->assertStringContainsString(
            'flightPaymentExecutionBusy',
            $source,
        );

        $this->assertStringContainsString(
            'flightPaymentReadinessUrlTemplate',
            $source,
        );

        $this->assertStringContainsString(
            'flightPaymentExecutionUrlTemplate',
            $source,
        );
    }

    public function test_processing_payment_exposes_manual_status_recovery(): void
    {
        $source = $this->paymentSource();

        $this->assertStringContainsString(
            "'Check payment status'",
            $source,
        );

        $this->assertStringContainsString(
            'flightPaymentRecoveryBusy',
            $source,
        );

        $this->assertStringContainsString(
            '.flightPaymentAttemptReference',
            $source,
        );
    }

    public function test_local_payment_status_is_checked_before_reconciliation(): void
    {
        $source = $this->paymentSource();

        $status = strpos(
            $source,
            'let statusResponse;',
        );

        $reconcile = strpos(
            $source,
            'let reconciliationResponse;',
        );

        $this->assertNotFalse($status);
        $this->assertNotFalse($reconcile);

        $this->assertTrue(
            $status < $reconcile,
        );

        $this->assertStringContainsString(
            "localStatus !== 'processing'",
            $source,
        );
    }

    public function test_payment_ui_has_no_polling_storage_or_browser_payment_authority(): void
    {
        $source = $this->paymentSource();

        foreach ([
            'supplier_order_id',
            'supplier_payment_id',
            'localStorage',
            'sessionStorage',
            'setInterval(',
            'setTimeout(',
            'body:',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function paymentSource(): string
    {
        $javascript = file_get_contents(
            resource_path(
                'js/app.js',
            ),
        );

        $this->assertIsString(
            $javascript,
        );

        $start = strpos(
            $javascript,
            '    function renderFlightOrderPaymentAction(',
        );

        $this->assertNotFalse($start);

        $end = strpos(
            $javascript,
            'function renderFlightOrderAttemptRecoveryAction(',
            $start,
        );

        $this->assertNotFalse($end);

        return substr(
            $javascript,
            $start,
            $end - $start,
        );
    }
}