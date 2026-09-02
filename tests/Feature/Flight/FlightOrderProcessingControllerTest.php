<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightOrderProvider;
use App\Exceptions\Flight\FlightOrderProcessingException;
use App\Models\User;
use App\Services\Flight\FlightBookingConfirmationIntentStore;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class ProcessingSignalTestFlightOrderProvider implements FlightOrderProvider
{
    public static int $calls = 0;

    public static function reset(): void
    {
        self::$calls = 0;
    }

    public function createFromTrustedConfirmationIntent(
        array $trustedConfirmationIntent
    ): array {
        self::$calls++;

        throw (new FlightOrderProcessingException(
            'processing_signal',
        ))->withAttemptReference(
            str_repeat(
                'A',
                64,
            ),
        );
    }
}

final class FlightOrderProcessingControllerTest extends TestCase
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

        ProcessingSignalTestFlightOrderProvider::reset();

        config()->set(
            'flight_orders.http_execution_enabled',
            true,
        );

        config()->set(
            'flight_orders.providers.processing_signal',
            ProcessingSignalTestFlightOrderProvider::class,
        );
    }

    public function test_processing_returns_safe_202_consumes_intent_and_cannot_replay(): void
    {
        Http::fake();

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

        $store =
            app(
                FlightBookingConfirmationIntentStore::class
            );

        $userId =
            (int) $user->getAuthIdentifier();

        $token =
            $store->put(
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
                        'off_processing_signal_1',

                    'provider' =>
                        'processing_signal',

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
                            'processing@example.test',

                        'phone_number' =>
                            '+14155550101',
                    ],
                ],
                [
                    'status' =>
                        'revalidated',

                    'provider' =>
                        'processing_signal',

                    'live_revalidation' =>
                        true,

                    'price_changed' =>
                        false,
                ],
            );

        $response =
            $this
                ->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.orders.execute'
                    ),
                    [
                        'confirmation_intent_token' =>
                            $token,
                    ],
                )
                ->assertStatus(202)
                ->assertJsonPath(
                    'data.status',
                    'processing',
                )
                ->assertJsonPath(
                    'data.provider',
                    'processing_signal',
                )
                ->assertJsonPath(
                    'data.live_order_creation',
                    true,
                )
                ->assertJsonPath(
                    'data.order_created',
                    false,
                )
                ->assertJsonPath(
                    'data.confirmation_intent_consumed',
                    true,
                )
                ->assertJsonPath(
                    'data.attempt_reference',
                    str_repeat(
                        'A',
                        64,
                    ),
                )
                ->assertHeader(
                    'Cache-Control',
                    'no-store, private',
                );

        $this->assertSame(
            1,
            ProcessingSignalTestFlightOrderProvider::$calls,
        );

        $this->assertNull(
            $store->get(
                $userId,
                $token,
            ),
        );

        $data =
            $response->json(
                'data',
            );

        $this->assertIsArray(
            $data,
        );

        $this->assertArrayNotHasKey(
            'supplier_offer_id',
            $data,
        );

        $this->assertArrayNotHasKey(
            'supplier_order_id',
            $data,
        );

        $attemptReference =
            $data['attempt_reference']
                ?? null;

        $this->assertIsString(
            $attemptReference,
        );

        $this->assertSame(
            64,
            strlen(
                $attemptReference,
            ),
        );

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]{64}$/',
            $attemptReference,
        );

        $content =
            $response->getContent();

        $this->assertStringNotContainsString(
            $token,
            $content,
        );

        $this->assertStringNotContainsString(
            'processing@example.test',
            $content,
        );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.orders.execute'
                ),
                [
                    'confirmation_intent_token' =>
                        $token,
                ],
            )
            ->assertGone();

        $this->assertSame(
            1,
            ProcessingSignalTestFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_processing_signal_fails_closed_and_typed_catch_precedes_generic_catch(): void
    {
        $exception =
            new FlightOrderProcessingException(
                ' DUFFEL ',
            );

        $this->assertInstanceOf(
            ServiceUnavailableHttpException::class,
            $exception,
        );

        $this->assertSame(
            'duffel',
            $exception->provider(),
        );

        $source =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderExecutionController.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $typed =
            strpos(
                $source,
                'FlightOrderProcessingException $exception',
            );

        $generic =
            strpos(
                $source,
                'ServiceUnavailableHttpException',
                $typed === false
                    ? 0
                    : $typed + 1,
            );

        $this->assertNotFalse(
            $typed,
        );

        $this->assertNotFalse(
            $generic,
        );

        $this->assertGreaterThan(
            $typed,
            $generic,
        );

        $this->assertStringContainsString(
            "'processing'",
            $source,
        );

        $this->assertStringContainsString(
            '], 202)',
            $source,
        );
    }
}