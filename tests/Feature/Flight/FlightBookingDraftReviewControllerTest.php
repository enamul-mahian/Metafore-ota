<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use App\Services\Flight\FlightBookingDraftStore;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FlightBookingDraftReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            RolePermissionSeeder::class
        );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }

    public function test_guest_cannot_review_booking_draft(): void
    {
        $this
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        str_repeat('a', 64),
                ],
            )
            ->assertUnauthorized();
    }

    public function test_verified_user_without_flights_book_permission_is_forbidden(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        str_repeat('a', 64),
                ],
            )
            ->assertForbidden();
    }

    public function test_customer_can_review_own_draft_using_server_trusted_data(): void
    {
        $user = $this->verifiedCustomer();

        $token = $this->issueDraftToken(
            $user
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        $token,

                    'total_amount' =>
                        '1.00',

                    'carrier' =>
                        'EVIL AIR',

                    'origin' =>
                        'XXX',

                    'destination' =>
                        'YYY',
                ],
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'status' =>
                        'draft_review',

                    'traveler_count' =>
                        1,

                    'criteria' => [
                        'trip_type' =>
                            'one_way',

                        'origin' =>
                            'DAC',

                        'destination' =>
                            'CXB',

                        'adults' =>
                            1,

                        'children' =>
                            0,

                        'infants' =>
                            0,

                        'cabin_class' =>
                            'economy',
                    ],

                    'offer' => [
                        'id' =>
                            'trusted-review-offer-1',

                        'provider' =>
                            'fixture',

                        'total_amount' =>
                            '14850.00',

                        'currency' =>
                            'BDT',

                        'owner' => [
                            'code' =>
                                'SVR',

                            'name' =>
                                'SERVER AIR',
                        ],

                        'origin' =>
                            'DAC',

                        'destination' =>
                            'CXB',
                    ],

                    'expires_in_seconds' =>
                        900,
                ],
            ]);

        $this->assertStringContainsString(
            'no-store',
            (string) $response
                ->headers
                ->get('Cache-Control')
        );

        $this->assertStringContainsString(
            'private',
            (string) $response
                ->headers
                ->get('Cache-Control')
        );

        $this->assertNotSame(
            '1.00',
            $response->json(
                'data.offer.total_amount'
            )
        );

        $this->assertNotSame(
            'EVIL AIR',
            $response->json(
                'data.offer.owner.name'
            )
        );
    }

    public function test_review_response_does_not_expose_traveler_pii_or_echo_token(): void
    {
        $user = $this->verifiedCustomer();

        $token = $this->issueDraftToken(
            $user
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        $token,
                ],
            )
            ->assertOk();

        $content =
            $response->getContent();

        $this->assertStringNotContainsString(
            'PrivateGivenName',
            $content
        );

        $this->assertStringNotContainsString(
            'PrivateFamilyName',
            $content
        );

        $this->assertStringNotContainsString(
            '1990-01-02',
            $content
        );

        $this->assertStringNotContainsString(
            $token,
            $content
        );

        $this->assertNull(
            $response->json(
                'data.booking_draft_token'
            )
        );

        $this->assertNull(
            $response->json(
                'data.travelers'
            )
        );
    }

    public function test_booking_draft_review_token_is_customer_scoped(): void
    {
        $owner =
            $this->verifiedCustomer();

        $token =
            $this->issueDraftToken(
                $owner
            );

        $otherCustomer =
            $this->verifiedCustomer();

        $response = $this
            ->actingAs($otherCustomer)
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        $token,
                ],
            );

        $response
            ->assertStatus(410)
            ->assertJson([
                'message' =>
                    'Booking draft is no longer available. Please create a new draft.',
            ]);

        $this->assertStringContainsString(
            'no-store',
            (string) $response
                ->headers
                ->get('Cache-Control')
        );

        $this->assertStringContainsString(
            'private',
            (string) $response
                ->headers
                ->get('Cache-Control')
        );
    }

    public function test_invalid_booking_draft_token_shape_is_rejected(): void
    {
        $user =
            $this->verifiedCustomer();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        'short-token',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'booking_draft_token',
            ]);
    }

    public function test_unknown_booking_draft_token_returns_410(): void
    {
        $user =
            $this->verifiedCustomer();

        $response = $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        str_repeat('f', 64),
                ],
            );

        $response
            ->assertStatus(410)
            ->assertJson([
                'message' =>
                    'Booking draft is no longer available. Please create a new draft.',
            ]);

        $this->assertStringContainsString(
            'no-store',
            (string) $response
                ->headers
                ->get('Cache-Control')
        );
    }

    public function test_corrupted_encrypted_booking_draft_returns_410(): void
    {
        $user =
            $this->verifiedCustomer();

        $token =
            str_repeat('c', 64);

        $cacheKey = sprintf(
            'flight_booking_draft:%d:%s',
            (int) $user->getAuthIdentifier(),
            hash(
                'sha256',
                $token
            ),
        );

        Cache::put(
            $cacheKey,
            'not-valid-encrypted-payload',
            900,
        );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        $token,
                ],
            )
            ->assertStatus(410);
    }

    public function test_booking_draft_review_does_not_call_supplier_http(): void
    {
        Http::fake();

        $user =
            $this->verifiedCustomer();

        $token =
            $this->issueDraftToken(
                $user
            );

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'flights.bookings.drafts.review'
                ),
                [
                    'booking_draft_token' =>
                        $token,
                ],
            )
            ->assertOk();

        Http::assertNothingSent();
    }

    private function verifiedCustomer(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole(
            'customer'
        );

        $this->assertTrue(
            $user
                ->fresh()
                ->can('flights.book'),
            'customer role must receive flights.book permission'
        );

        return $user;
    }

    private function issueDraftToken(
        User $user,
    ): string {
        return app(
            FlightBookingDraftStore::class
        )->put(
            (int) $user->getAuthIdentifier(),
            $this->criteria(),
            $this->trustedOffer(),
            $this->travelers(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function criteria(): array
    {
        return [
            'trip_type' =>
                'one_way',

            'origin' =>
                'DAC',

            'destination' =>
                'CXB',

            'departure_date' =>
                now()
                    ->addDays(30)
                    ->toDateString(),

            'return_date' =>
                null,

            'adults' =>
                1,

            'children' =>
                0,

            'infants' =>
                0,

            'cabin_class' =>
                'economy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trustedOffer(): array
    {
        return [
            'id' =>
                'trusted-review-offer-1',

            'provider' =>
                'fixture',

            'total_amount' =>
                '14850.00',

            'currency' =>
                'BDT',

            'owner' => [
                'code' =>
                    'SVR',

                'name' =>
                    'SERVER AIR',
            ],

            'origin' =>
                'DAC',

            'destination' =>
                'CXB',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function travelers(): array
    {
        return [
            [
                'type' =>
                    'adult',

                'title' =>
                    'mr',

                'given_name' =>
                    'PrivateGivenName',

                'family_name' =>
                    'PrivateFamilyName',

                'date_of_birth' =>
                    '1990-01-02',
            ],
        ];
    }
}
