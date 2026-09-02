<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOrderReconciliationProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class DuffelFlightOrderReconciliationProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'flight.duffel.access_token' =>
                'duffel-test-token',

            'flight.duffel.base_url' =>
                'https://api.duffel.test',

            'flight.duffel.api_version' =>
                'v2',

            'flight.duffel.http_timeout' =>
                30,
        ]);
    }

    public function test_invalid_offer_identity_fails_before_supplier_http(): void
    {
        Http::fake();

        $this->assertServiceUnavailable(
            fn () =>
                $this->provider()
                    ->readBySupplierOfferId(
                        "bad\noffer",
                    ),
        );

        Http::assertNothingSent();

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_missing_access_token_fails_before_supplier_http(): void
    {
        Http::fake();

        config([
            'flight.duffel.access_token' =>
                '',
        ]);

        $this->assertServiceUnavailable(
            fn () =>
                $this->provider()
                    ->readBySupplierOfferId(
                        'off_supplier_read_1',
                    ),
        );

        Http::assertNothingSent();

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_zero_matching_orders_remains_processing_without_durable_mutation(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'data' =>
                            [],
                    ],
                    200,
                ),
        ]);

        $result =
            $this->provider()
                ->readBySupplierOfferId(
                    'off_supplier_read_processing_1',
                );

        $this->assertSame(
            [
                'status' =>
                    'processing',
            ],
            $result,
        );

        $this->assertSupplierReadRequest(
            'off_supplier_read_processing_1',
        );

        Http::assertSentCount(
            1,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_one_exact_matching_order_returns_normalized_created_result_without_mutation(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'data' => [
                            [
                                'id' =>
                                    'ord_0000000000000001',

                                'offer_id' =>
                                    'off_supplier_read_created_1',
                            ],
                        ],
                    ],
                    200,
                ),
        ]);

        $result =
            $this->provider()
                ->readBySupplierOfferId(
                    ' off_supplier_read_created_1 ',
                );

        $this->assertSame(
            [
                'status' =>
                    'created',

                'supplier_order_id' =>
                    'ord_0000000000000001',
            ],
            $result,
        );

        $this->assertSupplierReadRequest(
            'off_supplier_read_created_1',
        );

        Http::assertSentCount(
            1,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_supplier_offer_mismatch_fails_closed_without_mutation(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'data' => [
                            [
                                'id' =>
                                    'ord_0000000000000002',

                                'offer_id' =>
                                    'off_different_offer',
                            ],
                        ],
                    ],
                    200,
                ),
        ]);

        $this->assertServiceUnavailable(
            fn () =>
                $this->provider()
                    ->readBySupplierOfferId(
                        'off_supplier_read_mismatch_1',
                    ),
        );

        Http::assertSentCount(
            1,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_multiple_matching_orders_fail_closed_without_mutation(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'data' => [
                            [
                                'id' =>
                                    'ord_0000000000000003',

                                'offer_id' =>
                                    'off_supplier_read_multiple_1',
                            ],
                            [
                                'id' =>
                                    'ord_0000000000000004',

                                'offer_id' =>
                                    'off_supplier_read_multiple_1',
                            ],
                        ],
                    ],
                    200,
                ),
        ]);

        $this->assertServiceUnavailable(
            fn () =>
                $this->provider()
                    ->readBySupplierOfferId(
                        'off_supplier_read_multiple_1',
                    ),
        );

        Http::assertSentCount(
            1,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_malformed_supplier_payload_fails_closed_without_mutation(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'data' =>
                            'not-an-order-list',
                    ],
                    200,
                ),
        ]);

        $this->assertServiceUnavailable(
            fn () =>
                $this->provider()
                    ->readBySupplierOfferId(
                        'off_supplier_read_malformed_1',
                    ),
        );

        Http::assertSentCount(
            1,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_non_success_supplier_response_fails_closed_without_mutation(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'message' =>
                                    'Supplier unavailable.',
                            ],
                        ],
                    ],
                    503,
                ),
        ]);

        $this->assertServiceUnavailable(
            fn () =>
                $this->provider()
                    ->readBySupplierOfferId(
                        'off_supplier_read_failure_1',
                    ),
        );

        Http::assertSentCount(
            1,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_supplier_read_provider_is_get_only_and_has_no_durable_transition_or_failure_wiring(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/DuffelFlightOrderReconciliationProvider.php',
                ),
            );

        $createContract =
            file_get_contents(
                app_path(
                    'Contracts/Flight/FlightOrderProvider.php',
                ),
            );

        $statusController =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderAttemptStatusController.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertIsString(
            $createContract,
        );

        $this->assertIsString(
            $statusController,
        );

        foreach ([
            'implements FlightOrderReconciliationProvider',
            'readBySupplierOfferId(',
            "->get(",
            "'/air/orders'",
            "'offer_id'",
            "'limit'",
            '2',
            "'processing'",
            "'created'",
            "'supplier_order_id'",
            'ConnectionException',
        ] as $requiredSignal) {
            $this->assertStringContainsString(
                $requiredSignal,
                $source,
            );
        }

        foreach ([
            '->post(',
            'selected_offers',
            'FlightOrderAttemptRecordStore',
            'findByProviderAndOffer(',
            'markCreated(',
            'markFailed(',
            'STATUS_FAILED',
            'dispatch(',
            'ShouldQueue',
            'Bus::',
            'Queue::',
        ] as $forbiddenSignal) {
            $this->assertStringNotContainsString(
                $forbiddenSignal,
                $source,
            );
        }

        $this->assertStringContainsString(
            'createFromTrustedConfirmationIntent(',
            $createContract,
        );

        $this->assertStringNotContainsString(
            'readBySupplierOfferId(',
            $createContract,
        );

        $this->assertStringContainsString(
            'findForUser(',
            $statusController,
        );

        $this->assertStringNotContainsString(
            'readBySupplierOfferId(',
            $statusController,
        );
    }

    private function provider(): DuffelFlightOrderReconciliationProvider
    {
        return app(
            DuffelFlightOrderReconciliationProvider::class,
        );
    }

    private function assertSupplierReadRequest(
        string $supplierOfferId,
    ): void {
        Http::assertSent(
            function (
                Request $request,
            ) use (
                $supplierOfferId,
            ): bool {
                if (
                    $request->method()
                    !== 'GET'
                ) {
                    return false;
                }

                $url =
                    $request->url();

                if (
                    ! str_contains(
                        $url,
                        '/air/orders?',
                    )
                ) {
                    return false;
                }

                if (
                    ! str_contains(
                        $url,
                        'offer_id='
                        . rawurlencode(
                            $supplierOfferId,
                        ),
                    )
                ) {
                    return false;
                }

                if (
                    ! str_contains(
                        $url,
                        'limit=2',
                    )
                ) {
                    return false;
                }

                if (
                    ! $request->hasHeader(
                        'Authorization',
                        'Bearer duffel-test-token',
                    )
                ) {
                    return false;
                }

                if (
                    ! $request->hasHeader(
                        'Duffel-Version',
                        'v2',
                    )
                ) {
                    return false;
                }

                return true;
            },
        );
    }

    private function assertServiceUnavailable(
        callable $callback,
    ): void {
        try {
            $callback();

            $this->fail(
                'Expected supplier reconciliation to fail closed.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }
    }
}