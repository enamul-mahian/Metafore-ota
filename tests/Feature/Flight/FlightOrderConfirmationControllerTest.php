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

final class FlightOrderConfirmationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_is_secure_read_only_get(): void
    {
        $route =
            Route::getRoutes()
                ->getByName(
                    'flights.bookings.orders.attempts.confirmation.show',
                );

        $this->assertNotNull($route);

        $this->assertSame(
            ['GET', 'HEAD'],
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
    }

    public function test_owner_with_succeeded_payment_can_load_safe_booking_confirmation(): void
    {
        $this->configureDuffel();

        $user =
            $this->userWithPermission();

        $order =
            $this->createdOrder(
                $user->id,
                'off_confirmation1',
                'ord_confirmation1',
            );

        $this->succeededPayment(
            $user->id,
            $order['id'],
            'ord_confirmation1',
            'pay_confirmation1',
        );

        Http::fake([
            'https://api.duffel.test/air/orders/ord_confirmation1' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_confirmation1',

                        'booking_reference' =>
                            'ABC123',

                        'payment_status' => [
                            'awaiting_payment' =>
                                false,
                        ],

                        'passengers' => [
                            [
                                'email' =>
                                    'private@example.test',
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $response =
            $this->actingAs($user)
                ->getJson(
                    route(
                        'flights.bookings.orders.attempts.confirmation.show',
                        [
                            'attemptReference' =>
                                $order['reference'],
                        ],
                    ),
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'confirmed',
            )
            ->assertJsonPath(
                'data.provider',
                'duffel',
            )
            ->assertJsonPath(
                'data.booking_reference',
                'ABC123',
            );

        $this->assertStringNotContainsString(
            'ord_confirmation1',
            $response->getContent(),
        );

        $this->assertStringNotContainsString(
            'pay_confirmation1',
            $response->getContent(),
        );

        $this->assertStringNotContainsString(
            'private@example.test',
            $response->getContent(),
        );

        Http::assertSentCount(1);
    }

    public function test_payment_must_be_succeeded_before_supplier_confirmation_read(): void
    {
        $user =
            $this->userWithPermission();

        $order =
            $this->createdOrder(
                $user->id,
                'off_confirmation2',
                'ord_confirmation2',
            );

        Http::fake();

        $this->actingAs($user)
            ->getJson(
                route(
                    'flights.bookings.orders.attempts.confirmation.show',
                    [
                        'attemptReference' =>
                            $order['reference'],
                    ],
                ),
            )
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_cross_user_reference_never_reaches_supplier(): void
    {
        $owner =
            $this->userWithPermission();

        $other =
            $this->userWithPermission();

        $order =
            $this->createdOrder(
                $owner->id,
                'off_confirmation3',
                'ord_confirmation3',
            );

        $this->succeededPayment(
            $owner->id,
            $order['id'],
            'ord_confirmation3',
            'pay_confirmation3',
        );

        Http::fake();

        $this->actingAs($other)
            ->getJson(
                route(
                    'flights.bookings.orders.attempts.confirmation.show',
                    [
                        'attemptReference' =>
                            $order['reference'],
                    ],
                ),
            )
            ->assertNotFound();

        Http::assertNothingSent();
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

    /**
     * @return array{
     *     id: int,
     *     reference: string
     * }
     */
    private function createdOrder(
        int $userId,
        string $supplierOfferId,
        string $supplierOrderId,
    ): array {
        $store =
            app(
                FlightOrderAttemptRecordStore::class,
            );

        $processing =
            $store->createProcessing(
                $userId,
                'duffel',
                $supplierOfferId,
            );

        $this->assertIsArray(
            $processing,
        );

        $created =
            $store->markCreated(
                'duffel',
                $supplierOfferId,
                $supplierOrderId,
            );

        $this->assertNotNull(
            $created,
        );

        return [
            'id' =>
                (int) $created->getKey(),

            'reference' =>
                $processing['reference'],
        ];
    }

    private function succeededPayment(
        int $userId,
        int $orderAttemptId,
        string $supplierOrderId,
        string $supplierPaymentId,
    ): void {
        $store =
            app(
                FlightOrderPaymentAttemptRecordStore::class,
            );

        $processing =
            $store->createProcessing(
                $userId,
                $orderAttemptId,
                'duffel',
                $supplierOrderId,
                'balance',
                '88.40',
                'USD',
            );

        $this->assertIsArray(
            $processing,
        );

        $succeeded =
            $store->markSucceeded(
                'duffel',
                $supplierOrderId,
                $supplierPaymentId,
            );

        $this->assertNotNull(
            $succeeded,
        );
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