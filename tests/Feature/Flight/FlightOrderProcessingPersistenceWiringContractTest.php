<?php

namespace Tests\Feature\Flight;

use App\Exceptions\Flight\FlightOrderProcessingException;
use Tests\TestCase;

final class FlightOrderProcessingPersistenceWiringContractTest extends TestCase
{
    public function test_processing_exception_can_carry_sanitized_supplier_offer_and_opaque_attempt_reference(): void
    {
        $reference =
            str_repeat(
                'A',
                64,
            );

        $exception =
            (new FlightOrderProcessingException(
                'duffel',
            ))
                ->withSupplierOfferId(
                    ' off_processing_bridge_1 ',
                )
                ->withAttemptReference(
                    $reference,
                );

        $this->assertSame(
            'duffel',
            $exception->provider(),
        );

        $this->assertSame(
            'off_processing_bridge_1',
            $exception->supplierOfferId(),
        );

        $this->assertSame(
            $reference,
            $exception->attemptReference(),
        );
    }

    public function test_processing_exception_rejects_invalid_bridge_metadata(): void
    {
        $exception =
            (new FlightOrderProcessingException(
                'duffel',
            ))
                ->withSupplierOfferId(
                    "bad\nsupplier-offer",
                )
                ->withAttemptReference(
                    'short-reference',
                );

        $this->assertNull(
            $exception->supplierOfferId(),
        );

        $this->assertNull(
            $exception->attemptReference(),
        );
    }

    public function test_duffel_202_branch_attaches_only_trusted_attempt_offer_identity(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/DuffelFlightOrderProvider.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            '$response->status() === 202',
            $source,
        );

        $this->assertStringContainsString(
            'withSupplierOfferId(',
            $source,
        );

        $this->assertStringContainsString(
            '$attemptOfferId',
            $source,
        );

        $this->assertStringNotContainsString(
            'FlightOrderAttemptRecordStore',
            $source,
        );

        $this->assertStringNotContainsString(
            'attemptReference(',
            $source,
        );
    }

    public function test_execution_service_owns_durable_processing_persistence_not_supplier_provider(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightOrderExecutionService.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        foreach ([
            'FlightOrderAttemptRecordStore $orderAttemptRecordStore',
            'catch (FlightOrderProcessingException $exception)',
            '$exception->supplierOfferId()',
            '->createProcessing(',
            '$userId',
            '$exception->provider()',
            '->withAttemptReference(',
        ] as $required) {
            $this->assertStringContainsString(
                $required,
                $source,
            );
        }

        foreach ([
            'Http::',
            '/air/orders',
            '->post(',
            'payments',
            'ticket',
            'ShouldQueue',
            'dispatch(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    public function test_current_controller_and_ui_are_not_yet_given_the_durable_reference(): void
    {
        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderExecutionController.php',
                ),
            );

        $ui =
            file_get_contents(
                resource_path(
                    'js/app.js',
                ),
            );

        $this->assertIsString(
            $controller,
        );

        $this->assertIsString(
            $ui,
        );

        foreach ([
            'attempt_reference',
            'attempt_id',
            'resolution_token',
            'processing_reference',
        ] as $referenceKey) {
            $this->assertStringNotContainsString(
                $referenceKey,
                $controller,
            );

            $this->assertStringNotContainsString(
                $referenceKey,
                $ui,
            );
        }
    }
}