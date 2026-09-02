<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FlightOrderPaymentExecutionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_is_secure_post_boundary(): void
    {
        $route = Route::getRoutes()
            ->getByName(
                'flights.bookings.orders.attempts.payments.store',
            );

        $this->assertNotNull($route);

        $this->assertSame(
            ['POST'],
            $route->methods(),
        );

        $this->assertSame(
            'flights/bookings/orders/attempts/{attemptReference}/payments',
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

    public function test_owner_can_execute_processing_payment_without_browser_payment_authority(): void
    {
        $this->configureDuffel();

        config()->set(
            'flight_payments.duffel.type',
            'balance',
        );

        $user = $this->userWithPermission();

        $order = $this->createdOrder(
            $user->id,
            'off_paymenthttp1',
            'ord_paymenthttp1',
        );

        $deadline = now()
            ->addHours(5)
            ->toIso8601String();

        Http::fake([
            'https://api.duffel.test/air/orders/ord_paymenthttp1' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_paymenthttp1',

                        'total_amount' =>
                            '88.40',

                        'total_currency' =>
                            'USD',

                        'payment_status' => [
                            'awaiting_payment' =>
                                true,

                            'payment_required_by' =>
                                $deadline,
                        ],
                    ],
                ], 200),

            'https://api.duffel.test/air/payments' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'pay_paymenthttp1',

                        'status' =>
                            'pending',
                    ],
                ], 201),
        ]);

        $url = route(
            'flights.bookings.orders.attempts.payments.store',
            [
                'attemptReference' =>
                    $order['reference'],
            ],
        );

        $response = $this->actingAs($user)
            ->postJson($url);

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

        $reference = $response->json(
            'data.attempt_reference',
        );

        $this->assertIsString($reference);

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]{64}$/',
            $reference,
        );

        $data = $response->json('data');

        $this->assertIsArray($data);

        foreach ([
            'supplier_order_id',
            'supplier_payment_id',
            'amount',
            'currency',
            'type',
            'payment_type',
        ] as $forbidden) {
            $this->assertArrayNotHasKey(
                $forbidden,
                $data,
            );
        }

        $this->assertStringNotContainsString(
            'ord_paymenthttp1',
            $response->getContent(),
        );

        $this->assertStringNotContainsString(
            'pay_paymenthttp1',
            $response->getContent(),
        );

        Http::assertSentCount(2);

        /*
         * A replay must not perform another supplier request.
         */
        Http::fake();

        $this->actingAs($user)
            ->postJson($url)
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_browser_body_and_cross_user_reference_fail_before_supplier(): void
    {
        $owner = $this->userWithPermission();
        $other = $this->userWithPermission();

        $order = $this->createdOrder(
            $owner->id,
            'off_paymentauthority1',
            'ord_paymentauthority1',
        );

        $url = route(
            'flights.bookings.orders.attempts.payments.store',
            [
                'attemptReference' =>
                    $order['reference'],
            ],
        );

        Http::fake();

        $this->actingAs($owner)
            ->postJson(
                $url,
                [
                    'supplier_order_id' =>
                        'ord_attacker',

                    'supplier_payment_id' =>
                        'pay_attacker',

                    'provider' =>
                        'attacker',

                    'amount' =>
                        '0.01',

                    'currency' =>
                        'USD',

                    'type' =>
                        'card',
                ],
            )
            ->assertNotFound();

        Http::assertNothingSent();

        Http::fake();

        $this->actingAs($other)
            ->postJson($url)
            ->assertNotFound();

        Http::assertNothingSent();
    }

    private function userWithPermission(): User
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
     *     reference: string
     * }
     */
    private function createdOrder(
        int $userId,
        string $supplierOfferId,
        string $supplierOrderId,
    ): array {
        $store = app(
            FlightOrderAttemptRecordStore::class,
        );

        $processing = $store->createProcessing(
            $userId,
            'duffel',
            $supplierOfferId,
        );

        $this->assertIsArray($processing);

        $created = $store->markCreated(
            'duffel',
            $supplierOfferId,
            $supplierOrderId,
        );

        $this->assertNotNull($created);

        return [
            'reference' =>
                $processing['reference'],
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