<?php

namespace Tests\Feature\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FlightOrderReconciliationControllerTest extends TestCase
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

        app(
            PermissionRegistrar::class,
        )->forgetCachedPermissions();

        Permission::query()
            ->firstOrCreate([
                'name' =>
                    'flights.book',

                'guard_name' =>
                    'web',
            ]);

        app(
            PermissionRegistrar::class,
        )->forgetCachedPermissions();
    }

    public function test_route_is_a_separate_authenticated_post_action_and_status_route_remains_get(): void
    {
        $route =
            app('router')
                ->getRoutes()
                ->getByName(
                    'flights.bookings.orders.attempts.reconcile',
                );

        $this->assertNotNull(
            $route,
        );

        $this->assertSame(
            'flights/bookings/orders/attempts/{attemptReference}/reconcile',
            $route->uri(),
        );

        $this->assertContains(
            'POST',
            $route->methods(),
        );

        $this->assertNotContains(
            'GET',
            $route->methods(),
        );

        $middleware =
            $route->gatherMiddleware();

        $this->assertContains(
            'auth',
            $middleware,
        );

        $this->assertContains(
            'verified',
            $middleware,
        );

        $this->assertContains(
            'permission:flights.book',
            $middleware,
        );

        foreach ($middleware as $entry) {
            $this->assertStringNotContainsString(
                'throttle:',
                $entry,
            );
        }

        $statusRoute =
            app('router')
                ->getRoutes()
                ->getByName(
                    'flights.bookings.orders.attempts.show',
                );

        $this->assertNotNull(
            $statusRoute,
        );

        $this->assertSame(
            'flights/bookings/orders/attempts/{attemptReference}',
            $statusRoute->uri(),
        );

        $this->assertContains(
            'GET',
            $statusRoute->methods(),
        );

        $this->assertNotContains(
            'POST',
            $statusRoute->methods(),
        );
    }

    public function test_route_requires_auth_verified_and_flights_book_permission(): void
    {
        Http::fake();

        $reference =
            str_repeat(
                'A',
                64,
            );

        $this->postJson(
            $this->reconcileUrl(
                $reference,
            ),
        )->assertUnauthorized();

        $unverifiedUser =
            User::factory()
                ->create([
                    'email_verified_at' =>
                        null,
                ]);

        $unverifiedUser
            ->givePermissionTo(
                'flights.book',
            );

        $this->actingAs(
            $unverifiedUser,
        )->postJson(
            $this->reconcileUrl(
                $reference,
            ),
        )->assertForbidden();

        $verifiedWithoutPermission =
            User::factory()
                ->create([
                    'email_verified_at' =>
                        now(),
                ]);

        $this->actingAs(
            $verifiedWithoutPermission,
        )->postJson(
            $this->reconcileUrl(
                $reference,
            ),
        )->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_owner_can_explicitly_reconcile_processing_attempt_without_terminal_mutation(): void
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
            $this->bookableUser();

        $record =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_http_reconcile_processing_1',
            );

        $response =
            $this->actingAs(
                $user,
            )->postJson(
                $this->reconcileUrl(
                    $record['reference'],
                ),
            );

        $response
            ->assertStatus(
                202,
            )
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            )
            ->assertExactJson([
                'data' => [
                    'status' =>
                        FlightOrderAttempt::STATUS_PROCESSING,

                    'provider' =>
                        'duffel',
                ],

                'message' =>
                    'Flight order reconciliation is still processing.',
            ]);

        $content =
            $response->getContent();

        $this->assertStringNotContainsString(
            'off_http_reconcile_processing_1',
            $content,
        );

        $this->assertStringNotContainsString(
            'supplier_offer',
            $content,
        );

        $this->assertStringNotContainsString(
            'supplier_order',
            $content,
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
            'off_http_reconcile_processing_1',
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_exact_supplier_match_can_transition_processing_attempt_to_created(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'data' => [
                            [
                                'id' =>
                                    'ord_httpreconcile1',

                                'offer_id' =>
                                    'off_http_reconcile_created_1',
                            ],
                        ],
                    ],
                    200,
                ),
        ]);

        $user =
            $this->bookableUser();

        $record =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_http_reconcile_created_1',
            );

        $response =
            $this->actingAs(
                $user,
            )->postJson(
                $this->reconcileUrl(
                    $record['reference'],
                ),
            );

        $response
            ->assertOk()
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            )
            ->assertExactJson([
                'data' => [
                    'status' =>
                        FlightOrderAttempt::STATUS_CREATED,

                    'provider' =>
                        'duffel',
                ],

                'message' =>
                    'Flight order reconciliation confirmed the order.',
            ]);

        $content =
            $response->getContent();

        $this->assertStringNotContainsString(
            'off_http_reconcile_created_1',
            $content,
        );

        $this->assertStringNotContainsString(
            'ord_httpreconcile1',
            $content,
        );

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'id' =>
                    $record['attempt']->id,

                'status' =>
                    FlightOrderAttempt::STATUS_CREATED,

                'supplier_order_id' =>
                    'ord_httpreconcile1',
            ],
        );

        $attempt =
            FlightOrderAttempt::query()
                ->findOrFail(
                    $record['attempt']->id,
                );

        $this->assertNotNull(
            $attempt->resolved_at,
        );

        $this->assertExactSupplierRead(
            'off_http_reconcile_created_1',
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_unknown_malformed_and_cross_user_references_are_indistinguishable_and_do_not_hit_supplier(): void
    {
        Http::fake();

        $owner =
            $this->bookableUser();

        $otherUser =
            $this->bookableUser();

        $record =
            $this->processingAttempt(
                $owner,
                'duffel',
                'off_http_reconcile_owner_1',
            );

        $crossUser =
            $this->actingAs(
                $otherUser,
            )->postJson(
                $this->reconcileUrl(
                    $record['reference'],
                ),
            );

        $malformed =
            $this->actingAs(
                $owner,
            )->postJson(
                $this->reconcileUrl(
                    'invalid-reference',
                ),
            );

        $unknown =
            $this->actingAs(
                $owner,
            )->postJson(
                $this->reconcileUrl(
                    str_repeat(
                        'Z',
                        64,
                    ),
                ),
            );

        foreach ([
            $crossUser,
            $malformed,
            $unknown,
        ] as $response) {
            $response
                ->assertNotFound()
                ->assertHeader(
                    'Cache-Control',
                    'no-store, private',
                )
                ->assertExactJson([
                    'data' =>
                        null,

                    'message' =>
                        'Flight order reconciliation is unavailable.',
                ]);
        }

        $this->assertSame(
            $crossUser->getContent(),
            $malformed->getContent(),
        );

        $this->assertSame(
            $malformed->getContent(),
            $unknown->getContent(),
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

    public function test_already_terminal_created_and_failed_attempts_return_local_state_without_supplier_http(): void
    {
        Http::fake();

        $user =
            $this->bookableUser();

        $createdRecord =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_http_terminal_created_1',
            );

        $failedRecord =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_http_terminal_failed_1',
            );

        $store =
            app(
                FlightOrderAttemptRecordStore::class,
            );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $store->markCreated(
                'duffel',
                'off_http_terminal_created_1',
                'ord_http_terminal_existing_1',
            ),
        );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $store->markFailed(
                'duffel',
                'off_http_terminal_failed_1',
            ),
        );

        $createdResponse =
            $this->actingAs(
                $user,
            )->postJson(
                $this->reconcileUrl(
                    $createdRecord['reference'],
                ),
            );

        $failedResponse =
            $this->actingAs(
                $user,
            )->postJson(
                $this->reconcileUrl(
                    $failedRecord['reference'],
                ),
            );

        $createdResponse
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                FlightOrderAttempt::STATUS_CREATED,
            )
            ->assertJsonPath(
                'data.provider',
                'duffel',
            );

        $failedResponse
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                FlightOrderAttempt::STATUS_FAILED,
            )
            ->assertJsonPath(
                'data.provider',
                'duffel',
            );

        $this->assertStringNotContainsString(
            'ord_http_terminal_existing_1',
            $createdResponse->getContent(),
        );

        Http::assertNothingSent();
    }

    public function test_supplier_failure_returns_sanitized_503_and_preserves_processing_state(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders*' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'message' =>
                                    'Raw supplier failure detail.',
                            ],
                        ],
                    ],
                    503,
                ),
        ]);

        $user =
            $this->bookableUser();

        $record =
            $this->processingAttempt(
                $user,
                'duffel',
                'off_http_reconcile_failure_1',
            );

        $response =
            $this->actingAs(
                $user,
            )->postJson(
                $this->reconcileUrl(
                    $record['reference'],
                ),
            );

        $response
            ->assertStatus(
                503,
            )
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            )
            ->assertExactJson([
                'data' =>
                    null,

                'message' =>
                    'Flight order reconciliation is temporarily unavailable.',
            ]);

        $content =
            $response->getContent();

        $this->assertStringNotContainsString(
            'Raw supplier failure detail.',
            $content,
        );

        $this->assertStringNotContainsString(
            'off_http_reconcile_failure_1',
            $content,
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

        Http::assertSentCount(
            1,
        );
    }

    public function test_http_trigger_boundary_keeps_status_get_ui_and_background_automation_unwired(): void
    {
        $controllerSource =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderReconciliationController.php',
                ),
            );

        $statusSource =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderAttemptStatusController.php',
                ),
            );

        $routeSource =
            file_get_contents(
                base_path(
                    'routes/web.php',
                ),
            );

        $this->assertIsString(
            $controllerSource,
        );

        $this->assertIsString(
            $statusSource,
        );

        $this->assertIsString(
            $routeSource,
        );

        foreach ([
            'FlightOrderReconciliationService',
            '->reconcile(',
            'getAuthIdentifier()',
            'STATUS_PROCESSING',
            'STATUS_CREATED',
            'STATUS_FAILED',
            'no-store, private',
        ] as $requiredSignal) {
            $this->assertStringContainsString(
                $requiredSignal,
                $controllerSource,
            );
        }

        foreach ([
            'Http::',
            'readBySupplierOfferId(',
            'markCreated(',
            'markFailed(',
            '/air/orders',
            'supplier_offer_id',
            'supplier_order_id',
            'confirmation_intent',
            'dispatch(',
            'ShouldQueue',
            'Bus::',
            'Queue::',
        ] as $forbiddenSignal) {
            $this->assertStringNotContainsString(
                $forbiddenSignal,
                $controllerSource,
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
            'markCreated(',
            $statusSource,
        );

        $this->assertStringContainsString(
            'Route::post(',
            $routeSource,
        );

        $this->assertStringContainsString(
            '/flights/bookings/orders/attempts/{attemptReference}/reconcile',
            $routeSource,
        );

        $this->assertStringContainsString(
            'permission:flights.book',
            $routeSource,
        );

        $resources =
            base_path(
                'resources',
            );

        $iterator =
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $resources,
                    \FilesystemIterator::SKIP_DOTS,
                ),
            );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $source =
                file_get_contents(
                    $file->getPathname(),
                );

            if (! is_string($source)) {
                continue;
            }

            $this->assertStringNotContainsString(
                '/flights/bookings/orders/attempts/',
                $source,
            );

            $this->assertStringNotContainsString(
                'setInterval(',
                $source,
            );
        }
    }

    private function bookableUser(): User
    {
        $user =
            User::factory()
                ->create([
                    'email_verified_at' =>
                        now(),
                ]);

        $user->givePermissionTo(
            'flights.book',
        );

        return $user;
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

    private function reconcileUrl(
        string $attemptReference,
    ): string {
        return route(
            'flights.bookings.orders.attempts.reconcile',
            [
                'attemptReference' =>
                    $attemptReference,
            ],
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

                $path =
                    parse_url(
                        $url,
                        PHP_URL_PATH,
                    );

                if (
                    $path
                    !== '/air/orders'
                ) {
                    return false;
                }

                $queryString =
                    parse_url(
                        $url,
                        PHP_URL_QUERY,
                    );

                if (! is_string($queryString)) {
                    return false;
                }

                $query = [];

                parse_str(
                    $queryString,
                    $query,
                );

                $offerMatches =
                    ($query['offer_id'] ?? null)
                    === $supplierOfferId;

                $limitMatches =
                    (string) ($query['limit'] ?? '')
                    === '2';

                return $offerMatches
                    && $limitMatches;
            },
        );
    }
}