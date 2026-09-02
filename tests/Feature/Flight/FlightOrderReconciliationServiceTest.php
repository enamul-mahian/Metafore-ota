<?php

namespace Tests\Feature\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use App\Services\Flight\FlightOrderReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class FlightOrderReconciliationServiceTest extends TestCase
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

    public function test_unknown_malformed_and_cross_user_references_fail_before_supplier_read(): void
    {
        Http::fake();

        $owner =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $record =
            $this->processingAttempt(
                $owner,
                'duffel',
                'off_reconcile_owner_1',
            );

        $this->assertNull(
            $this->service()
                ->reconcile(
                    (int) $otherUser->id,
                    $record['reference'],
                ),
        );

        $this->assertNull(
            $this->service()
                ->reconcile(
                    (int) $owner->id,
                    'not-a-valid-reference',
                ),
        );

        $this->assertNull(
            $this->service()
                ->reconcile(
                    (int) $owner->id,
                    str_repeat(
                        'Z',
                        64,
                    ),
                ),
        );

        Http::assertNothingSent();

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'id' =>
                    $record['attempt']->id,

                'status' =>
                    FlightOrderAttempt::STATUS_PROCESSING,

                'supplier_order_id' =>
                    null,
            ],
        );
    }

    public function test_terminal_attempts_are_returned_from_local_state_without_supplier_read(): void
    {
        Http::fake();

        $user =
            User::factory()
                ->create();

        $createdRecord =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_terminal_created_1',
            );

        $failedRecord =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_terminal_failed_1',
            );

        $store =
            app(
                FlightOrderAttemptRecordStore::class,
            );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $store->markCreated(
                'duffel',
                'off_terminal_created_1',
                'terminal-created-order',
            ),
        );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $store->markFailed(
                'duffel',
                'off_terminal_failed_1',
            ),
        );

        $createdResult =
            $this->service()
                ->reconcile(
                    (int) $user->id,
                    $createdRecord['reference'],
                );

        $failedResult =
            $this->service()
                ->reconcile(
                    (int) $user->id,
                    $failedRecord['reference'],
                );

        $this->assertSame(
            [
                'status' =>
                    FlightOrderAttempt::STATUS_CREATED,

                'provider' =>
                    'duffel',
            ],
            $createdResult,
        );

        $this->assertSame(
            [
                'status' =>
                    FlightOrderAttempt::STATUS_FAILED,

                'provider' =>
                    'duffel',
            ],
            $failedResult,
        );

        $this->assertArrayNotHasKey(
            'supplier_order_id',
            $createdResult,
        );

        $this->assertArrayNotHasKey(
            'supplier_order_id',
            $failedResult,
        );

        Http::assertNothingSent();
    }

    public function test_processing_supplier_absence_remains_processing_without_mutation(): void
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

        $user =
            User::factory()
                ->create();

        $record =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_reconcile_processing_1',
            );

        $result =
            $this->service()
                ->reconcile(
                    (int) $user->id,
                    $record['reference'],
                );

        $this->assertSame(
            [
                'status' =>
                    FlightOrderAttempt::STATUS_PROCESSING,

                'provider' =>
                    'duffel',
            ],
            $result,
        );

        $this->assertArrayNotHasKey(
            'supplier_order_id',
            $result,
        );

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'id' =>
                    $record['attempt']->id,

                'status' =>
                    FlightOrderAttempt::STATUS_PROCESSING,

                'supplier_order_id' =>
                    null,

                'resolved_at' =>
                    null,
            ],
        );

        $this->assertExactSupplierRead(
            'off_reconcile_processing_1',
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_exact_created_supplier_result_atomically_marks_created(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'data' => [
                            [
                                'id' =>
                                    'ord_reconcileservice1',

                                'offer_id' =>
                                    'off_reconcile_created_1',
                            ],
                        ],
                    ],
                    200,
                ),
        ]);

        $user =
            User::factory()
                ->create();

        $record =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_reconcile_created_1',
            );

        $result =
            $this->service()
                ->reconcile(
                    (int) $user->id,
                    $record['reference'],
                );

        $this->assertSame(
            [
                'status' =>
                    FlightOrderAttempt::STATUS_CREATED,

                'provider' =>
                    'duffel',
            ],
            $result,
        );

        $this->assertArrayNotHasKey(
            'supplier_order_id',
            $result,
        );

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'id' =>
                    $record['attempt']->id,

                'status' =>
                    FlightOrderAttempt::STATUS_CREATED,

                'supplier_order_id' =>
                    'ord_reconcileservice1',
            ],
        );

        $resolvedAttempt =
            FlightOrderAttempt::query()
                ->findOrFail(
                    $record['attempt']->id,
                );

        $this->assertNotNull(
            $resolvedAttempt->resolved_at,
        );

        $this->assertExactSupplierRead(
            'off_reconcile_created_1',
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_unsupported_processing_provider_fails_closed_before_supplier_read(): void
    {
        Http::fake();

        $user =
            User::factory()
                ->create();

        $record =
            $this->processingAttempt(
                $user,
                'fixture',
                'off_fixture_reconcile_1',
            );

        $this->assertNull(
            $this->service()
                ->reconcile(
                    (int) $user->id,
                    $record['reference'],
                ),
        );

        Http::assertNothingSent();

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'id' =>
                    $record['attempt']->id,

                'status' =>
                    FlightOrderAttempt::STATUS_PROCESSING,

                'supplier_order_id' =>
                    null,

                'resolved_at' =>
                    null,
            ],
        );
    }

    public function test_supplier_failure_propagates_without_durable_mutation(): void
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

        $user =
            User::factory()
                ->create();

        $record =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_reconcile_supplier_failure_1',
            );

        $this->assertServiceUnavailable(
            fn () =>
                $this->service()
                    ->reconcile(
                        (int) $user->id,
                        $record['reference'],
                    ),
        );

        Http::assertSentCount(
            1,
        );

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'id' =>
                    $record['attempt']->id,

                'status' =>
                    FlightOrderAttempt::STATUS_PROCESSING,

                'supplier_order_id' =>
                    null,

                'resolved_at' =>
                    null,
            ],
        );
    }

    public function test_concurrent_terminal_resolution_is_reread_and_never_overwritten(): void
    {
        $user =
            User::factory()
                ->create();

        $supplierOfferId =
            'off_reconcile_race_1';

        $record =
            $this->processingAttempt(
                $user,
                'duffel',
                $supplierOfferId,
            );

        $attemptId =
            (int) $record['attempt']->id;

        Http::fake(
            function (
                Request $request,
            ) use (
                $attemptId,
                $supplierOfferId,
            ) {
                FlightOrderAttempt::query()
                    ->whereKey(
                        $attemptId,
                    )
                    ->update([
                        'status' =>
                            FlightOrderAttempt::STATUS_CREATED,

                        'supplier_order_id' =>
                            'race-existing-order',

                        'resolved_at' =>
                            now(),
                    ]);

                return Http::response(
                    [
                        'data' => [
                            [
                                'id' =>
                                    'ord_supplierrace1',

                                'offer_id' =>
                                    $supplierOfferId,
                            ],
                        ],
                    ],
                    200,
                );
            },
        );

        $result =
            $this->service()
                ->reconcile(
                    (int) $user->id,
                    $record['reference'],
                );

        $this->assertSame(
            [
                'status' =>
                    FlightOrderAttempt::STATUS_CREATED,

                'provider' =>
                    'duffel',
            ],
            $result,
        );

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'id' =>
                    $attemptId,

                'status' =>
                    FlightOrderAttempt::STATUS_CREATED,

                'supplier_order_id' =>
                    'race-existing-order',
            ],
        );

        $this->assertDatabaseMissing(
            'flight_order_attempts',
            [
                'id' =>
                    $attemptId,

                'supplier_order_id' =>
                    'ord_supplierrace1',
            ],
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_service_boundary_uses_server_identity_and_keeps_http_status_read_only(): void
    {
        $serviceSource =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightOrderReconciliationService.php',
                ),
            );

        $statusSource =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderAttemptStatusController.php',
                ),
            );

        $executionSource =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightOrderExecutionService.php',
                ),
            );

        $this->assertIsString(
            $serviceSource,
        );

        $this->assertIsString(
            $statusSource,
        );

        $this->assertIsString(
            $executionSource,
        );

        foreach ([
            'findForUser(',
            'readBySupplierOfferId(',
            'markCreated(',
            'providerFor(',
            "'duffel'",
            'STATUS_PROCESSING',
            'STATUS_CREATED',
            'STATUS_FAILED',
            'latestLocalResult(',
        ] as $requiredSignal) {
            $this->assertStringContainsString(
                $requiredSignal,
                $serviceSource,
            );
        }

        foreach ([
            'markFailed(',
            'Http::',
            '->get(',
            '->post(',
            '/air/orders',
            'Route::',
            'JsonResponse',
            'dispatch(',
            'ShouldQueue',
            'Bus::',
            'Queue::',
        ] as $forbiddenSignal) {
            $this->assertStringNotContainsString(
                $forbiddenSignal,
                $serviceSource,
            );
        }

        $this->assertStringContainsString(
            'findForUser(',
            $statusSource,
        );

        $this->assertStringNotContainsString(
            'FlightOrderReconciliationService',
            $statusSource,
        );

        $this->assertStringNotContainsString(
            'readBySupplierOfferId(',
            $statusSource,
        );

        $this->assertStringNotContainsString(
            'Http::',
            $statusSource,
        );

        $this->assertStringNotContainsString(
            'FlightOrderReconciliationService',
            $executionSource,
        );
    }

    /**
     * @return array{
     *     reference: string,
     *     attempt: FlightOrderAttempt
     * }
     */
    private function processingAttempt(
        User $user,
        string $provider,
        string $supplierOfferId,
    ): array {
        $record =
            app(
                FlightOrderAttemptRecordStore::class,
            )->createProcessing(
                (int) $user->id,
                $provider,
                $supplierOfferId,
            );

        $this->assertIsArray(
            $record,
        );

        $this->assertArrayHasKey(
            'reference',
            $record,
        );

        $this->assertArrayHasKey(
            'attempt',
            $record,
        );

        $this->assertIsString(
            $record['reference'],
        );

        $this->assertSame(
            64,
            strlen(
                $record['reference'],
            ),
        );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $record['attempt'],
        );

        return $record;
    }

    private function service(): FlightOrderReconciliationService
    {
        return app(
            FlightOrderReconciliationService::class,
        );
    }

    private function assertExactSupplierRead(
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
                'Expected reconciliation to fail closed.',
            );
        } catch (ServiceUnavailableHttpException $exception) {
            $this->assertSame(
                503,
                $exception->getStatusCode(),
            );
        }
    }
}