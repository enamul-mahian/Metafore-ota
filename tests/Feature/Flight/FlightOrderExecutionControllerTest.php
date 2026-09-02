<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightOrderProvider;
use App\Models\User;
use App\Services\Flight\FlightBookingConfirmationIntentStore;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class HttpExecutionTestFlightOrderProvider implements FlightOrderProvider
{
    public static int $calls = 0;

    public static bool $throwSupplierUnavailable = false;

    /**
     * @var array<string, mixed>
     */
    public static array $lastIntent = [];

    public static function reset(): void
    {
        self::$calls = 0;
        self::$throwSupplierUnavailable = false;
        self::$lastIntent = [];
    }

    public function createFromTrustedConfirmationIntent(
        array $trustedConfirmationIntent
    ): array {
        self::$calls++;

        self::$lastIntent =
            $trustedConfirmationIntent;

        if (self::$throwSupplierUnavailable) {
            throw new ServiceUnavailableHttpException(
                60,
                'supplier-secret-upstream-detail',
            );
        }

        return [
            'status' => 'created',
            'live_order_creation' => true,
            'order_created' => true,
        ];
    }
}

final class FlightOrderExecutionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->seed(
            RolePermissionSeeder::class
        );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        HttpExecutionTestFlightOrderProvider::reset();

        config()->set(
            'flight_orders.http_execution_enabled',
            false,
        );

        config()->set(
            'flight_orders.providers.http_execution_test',
            HttpExecutionTestFlightOrderProvider::class,
        );
    }

    public function test_guest_cannot_execute_confirmation_intent(): void
    {
        Http::fake();

        $this
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                $this->validPayload(
                    str_repeat('a', 64),
                ),
            )
            ->assertUnauthorized();

        $this->assertSame(
            0,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_verified_user_without_flights_book_permission_is_forbidden(): void
    {
        Http::fake();

        $user =
            User::factory()->create([
                'email_verified_at' =>
                    now(),
            ]);

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                $this->validPayload(
                    str_repeat('a', 64),
                ),
            )
            ->assertForbidden();

        $this->assertSame(
            0,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_execution_request_requires_exact_confirmation_token_shape(): void
    {
        Http::fake();

        $user =
            $this->bookableCustomer();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                [
                    'confirmation_intent_token' =>
                        'short',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'confirmation_intent_token',
            ]);

        $this->assertSame(
            0,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_http_execution_gate_is_disabled_by_default_without_consuming_intent(): void
    {
        Http::fake();

        $user =
            $this->bookableCustomer();

        $store =
            $this->store();

        $token =
            $this->putIntent(
                $store,
                (int) $user->getAuthIdentifier(),
            );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                $this->validPayload(
                    $token,
                ),
            )
            ->assertStatus(503)
            ->assertJsonPath(
                'data.status',
                'execution_disabled',
            )
            ->assertJsonPath(
                'data.confirmation_intent_consumed',
                false,
            )
            ->assertJsonPath(
                'data.order_created',
                false,
            )
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            );

        $this->assertIsArray(
            $store->get(
                (int) $user->getAuthIdentifier(),
                $token,
            ),
        );

        $this->assertSame(
            0,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_enabled_execution_with_unknown_token_returns_gone_without_provider_call(): void
    {
        Http::fake();

        $this->enableHttpExecution();

        $user =
            $this->bookableCustomer();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                $this->validPayload(
                    str_repeat('b', 64),
                ),
            )
            ->assertGone()
            ->assertJsonPath(
                'data.status',
                'confirmation_intent_unavailable',
            )
            ->assertJsonPath(
                'data.confirmation_intent_consumed',
                false,
            );

        $this->assertSame(
            0,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_wrong_user_cannot_execute_or_consume_owner_intent(): void
    {
        Http::fake();

        $this->enableHttpExecution();

        $owner =
            $this->bookableCustomer();

        $other =
            $this->bookableCustomer();

        $store =
            $this->store();

        $token =
            $this->putIntent(
                $store,
                (int) $owner->getAuthIdentifier(),
            );

        $this
            ->actingAs($other)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                $this->validPayload(
                    $token,
                ),
            )
            ->assertGone()
            ->assertJsonPath(
                'data.confirmation_intent_consumed',
                false,
            );

        $this->assertIsArray(
            $store->get(
                (int) $owner->getAuthIdentifier(),
                $token,
            ),
        );

        $this->assertSame(
            0,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_customer_can_execute_once_and_receives_only_safe_normalized_result(): void
    {
        Http::fake();

        $this->enableHttpExecution();

        $user =
            $this->bookableCustomer();

        $store =
            $this->store();

        $userId =
            (int) $user->getAuthIdentifier();

        $token =
            $this->putIntent(
                $store,
                $userId,
            );

        $response =
            $this
                ->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.orders.execute'
                    ),
                    $this->validPayload(
                        $token,
                    ),
                )
                ->assertCreated()
                ->assertJsonPath(
                    'data.status',
                    'created',
                )
                ->assertJsonPath(
                    'data.provider',
                    'http_execution_test',
                )
                ->assertJsonPath(
                    'data.live_order_creation',
                    true,
                )
                ->assertJsonPath(
                    'data.order_created',
                    true,
                )
                ->assertJsonPath(
                    'data.confirmation_intent_consumed',
                    true,
                )
                ->assertJsonMissingPath(
                    'data.confirmation_intent_token'
                )
                ->assertJsonMissingPath(
                    'data.offer'
                )
                ->assertJsonMissingPath(
                    'data.travelers'
                )
                ->assertHeader(
                    'Cache-Control',
                    'no-store, private',
                );

        $this->assertSame(
            1,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        $this->assertNull(
            $store->get(
                $userId,
                $token,
            ),
        );

        $content =
            $response->getContent();

        $this->assertStringNotContainsString(
            'safe@example.test',
            $content,
        );

        $this->assertStringNotContainsString(
            $token,
            $content,
        );

        Http::assertNothingSent();
    }

    public function test_replay_of_consumed_confirmation_intent_cannot_delegate_twice(): void
    {
        Http::fake();

        $this->enableHttpExecution();

        $user =
            $this->bookableCustomer();

        $userId =
            (int) $user->getAuthIdentifier();

        $token =
            $this->putIntent(
                $this->store(),
                $userId,
            );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                $this->validPayload(
                    $token,
                ),
            )
            ->assertCreated();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                $this->validPayload(
                    $token,
                ),
            )
            ->assertGone()
            ->assertJsonPath(
                'data.confirmation_intent_consumed',
                false,
            );

        $this->assertSame(
            1,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_supplier_exception_is_generic_consumed_and_not_replayable(): void
    {
        Http::fake();

        $this->enableHttpExecution();

        HttpExecutionTestFlightOrderProvider::$throwSupplierUnavailable =
            true;

        $user =
            $this->bookableCustomer();

        $userId =
            (int) $user->getAuthIdentifier();

        $token =
            $this->putIntent(
                $this->store(),
                $userId,
            );

        $response =
            $this
                ->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.orders.execute'
                    ),
                    $this->validPayload(
                        $token,
                    ),
                )
                ->assertStatus(503)
                ->assertJsonPath(
                    'data.status',
                    'unavailable',
                )
                ->assertJsonPath(
                    'data.confirmation_intent_consumed',
                    true,
                )
                ->assertJsonPath(
                    'data.order_created',
                    false,
                )
                ->assertHeader(
                    'Cache-Control',
                    'no-store, private',
                );

        $this->assertStringNotContainsString(
            'supplier-secret-upstream-detail',
            $response->getContent(),
        );

        $this->assertSame(
            1,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        HttpExecutionTestFlightOrderProvider::$throwSupplierUnavailable =
            false;

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                $this->validPayload(
                    $token,
                ),
            )
            ->assertGone();

        $this->assertSame(
            1,
            HttpExecutionTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_controller_source_has_no_direct_supplier_payment_ticketing_or_persistence_boundary(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderExecutionController.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'FlightOrderExecutionService',
            $source,
        );

        $this->assertStringContainsString(
            "'flight_orders.http_execution_enabled'",
            $source,
        );

        $this->assertStringContainsString(
            'confirmation_intent_consumed',
            $source,
        );

        $this->assertStringContainsString(
            'no-store, private',
            $source,
        );

        foreach ([
            'Http::',
            '/air/orders',
            'DuffelFlightOrderProvider',
            'payment_intent',
            'ticket_number',
            'DB::',
            '->save(',
            '->insert(',
            '->create(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function enableHttpExecution(): void
    {
        config()->set(
            'flight_orders.http_execution_enabled',
            true,
        );
    }

    private function bookableCustomer(): User
    {
        $user =
            User::factory()->create([
                'email_verified_at' =>
                    now(),
            ]);

        $user->assignRole(
            'customer'
        );

        $this->assertTrue(
            $user->can(
                'flights.book'
            )
        );

        return $user;
    }

    private function store(): FlightBookingConfirmationIntentStore
    {
        return app(
            FlightBookingConfirmationIntentStore::class
        );
    }

    private function putIntent(
        FlightBookingConfirmationIntentStore $store,
        int $userId,
    ): string {
        return $store->put(
            $userId,
            [
                'trip_type' =>
                    'one_way',

                'origin' =>
                    'DAC',

                'destination' =>
                    'CXB',
            ],
            [
                'id' =>
                    'off_http_execution_safe_1',

                'provider' =>
                    'http_execution_test',

                'total_amount' =>
                    '15000.00',

                'currency' =>
                    'BDT',
            ],
            [
                [
                    'type' =>
                        'adult',

                    'title' =>
                        'mr',

                    'given_name' =>
                        'Safe',

                    'family_name' =>
                        'Traveler',

                    'date_of_birth' =>
                        '1990-01-01',

                    'gender' =>
                        'm',

                    'email' =>
                        'safe@example.test',

                    'phone_number' =>
                        '+14155550101',
                ],
            ],
            [
                'status' =>
                    'revalidated',

                'provider' =>
                    'http_execution_test',

                'live_revalidation' =>
                    true,

                'price_changed' =>
                    false,
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(
        string $token,
    ): array {
        return [
            'confirmation_intent_token' =>
                $token,
        ];
    }
}