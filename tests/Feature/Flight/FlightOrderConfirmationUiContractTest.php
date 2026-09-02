<?php

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightOrderConfirmationUiContractTest extends TestCase
{
    public function test_flight_page_exposes_confirmation_get_endpoint(): void
    {
        $blade =
            file_get_contents(
                resource_path(
                    'views/flights/search.blade.php',
                ),
            );

        $this->assertIsString($blade);

        $this->assertStringContainsString(
            'data-flight-order-confirmation-url-template=',
            $blade,
        );

        $this->assertStringContainsString(
            'flights.bookings.orders.attempts.confirmation.show',
            $blade,
        );
    }

    public function test_all_three_payment_success_paths_expose_confirmation_action(): void
    {
        $source =
            $this->paymentSource();

        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderBookingConfirmationAction();',
            ),
        );

        foreach ([
            "paymentStatus === 'succeeded'",
            "localStatus === 'succeeded'",
            "reconciledStatus === 'succeeded'",
            "'View booking confirmation'",
            'booking_reference',
            'Booking confirmed. Reference:',
        ] as $required) {
            $this->assertStringContainsString(
                $required,
                $source,
            );
        }
    }

    public function test_confirmation_action_is_get_only_and_has_no_mutation_authority(): void
    {
        $source =
            $this->confirmationSource();

        $this->assertStringContainsString(
            "'GET'",
            $source,
        );

        foreach ([
            "'POST'",
            'body:',
            'supplier_order_id',
            'supplier_payment_id',
            'localStorage',
            'sessionStorage',
            'setInterval(',
            'setTimeout(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function paymentSource(): string
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/app.js',
                ),
            );

        $this->assertIsString($javascript);

        $start =
            strpos(
                $javascript,
                '    function renderFlightOrderPaymentAction(',
            );

        $this->assertNotFalse($start);

        $end =
            strpos(
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

    private function confirmationSource(): string
    {
        $source =
            $this->paymentSource();

        $start =
            strpos(
                $source,
                '        const renderBookingConfirmationAction = () => {',
            );

        $this->assertNotFalse($start);

        $end =
            strpos(
                $source,
                '        const renderPaymentRecoveryAction = (',
                $start,
            );

        $this->assertNotFalse($end);

        return substr(
            $source,
            $start,
            $end - $start,
        );
    }
}