<?php

namespace Tests\Feature\Flight;

use Tests\TestCase;

final class FlightBookingDraftLiveRevalidationIntegrationTest extends TestCase
{
    public function test_review_controller_revalidates_only_the_server_trusted_draft_offer(): void
    {
        $source = $this->controllerSource();

        $this->assertStringContainsString(
            'FlightOfferRevalidationService',
            $source,
        );

        $this->assertStringContainsString(
            '$trustedOffer',
            $source,
        );

        $this->assertStringContainsString(
            '$revalidationService->revalidate(',
            $source,
        );

        $this->assertStringContainsString(
            '$trustedOffer,',
            $source,
        );

        $this->assertStringNotContainsString(
            '$request->input(',
            $source,
        );

        $this->assertStringNotContainsString(
            '$request->get(',
            $source,
        );
    }

    public function test_review_response_uses_revalidated_offer_and_safe_revalidation_metadata(): void
    {
        $source = $this->controllerSource();

        $this->assertStringContainsString(
            '$reviewOffer',
            $source,
        );

        $this->assertStringContainsString(
            "'offer' => \$this->offerForReview(",
            $source,
        );

        $this->assertStringContainsString(
            "'revalidation' => \$this->revalidationForReview(",
            $source,
        );

        $this->assertStringContainsString(
            "'live_revalidation'",
            $source,
        );

        $this->assertStringContainsString(
            "'price_changed'",
            $source,
        );

        $this->assertStringContainsString(
            "'status' => 'draft_review'",
            $source,
        );

        $this->assertStringContainsString(
            "'Cache-Control'",
            $source,
        );

        $this->assertStringContainsString(
            "'no-store, private'",
            $source,
        );
    }

    public function test_review_controller_does_not_create_supplier_order_payment_or_ticketing_actions(): void
    {
        $source = $this->controllerSource();

        $this->assertStringNotContainsString(
            'Http::',
            $source,
        );

        $this->assertStringNotContainsString(
            '/air/orders',
            $source,
        );

        $this->assertStringNotContainsString(
            '->post(',
            $source,
        );

        $this->assertStringNotContainsString(
            'payment_intent',
            $source,
        );

        $this->assertStringNotContainsString(
            'ticket_number',
            $source,
        );

        $this->assertStringNotContainsString(
            "'booking_draft_token' =>",
            $source,
        );

        $this->assertStringNotContainsString(
            "'travelers' =>",
            $source,
        );
    }

    public function test_review_controller_keeps_existing_server_whitelists(): void
    {
        $source = $this->controllerSource();

        foreach ([
            "'trip_type'",
            "'origin'",
            "'destination'",
            "'departure_date'",
            "'return_date'",
            "'adults'",
            "'children'",
            "'infants'",
            "'cabin_class'",
            "'id'",
            "'provider'",
            "'total_amount'",
            "'currency'",
            "'owner'",
        ] as $fragment) {
            $this->assertStringContainsString(
                $fragment,
                $source,
            );
        }
    }

    private function controllerSource(): string
    {
        $source = file_get_contents(
            app_path(
                'Http/Controllers/Flight/FlightBookingDraftReviewController.php',
            ),
        );

        $this->assertIsString(
            $source,
        );

        return $source;
    }
}
