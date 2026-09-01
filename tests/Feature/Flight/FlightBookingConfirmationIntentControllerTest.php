<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightOfferRevalidationProvider;
use App\Models\User;
use App\Services\Flight\FlightBookingConfirmationIntentStore;
use App\Services\Flight\FlightBookingDraftStore;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class ConfirmationIntentTestRevalidationProvider implements FlightOfferRevalidationProvider
{
    public static string $totalAmount = '15000.00';

    public static string $currency = 'BDT';

    public static bool $priceChanged = true;

    /**
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    public function revalidate(array $offer): array
    {
        return [
            'status' => 'revalidated',
            'provider' => 'confirmation_test',
            'live_revalidation' => true,
            'price_changed' => self::$priceChanged,
            'offer' => array_replace(
                $offer,
                [
                    'total_amount' =>
                        self::$totalAmount,

                    'currency' =>
                        self::$currency,
                ],
            ),
        ];
    }
}

final class FlightBookingConfirmationIntentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ConfirmationIntentTestRevalidationProvider::$totalAmount =
            '15000.00';

        ConfirmationIntentTestRevalidationProvider::$currency =
            'BDT';

        ConfirmationIntentTestRevalidationProvider::$priceChanged =
            true;

        config()->set(
            'flight_revalidation.providers.confirmation_test',
            ConfirmationIntentTestRevalidationProvider::class,
        );
    }

    public function test_guest_cannot_create_confirmation_intent(): void
    {
        $this->postJson(
            route(
                'flights.bookings.confirmation-intents.store'
            ),
            $this->validPayload(
                str_repeat('a', 64),
            ),
        )->assertUnauthorized();
    }

    public function test_verified_user_without_flights_book_permission_is_forbidden(): void
    {
        $user =
            User::factory()->create([
                'email_verified_at' =>
                    now(),
            ]);

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.confirmation-intents.store'
                ),
                $this->validPayload(
                    str_repeat('a', 64),
                ),
            )
            ->assertForbidden();
    }

    public function test_confirmation_intent_request_requires_explicit_valid_fare_acknowledgement(): void
    {
        $user =
            $this->bookableCustomer();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.confirmation-intents.store'
                ),
                [
                    'booking_draft_token' =>
                        'short',

                    'accept_revalidated_fare' =>
                        false,

                    'acknowledged_total_amount' =>
                        'bad',

                    'acknowledged_currency' =>
                        'bdt',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'booking_draft_token',
                'accept_revalidated_fare',
                'acknowledged_total_amount',
                'acknowledged_currency',
            ]);
    }

    public function test_customer_can_create_confirmation_intent_for_exact_current_live_fare(): void
    {
        Http::fake();

        $user =
            $this->bookableCustomer();

        $draftToken =
            $this->issueDraftToken(
                $user,
                'confirmation_test',
                '14850.00',
            );

        $response =
            $this
                ->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.confirmation-intents.store'
                    ),
                    $this->validPayload(
                        $draftToken,
                        '15000.00',
                        'BDT',
                    ),
                )
                ->assertCreated()
                ->assertJsonPath(
                    'data.status',
                    'confirmation_intent',
                )
                ->assertJsonPath(
                    'data.offer.total_amount',
                    '15000.00',
                )
                ->assertJsonPath(
                    'data.offer.currency',
                    'BDT',
                )
                ->assertJsonPath(
                    'data.revalidation.status',
                    'revalidated',
                )
                ->assertJsonPath(
                    'data.revalidation.live_revalidation',
                    true,
                )
                ->assertJsonPath(
                    'data.revalidation.price_changed',
                    true,
                )
                ->assertJsonMissingPath(
                    'data.booking_draft_token'
                )
                ->assertJsonMissingPath(
                    'data.travelers'
                );

        $this->assertSame(
            'no-store, private',
            $response->headers->get(
                'Cache-Control'
            ),
        );

        $intentToken =
            $response->json(
                'data.confirmation_intent_token'
            );

        $this->assertIsString(
            $intentToken
        );

        $this->assertSame(
            64,
            strlen($intentToken),
        );

        $intent =
            app(
                FlightBookingConfirmationIntentStore::class
            )->get(
                (int) $user->getAuthIdentifier(),
                $intentToken,
            );

        $this->assertNotNull(
            $intent
        );

        $this->assertSame(
            '15000.00',
            data_get(
                $intent,
                'offer.total_amount',
            ),
        );

        $this->assertSame(
            'BDT',
            data_get(
                $intent,
                'offer.currency',
            ),
        );

        $this->assertSame(
            'Alice',
            data_get(
                $intent,
                'travelers.0.given_name',
            ),
        );

        Http::assertNothingSent();
    }

    public function test_stale_acknowledged_fare_returns_409_and_does_not_create_intent(): void
    {
        Http::fake();

        $user =
            $this->bookableCustomer();

        $draftToken =
            $this->issueDraftToken(
                $user,
                'confirmation_test',
                '14850.00',
            );

        $response =
            $this
                ->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.confirmation-intents.store'
                    ),
                    $this->validPayload(
                        $draftToken,
                        '14850.00',
                        'BDT',
                    ),
                )
                ->assertStatus(409)
                ->assertJsonPath(
                    'data.status',
                    'fare_changed',
                )
                ->assertJsonPath(
                    'data.requires_review',
                    true,
                )
                ->assertJsonPath(
                    'data.confirmation_intent_created',
                    false,
                )
                ->assertJsonPath(
                    'data.offer.total_amount',
                    '15000.00',
                )
                ->assertJsonMissingPath(
                    'data.confirmation_intent_token'
                );

        $this->assertSame(
            'no-store, private',
            $response->headers->get(
                'Cache-Control'
            ),
        );

        Http::assertNothingSent();
    }

    public function test_fixture_demo_offer_cannot_create_confirmation_intent(): void
    {
        Http::fake();

        $user =
            $this->bookableCustomer();

        $draftToken =
            $this->issueDraftToken(
                $user,
                'fixture',
                '14850.00',
            );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.confirmation-intents.store'
                ),
                $this->validPayload(
                    $draftToken,
                    '14850.00',
                    'BDT',
                ),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'data.status',
                'live_revalidation_required',
            )
            ->assertJsonPath(
                'data.live_revalidation',
                false,
            )
            ->assertJsonPath(
                'data.confirmation_intent_created',
                false,
            )
            ->assertJsonMissingPath(
                'data.confirmation_intent_token'
            );

        Http::assertNothingSent();
    }

    public function test_unknown_booking_draft_returns_410_before_revalidation(): void
    {
        Http::fake();

        $user =
            $this->bookableCustomer();

        $response =
            $this
                ->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.confirmation-intents.store'
                    ),
                    $this->validPayload(
                        str_repeat('z', 64),
                    ),
                )
                ->assertStatus(410)
                ->assertJsonMissingPath(
                    'data.confirmation_intent_token'
                );

        $this->assertSame(
            'no-store, private',
            $response->headers->get(
                'Cache-Control'
            ),
        );

        Http::assertNothingSent();
    }

    public function test_confirmation_intent_token_is_customer_scoped(): void
    {
        $user =
            $this->bookableCustomer();

        $otherUser =
            $this->bookableCustomer();

        $draftToken =
            $this->issueDraftToken(
                $user,
                'confirmation_test',
                '14850.00',
            );

        $intentToken =
            $this
                ->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.confirmation-intents.store'
                    ),
                    $this->validPayload(
                        $draftToken,
                        '15000.00',
                        'BDT',
                    ),
                )
                ->assertCreated()
                ->json(
                    'data.confirmation_intent_token'
                );

        $this->assertIsString(
            $intentToken
        );

        $store =
            app(
                FlightBookingConfirmationIntentStore::class
            );

        $this->assertNotNull(
            $store->get(
                (int) $user->getAuthIdentifier(),
                $intentToken,
            ),
        );

        $this->assertNull(
            $store->get(
                (int) $otherUser->getAuthIdentifier(),
                $intentToken,
            ),
        );
    }

    public function test_cached_confirmation_intent_is_encrypted_and_contains_no_plaintext_pii_or_fare_owner(): void
    {
        $user =
            $this->bookableCustomer();

        $draftToken =
            $this->issueDraftToken(
                $user,
                'confirmation_test',
                '14850.00',
            );

        $intentToken =
            $this
                ->actingAs($user)
                ->postJson(
                    route(
                        'flights.bookings.confirmation-intents.store'
                    ),
                    $this->validPayload(
                        $draftToken,
                        '15000.00',
                        'BDT',
                    ),
                )
                ->assertCreated()
                ->json(
                    'data.confirmation_intent_token'
                );

        $this->assertIsString(
            $intentToken
        );

        $cacheKey =
            'flight_booking_confirmation_intent:'
            . (int) $user->getAuthIdentifier()
            . ':'
            . hash(
                'sha256',
                $intentToken,
            );

        $raw =
            Cache::get(
                $cacheKey
            );

        $this->assertIsString(
            $raw
        );

        $this->assertStringNotContainsString(
            'Alice',
            $raw,
        );

        $this->assertStringNotContainsString(
            'SERVER AIR',
            $raw,
        );

        $this->assertStringNotContainsString(
            '15000.00',
            $raw,
        );
    }

    public function test_confirmation_controller_has_no_direct_supplier_order_payment_ticketing_or_database_action(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightBookingConfirmationIntentController.php'
                )
            );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            'FlightOfferRevalidationService',
            $source,
        );

        $this->assertStringContainsString(
            '$revalidationService->revalidate(',
            $source,
        );

        $this->assertStringContainsString(
            '$trustedOffer',
            $source,
        );

        $this->assertStringContainsString(
            'FlightBookingConfirmationIntentStore',
            $source,
        );

        $this->assertStringContainsString(
            '$currentOffer',
            $source,
        );

        $this->assertStringContainsString(
            'hash_equals(',
            $source,
        );

        foreach ([
            'Http::',
            '/air/orders',
            'payment_intent',
            'ticket_number',
            'DB::',
            '->insert(',
            '->create(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function bookableCustomer(): User
    {
        $this->seed(
            RolePermissionSeeder::class
        );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

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

    private function issueDraftToken(
        User $user,
        string $provider,
        string $amount,
    ): string {
        return app(
            FlightBookingDraftStore::class
        )->put(
            (int) $user->getAuthIdentifier(),
            [
                'trip_type' => 'one_way',
                'origin' => 'DAC',
                'destination' => 'CXB',
                'departure_date' =>
                    now()
                        ->addDays(10)
                        ->toDateString(),
                'return_date' => null,
                'adults' => 1,
                'children' => 0,
                'infants' => 0,
                'cabin_class' => 'economy',
            ],
            [
                'id' =>
                    $provider === 'fixture'
                        ? 'fixture-offer-1'
                        : 'offer-test-live-1',

                'provider' =>
                    $provider,

                'total_amount' =>
                    $amount,

                'currency' =>
                    'BDT',

                'owner' => [
                    'code' => 'SA',
                    'name' => 'SERVER AIR',
                ],

                'origin' => 'DAC',
                'destination' => 'CXB',
            ],
            [
                [
                    'type' => 'adult',
                    'title' => 'mr',
                    'given_name' => 'Alice',
                    'family_name' => 'Traveler',
                    'date_of_birth' => '1990-01-01',
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(
        string $draftToken,
        string $amount = '15000.00',
        string $currency = 'BDT',
    ): array {
        return [
            'booking_draft_token' =>
                $draftToken,

            'accept_revalidated_fare' =>
                true,

            'acknowledged_total_amount' =>
                $amount,

            'acknowledged_currency' =>
                $currency,
        ];
    }
}
