<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use App\Services\Flight\FlightOrderPaymentAttemptRecordStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FlightOrderPaymentReconciliationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_are_authenticated_and_use_separate_get_and_post_boundaries(): void
    {
        $status =
            Route::getRoutes()
                ->getByName(
                    'flights.bookings.orders.payments.attempts.show',
                );

        $reconcile =
            Route::getRoutes()
                ->getByName(
                    'flights.bookings.orders.payments.attempts.reconcile',
                );

        $this->assertNotNull($status);
        $this->assertNotNull($reconcile);

        $this->assertContains(
            'GET',
            $status->methods(),
        );

        $this->assertSame(
            ['POST'],
            $reconcile->methods(),
        );

        foreach ([
            $status,
            $reconcile,
        ] as $route) {
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
        }
    }

    public function test_local_status_get_never_calls_supplier(): void
    {
        $user =
            $this->userWithPermission();

        $payment =
            $this->processingPayment(
                $user->id,
                'off_statushttp1',
                'ord_statushttp1',
                '20.00',
                'USD',
            );

        Http::fake();

        $response =
            $this->actingAs($user)
                ->getJson(
                    route(
                        'flights.bookings.orders.payments.attempts.show',
                        [
                            'attemptReference' =>
                                $payment['reference'],
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'processing',
            )
            ->assertJsonPath(
                'data.provider',
                'duffel',
            );

        Http::assertNothingSent();
    }

    public function test_explicit_reconciliation_can_return_processing_202(): void
    {
        $this->configureDuffel();

        $user =
            $this->userWithPermission();

        $payment =
            $this->processingPayment(
                $user->id,
                'off_processinghttp1',
                'ord_processinghttp1',
                '30.00',
                'GBP',
            );

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [],
                ], 200),
        ]);

        $response =
            $this->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.orders.payments.attempts.reconcile',
                        [
                            'attemptReference' =>
                                $payment['reference'],
                        ],
                    ),
                );

        $response
            ->assertStatus(202)
            ->assertJsonPath(
                'data.status',
                'processing',
            )
            ->assertJsonPath(
                'data.provider',
                'duffel',
            );

        Http::assertSentCount(1);
    }

    public function test_explicit_reconciliation_can_return_terminal_success_without_exposing_supplier_ids(): void
    {
        $this->configureDuffel();

        $user =
            $this->userWithPermission();

        $payment =
            $this->processingPayment(
                $user->id,
                'off_successhttp1',
                'ord_successhttp1',
                '91.50',
                'GBP',
            );

        Http::fake([
            '*' =>
                Http::response([
                    'data' => [
                        [
                            'id' =>
                                'pay_successhttp1',

                            'order_id' =>
                                'ord_successhttp1',

                            'type' =>
                                'balance',

                            'amount' =>
                                '91.50',

                            'currency' =>
                                'GBP',

                            'status' =>
                                'succeeded',
                        ],
                    ],
                ], 200),
        ]);

        $response =
            $this->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.orders.payments.attempts.reconcile',
                        [
                            'attemptReference' =>
                                $payment['reference'],
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'status' =>
                        'succeeded',

                    'provider' =>
                        'duffel',
                ],
            ]);

        $this->assertStringNotContainsString(
            'pay_successhttp1',
            $response->getContent(),
        );

        $this->assertStringNotContainsString(
            'ord_successhttp1',
            $response->getContent(),
        );

        Http::assertSentCount(1);
    }

    public function test_unknown_malformed_and_cross_user_references_are_generic_and_do_not_hit_supplier(): void
    {
        $this->configureDuffel();

        $owner =
            $this->userWithPermission();

        $other =
            $this->userWithPermission();

        $payment =
            $this->processingPayment(
                $owner->id,
                'off_crosshttp1',
                'ord_crosshttp1',
                '20.00',
                'USD',
            );

        $cases = [
            [
                'user' =>
                    $owner,

                'reference' =>
                    'bad-reference',
            ],
            [
                'user' =>
                    $owner,

                'reference' =>
                    str_repeat(
                        'Z',
                        64,
                    ),
            ],
            [
                'user' =>
                    $other,

                'reference' =>
                    $payment['reference'],
            ],
        ];

        foreach ($cases as $case) {
            Http::fake();

            $response =
                $this->actingAs(
                    $case['user'],
                )
                    ->postJson(
                        route(
                            'flights.bookings.orders.payments.attempts.reconcile',
                            [
                                'attemptReference' =>
                                    $case['reference'],
                            ],
                        ),
                    );

            $response
                ->assertNotFound()
                ->assertExactJson([
                    'message' =>
                        'Flight payment attempt is unavailable.',
                ]);

            Http::assertNothingSent();
        }
    }

    public function test_supplier_failure_returns_sanitized_503_and_preserves_processing(): void
    {
        $this->configureDuffel();

        $user =
            $this->userWithPermission();

        $payment =
            $this->processingPayment(
                $user->id,
                'off_failurehttp1',
                'ord_failurehttp1',
                '30.00',
                'GBP',
            );

        Http::fake([
            '*' =>
                Http::response(
                    [
                        'errors' => [
                            [
                                'message' =>
                                    'secret supplier failure',
                            ],
                        ],
                    ],
                    500,
                ),
        ]);

        $response =
            $this->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.orders.payments.attempts.reconcile',
                        [
                            'attemptReference' =>
                                $payment['reference'],
                        ],
                    ),
                );

        $response
            ->assertStatus(503)
            ->assertExactJson([
                'message' =>
                    'Flight payment reconciliation is temporarily unavailable.',
            ]);

        $this->assertStringNotContainsString(
            'secret supplier failure',
            $response->getContent(),
        );

        Http::assertSentCount(1);
    }

    public function test_http_recovery_boundary_has_no_payment_create_or_ticketing_contract(): void
    {
        $statusSource =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderPaymentAttemptStatusController.php',
                ),
            );

        $reconcileSource =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderPaymentReconciliationController.php',
                ),
            );

        $providerSource =
            file_get_contents(
                app_path(
                    'Services/Flight/DuffelFlightOrderPaymentReconciliationProvider.php',
                ),
            );

        foreach ([
            $statusSource,
            $reconcileSource,
            $providerSource,
        ] as $source) {
            $this->assertIsString(
                $source,
            );

            $this->assertStringNotContainsString(
                "->post(",
                $source,
            );

            $this->assertStringNotContainsString(
                '->retry(',
                $source,
            );

            $this->assertStringNotContainsString(
                'ticket',
                strtolower(
                    $source,
                ),
            );
        }

        $this->assertStringContainsString(
            "->get(",
            $providerSource,
        );
    }

    /**
     * @return array{
     *     reference: string
     * }
     */
    private function processingPayment(
        int $userId,
        string $supplierOfferId,
        string $supplierOrderId,
        string $amount,
        string $currency,
    ): array {
        $orderStore =
            app(
                FlightOrderAttemptRecordStore::class,
            );

        $order =
            $orderStore->createProcessing(
                $userId,
                'duffel',
                $supplierOfferId,
            );

        $this->assertIsArray(
            $order,
        );

        $createdOrder =
            $orderStore->markCreated(
                'duffel',
                $supplierOfferId,
                $supplierOrderId,
            );

        $this->assertNotNull(
            $createdOrder,
        );

        $paymentStore =
            app(
                FlightOrderPaymentAttemptRecordStore::class,
            );

        $payment =
            $paymentStore->createProcessing(
                $userId,
                (int) $createdOrder->getKey(),
                'duffel',
                $supplierOrderId,
                'balance',
                $amount,
                $currency,
            );

        $this->assertIsArray(
            $payment,
        );

        return [
            'reference' =>
                $payment['reference'],
        ];
    }

    private function userWithPermission(): User
    {
        app(
            PermissionRegistrar::class,
        )->forgetCachedPermissions();

        $permission =
            Permission::findOrCreate(
                'flights.book',
                'web',
            );

        $user =
            User::factory()
                ->create([
                    'email_verified_at' =>
                        now(),
                ]);

        $user->givePermissionTo(
            $permission,
        );

        return $user;
    }

    private function configureDuffel(): void
    {
        config()->set(
            'flight.duffel.access_token',
            'test-duffel-token',
        );

        config()->set(
            'flight.duffel.base_url',
            'https://api.duffel.test',
        );

        config()->set(
            'flight.duffel.api_version',
            'v2',
        );

        config()->set(
            'flight.duffel.http_timeout',
            5,
        );
    }
}