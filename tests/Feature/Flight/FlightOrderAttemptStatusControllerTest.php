<?php

namespace Tests\Feature\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FlightOrderAttemptStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_route_requires_authentication_and_flights_book_permission(): void
    {
        Permission::findOrCreate(
            'flights.book',
            'web',
        );

        app(
            PermissionRegistrar::class,
        )->forgetCachedPermissions();

        $reference =
            str_repeat(
                'A',
                64,
            );

        $this->getJson(
            $this->statusUrl(
                $reference,
            ),
        )->assertUnauthorized();

        $user =
            User::factory()->create([
                'email_verified_at' =>
                    now(),
            ]);

        $this->actingAs(
            $user,
        )
            ->getJson(
                $this->statusUrl(
                    $reference,
                ),
            )
            ->assertForbidden();
    }

    public function test_owner_can_read_only_safe_local_processing_state(): void
    {
        Http::fake();

        $user =
            $this->userWithFlightBookPermission();

        $created =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'duffel',
                    'off_local_status_processing_1',
                );

        $this->assertIsArray(
            $created,
        );

        $reference =
            $created['reference'];

        $response =
            $this->actingAs(
                $user,
            )
                ->getJson(
                    $this->statusUrl(
                        $reference,
                    ),
                );

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'status' =>
                        FlightOrderAttempt::STATUS_PROCESSING,

                    'provider' =>
                        'duffel',
                ],

                'message' =>
                    'Flight order attempt status loaded.',
            ])
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            );

        $content =
            $response->getContent();

        $this->assertStringNotContainsString(
            $reference,
            $content,
        );

        foreach ([
            'off_local_status_processing_1',
            'reference_hash',
            'attempt_identity_hash',
            'supplier_offer_id',
            'supplier_order_id',
        ] as $forbiddenValue) {
            $this->assertStringNotContainsString(
                $forbiddenValue,
                $content,
            );
        }

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );

        Http::assertNothingSent();
    }

    public function test_endpoint_reads_created_and_failed_state_only_from_local_database(): void
    {
        Http::fake();

        $user =
            $this->userWithFlightBookPermission();

        $created =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'duffel',
                    'off_local_status_resolution_1',
                );

        $this->assertIsArray(
            $created,
        );

        $reference =
            $created['reference'];

        /** @var FlightOrderAttempt $attempt */
        $attempt =
            $created['attempt'];

        $attempt->forceFill([
            'status' =>
                FlightOrderAttempt::STATUS_CREATED,

            'supplier_order_id' =>
                'ord_server_only_status_1',

            'resolved_at' =>
                now(),
        ])->save();

        $createdResponse =
            $this->actingAs(
                $user,
            )
                ->getJson(
                    $this->statusUrl(
                        $reference,
                    ),
                );

        $createdResponse
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'status' =>
                        FlightOrderAttempt::STATUS_CREATED,

                    'provider' =>
                        'duffel',
                ],

                'message' =>
                    'Flight order attempt status loaded.',
            ])
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            );

        $this->assertStringNotContainsString(
            'ord_server_only_status_1',
            $createdResponse->getContent(),
        );

        $attempt->forceFill([
            'status' =>
                FlightOrderAttempt::STATUS_FAILED,

            'supplier_order_id' =>
                null,

            'resolved_at' =>
                now(),
        ])->save();

        $failedResponse =
            $this->actingAs(
                $user,
            )
                ->getJson(
                    $this->statusUrl(
                        $reference,
                    ),
                );

        $failedResponse
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'status' =>
                        FlightOrderAttempt::STATUS_FAILED,

                    'provider' =>
                        'duffel',
                ],

                'message' =>
                    'Flight order attempt status loaded.',
            ])
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );

        Http::assertNothingSent();
    }

    public function test_unknown_malformed_and_cross_user_references_fail_closed_identically(): void
    {
        Http::fake();

        $owner =
            $this->userWithFlightBookPermission();

        $otherUser =
            $this->userWithFlightBookPermission();

        $created =
            $this->store()
                ->createProcessing(
                    (int) $owner->getKey(),
                    'duffel',
                    'off_local_status_user_scope_1',
                );

        $this->assertIsArray(
            $created,
        );

        $reference =
            $created['reference'];

        $expectedJson = [
            'data' =>
                null,

            'message' =>
                'Flight order attempt status is unavailable.',
        ];

        $crossUserResponse =
            $this->actingAs(
                $otherUser,
            )
                ->getJson(
                    $this->statusUrl(
                        $reference,
                    ),
                );

        $crossUserResponse
            ->assertNotFound()
            ->assertExactJson(
                $expectedJson,
            )
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            );

        $unknownResponse =
            $this->actingAs(
                $owner,
            )
                ->getJson(
                    $this->statusUrl(
                        str_repeat(
                            'Z',
                            64,
                        ),
                    ),
                );

        $unknownResponse
            ->assertNotFound()
            ->assertExactJson(
                $expectedJson,
            )
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            );

        $malformedResponse =
            $this->actingAs(
                $owner,
            )
                ->getJson(
                    $this->statusUrl(
                        'short-reference',
                    ),
                );

        $malformedResponse
            ->assertNotFound()
            ->assertExactJson(
                $expectedJson,
            )
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            );

        $this->assertSame(
            $crossUserResponse->getContent(),
            $unknownResponse->getContent(),
        );

        $this->assertSame(
            $unknownResponse->getContent(),
            $malformedResponse->getContent(),
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );

        Http::assertNothingSent();
    }

    public function test_status_boundary_is_local_read_only_and_ui_remains_unwired(): void
    {
        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderAttemptStatusController.php',
                ),
            );

        $routes =
            file_get_contents(
                base_path(
                    'routes/web.php',
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
            $routes,
        );

        $this->assertIsString(
            $ui,
        );

        $this->assertStringContainsString(
            'findForUser(',
            $controller,
        );

        $this->assertStringContainsString(
            'getAuthIdentifier()',
            $controller,
        );

        foreach ([
            'findByProviderAndOffer(',
            'Http::',
            '/air/orders',
            'Duffel',
            'supplier_offer_id',
            'supplier_order_id',
            'reference_hash',
            'attempt_identity_hash',
            'dispatch(',
            'Bus::',
            'Queue::',
        ] as $forbiddenControllerBehavior) {
            $this->assertStringNotContainsString(
                $forbiddenControllerBehavior,
                $controller,
            );
        }

        $this->assertStringContainsString(
            "Route::get(",
            $routes,
        );

        $this->assertStringContainsString(
            '/flights/bookings/orders/attempts/{attemptReference}',
            $routes,
        );

        $this->assertStringContainsString(
            "'permission:flights.book'",
            $routes,
        );

        $this->assertStringContainsString(
            "'verified'",
            $routes,
        );

        $this->assertStringNotContainsString(
            '/flights/bookings/orders/attempts/',
            $ui,
        );
    }

    private function userWithFlightBookPermission(): User
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
            User::factory()->create([
                'email_verified_at' =>
                    now(),
            ]);

        $user->givePermissionTo(
            $permission,
        );

        return $user;
    }

    private function store(): FlightOrderAttemptRecordStore
    {
        return app(
            FlightOrderAttemptRecordStore::class,
        );
    }

    private function statusUrl(
        string $reference,
    ): string {
        return sprintf(
            '/flights/bookings/orders/attempts/%s',
            $reference,
        );
    }
}