<?php

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightTravelerOrderReadinessUiContractTest extends TestCase
{
    public function test_order_ready_fields_are_rendered_and_collected_into_the_existing_traveler_payload(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js'),
        );

        $this->assertIsString($javascript);

        foreach (
            [
                'createTravelerGenderField',
                'createTravelerOrderReadyTextField',
                "'Gender'",
                "'Email'",
                "'Phone number'",
                "gender: value('gender')",
                "email: value('email')",
                "value('phone_number')",
                "'gender'",
                "'email'",
                "'phone_number'",
            ]
            as $marker
        ) {
            $this->assertStringContainsString(
                $marker,
                $javascript,
            );
        }

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $javascript,
                'travelers,',
            ),
        );

        $this->assertStringContainsString(
            'selection_token:',
            $javascript,
        );
    }

    public function test_server_validation_errors_can_target_the_new_fields(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js'),
        );

        $this->assertIsString($javascript);

        $this->assertStringContainsString(
            '/^travelers\.(\d+)\.(title|gender|email|phone_number|given_name|family_name|date_of_birth)$/',
            $javascript,
        );

        $this->assertStringContainsString(
            'flightTravelerField',
            $javascript,
        );
    }

    public function test_browser_constraints_match_the_server_side_order_ready_contract(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js'),
        );

        $this->assertIsString($javascript);

        $this->assertStringContainsString(
            "input.required = true;",
            $javascript,
        );

        $this->assertStringContainsString(
            "select.required = true;",
            $javascript,
        );

        $this->assertStringContainsString(
            "input.maxLength = 254;",
            $javascript,
        );

        $this->assertStringContainsString(
            'String.raw`\+[1-9][0-9]{6,14}`',
            $javascript,
        );
    }

    public function test_traveler_ui_preserves_existing_dom_and_token_safety_contract(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js'),
        );

        $this->assertIsString($javascript);

        foreach (
            [
                '.innerHTML =',
                'insertAdjacentHTML',
                'localStorage',
                'sessionStorage',
                'URLSearchParams',
                'location.search',
            ]
            as $forbidden
        ) {
            $this->assertStringNotContainsString(
                $forbidden,
                $javascript,
            );
        }
    }

    public function test_traveler_ui_does_not_cross_supplier_order_payment_or_ticketing_boundary(): void
    {
        $javascript = file_get_contents(
            resource_path('js/app.js'),
        );

        $this->assertIsString($javascript);

        foreach (
            [
                '/air/orders',
                'payment_intent',
                'ticket_number',
            ]
            as $forbidden
        ) {
            $this->assertStringNotContainsString(
                $forbidden,
                $javascript,
            );
        }
    }
}
