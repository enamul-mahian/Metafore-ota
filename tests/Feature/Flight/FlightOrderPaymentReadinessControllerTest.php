<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FlightOrderPaymentReadinessControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_is_get_and_requires_booking_security_middleware(): void
    {
        $route = Route::getRoutes()
            ->getByName(
                'flights.bookings.orders.attempts.payment-readiness.show',
            );

        $this->assertNotNull($route);

        $this->assertContains(
            'GET',
            $route->methods(),
        );

        $this->assertSame(
            'flights/bookings/orders/attempts/{attemptReference}/payment-readiness',
            $route->uri(),
        );

        $middleware = $route->gatherMiddleware();

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

    public function test_guest_cannot_read_payment_readiness(): void
    {
        Http::fake();

        $response = $this->get(
            route(
                'flights.bookings.orders.attempts.payment-readiness.show',
                [
                    'attemptReference' => str_repeat(
                        'A',
                        64,
                    ),
                ],
            ),
        );

        $response->assertRedirect();

        Http::assertNothingSent();
    }

    public function test_verified_user_without_permission_is_forbidden(): void
    {
        Http::fake();

        $user = User::factory()
            ->create([
                'email_verified_at' => now(),
            ]);

        $response = $this->actingAs($user)
            ->getJson(
                route(
                    'flights.bookings.orders.attempts.payment-readiness.show',
                    [
                        'attemptReference' => str_repeat(
                            'A',
                            64,
                        ),
                    ],
                ),
            );

        $response->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_owner_can_read_only_safe_current_payment_readiness(): void
    {
        $this->configureDuffel();

        $user = $this->userWithFlightPermission();

        $attempt = $this->createdAttempt(
            $user->id,
            'duffel',
            'off_paymenthttp1',
            'ord_paymenthttp1',
        );

        $deadline = now()
            ->addHours(6)
            ->toIso8601String();

        Http::fake([
            'https://api.duffel.test/air/orders/ord_paymenthttp1' =>
                Http::response([
                    'data' => [
                        'id' => 'ord_paymenthttp1',
                        'total_amount' => '88400.00',
                        'total_currency' => 'BDT',
                        'payment_status' => [
                            'awaiting_payment' => true,
                            'payment_required_by' => $deadline,
                        ],
                    ],
                ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson(
                route(
                    'flights.bookings.orders.attempts.payment-readiness.show',
                    [
                        'attemptReference' => $attempt['reference'],
                    ],
                ),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'ready_for_payment',
            )
            ->assertJsonPath(
                'data.provider',
                'duffel',
            )
            ->assertJsonPath(
                'data.awaiting_payment',
                true,
            )
            ->assertJsonPath(
                'data.total_amount',
                '88400.00',
            )
            ->assertJsonPath(
                'data.total_currency',
                'BDT',
            )
            ->assertJsonPath(
                'data.payment_required_by',
                $deadline,
            );

        $payload = $response->json('data');

        $this->assertIsArray($payload);

        $this->assertArrayNotHasKey(
            'supplier_order_id',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'supplier_offer_id',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'attempt_reference',
            $payload,
        );

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get(
                'Cache-Control',
            ),
        );

        Http::assertSent(
            function (HttpRequest $request): bool {
                return $request->method() === 'GET'
                    && $request->url()
                        === 'https://api.duffel.test/air/orders/ord_paymenthttp1';
            },
        );

        Http::assertSentCount(1);
    }

    public function test_processing_unknown_malformed_and_cross_user_references_fail_before_supplier(): void
    {
        $this->configureDuffel();

        $owner = $this->userWithFlightPermission();
        $otherUser = $this->userWithFlightPermission();

        $store = app(
            FlightOrderAttemptRecordStore::class,
        );

        $processing = $store->createProcessing(
            $owner->id,
            'duffel',
            'off_paymentprocessing1',
        );

        $this->assertIsArray($processing);

        $created = $this->createdAttempt(
            $owner->id,
            'duffel',
            'off_paymentcross1',
            'ord_paymentcross1',
        );

        $cases = [
            [
                'user' => $owner,
                'reference' => $processing['reference'],
            ],
            [
                'user' => $owner,
                'reference' => 'bad-reference',
            ],
            [
                'user' => $owner,
                'reference' => str_repeat(
                    'Z',
                    64,
                ),
            ],
            [
                'user' => $otherUser,
                'reference' => $created['reference'],
            ],
        ];

        foreach ($cases as $case) {
            Http::fake();

            $response = $this->actingAs(
                $case['user'],
            )->getJson(
                route(
                    'flights.bookings.orders.attempts.payment-readiness.show',
                    [
                        'attemptReference' => $case['reference'],
                    ],
                ),
            );

            $response
                ->assertNotFound()
                ->assertExactJson([
                    'message' =>
                        'Flight order payment readiness is unavailable.',
                ]);

            Http::assertNothingSent();
        }
    }

    public function test_supplier_failure_returns_sanitized_503(): void
    {
        $this->configureDuffel();

        $user = $this->userWithFlightPermission();

        $attempt = $this->createdAttempt(
            $user->id,
            'duffel',
            'off_paymentfailure1',
            'ord_paymentfailure1',
        );

        Http::fake([
            '*' => Http::response([
                'errors' => [
                    [
                        'message' =>
                            'secret supplier failure detail',
                    ],
                ],
            ], 500),
        ]);

        $response = $this->actingAs($user)
            ->getJson(
                route(
                    'flights.bookings.orders.attempts.payment-readiness.show',
                    [
                        'attemptReference' =>
                            $attempt['reference'],
                    ],
                ),
            );

        $response
            ->assertStatus(503)
            ->assertExactJson([
                'message' =>
                    'Flight order payment readiness is temporarily unavailable.',
            ]);

        $this->assertStringNotContainsString(
            'secret supplier failure detail',
            $response->getContent(),
        );

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get(
                'Cache-Control',
            ),
        );

        Http::assertSentCount(1);
    }

    public function test_controller_has_no_supplier_payment_ticket_or_persistence_mutation(): void
    {
        $source = file_get_contents(
            app_path(
                'Http/Controllers/Flight/FlightOrderPaymentReadinessController.php',
            ),
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'FlightOrderPaymentReadinessService',
            $source,
        );

        foreach ([
            '/air/payments',
            '/air/orders',
            '->post(',
            'supplier_order_id',
            'supplier_offer_id',
            'markCreated(',
            'markFailed(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function userWithFlightPermission(): User
    {
        app(
            PermissionRegistrar::class,
        )->forgetCachedPermissions();

        $permission = Permission::findOrCreate(
            'flights.book',
            'web',
        );

        $user = User::factory()
            ->create([
                'email_verified_at' => now(),
            ]);

        $user->givePermissionTo(
            $permission,
        );

        return $user;
    }

    /**
     * @return array{
     *     reference: string
     * }
     */
    private function createdAttempt(
        int $userId,
        string $provider,
        string $supplierOfferId,
        string $supplierOrderId,
    ): array {
        $store = app(
            FlightOrderAttemptRecordStore::class,
        );

        $processing = $store->createProcessing(
            $userId,
            $provider,
            $supplierOfferId,
        );

        $this->assertIsArray($processing);

        $created = $store->markCreated(
            $provider,
            $supplierOfferId,
            $supplierOrderId,
        );

        $this->assertNotNull($created);

        return [
            'reference' => $processing['reference'],
        ];
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