<?php

namespace Tests\Feature\Flight;

use App\Models\User;
use App\Services\Flight\FlightBookingDraftStore;
use App\Services\Flight\FlightOfferSelectionStore;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FlightBookingDraftControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_booking_draft(): void
    {
        $response = $this->postJson(
            route('flights.bookings.drafts.store'),
            $this->validTravelerPayload(
                str_repeat('a', 64)
            ),
        );

        $response->assertUnauthorized();
    }

    public function test_verified_user_without_flights_book_permission_is_forbidden(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $this->validTravelerPayload(
                    str_repeat('a', 64)
                ),
            );

        $response->assertForbidden();
    }

    public function test_customer_with_permission_can_create_booking_draft(): void
    {
        $user = $this->verifiedCustomer();

        $selectionToken = $this->issueSelectionToken(
            $user,
            $this->criteria(),
            $this->trustedOffer(),
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $this->validTravelerPayload(
                    $selectionToken
                ),
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'draft'
            )
            ->assertJsonPath(
                'data.traveler_count',
                1
            )
            ->assertJsonPath(
                'data.expires_in_seconds',
                900
            )
            ->assertHeader(
                'Cache-Control',
                'no-store, private'
            );
    }

    public function test_returned_booking_draft_token_is_64_characters(): void
    {
        $user = $this->verifiedCustomer();

        $selectionToken = $this->issueSelectionToken(
            $user,
            $this->criteria(),
            $this->trustedOffer(),
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $this->validTravelerPayload(
                    $selectionToken
                ),
            )
            ->assertCreated();

        $token = $response->json(
            'data.booking_draft_token'
        );

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
    }

    public function test_response_does_not_expose_traveler_pii(): void
    {
        $user = $this->verifiedCustomer();

        $selectionToken = $this->issueSelectionToken(
            $user,
            $this->criteria(),
            $this->trustedOffer(),
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $this->validTravelerPayload(
                    $selectionToken
                ),
            )
            ->assertCreated();

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            'SecureGivenName',
            $content
        );

        $this->assertStringNotContainsString(
            'SecureFamilyName',
            $content
        );

        $this->assertStringNotContainsString(
            now()->subYears(30)->toDateString(),
            $content
        );
    }

    public function test_draft_uses_server_trusted_offer_and_ignores_client_offer_overrides(): void
    {
        $user = $this->verifiedCustomer();

        $selectionToken = $this->issueSelectionToken(
            $user,
            $this->criteria(),
            $this->trustedOffer(),
        );

        $payload = $this->validTravelerPayload(
            $selectionToken
        );

        $payload['total_amount'] = '0.01';
        $payload['carrier'] = 'EVIL AIR';

        $payload['owner'] = [
            'code' => 'EVL',
            'name' => 'EVIL AIR',
        ];

        $payload['offer'] = [
            'total_amount' => '0.01',
            'owner' => [
                'code' => 'EVL',
                'name' => 'EVIL AIR',
            ],
        ];

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $payload,
            )
            ->assertCreated();

        $draftToken = $response->json(
            'data.booking_draft_token'
        );

        $draft = app(
            FlightBookingDraftStore::class
        )->get(
            (int) $user->getAuthIdentifier(),
            $draftToken,
        );

        $this->assertNotNull($draft);

        $this->assertSame(
            '14850.00',
            data_get(
                $draft,
                'offer.total_amount'
            )
        );

        $this->assertSame(
            'SERVER AIR',
            data_get(
                $draft,
                'offer.owner.name'
            )
        );

        $this->assertNotSame(
            '0.01',
            data_get(
                $draft,
                'offer.total_amount'
            )
        );

        $this->assertNotSame(
            'EVIL AIR',
            data_get(
                $draft,
                'offer.owner.name'
            )
        );
    }

    public function test_cached_booking_draft_is_encrypted_and_contains_no_plaintext_pii_or_carrier(): void
    {
        $user = $this->verifiedCustomer();

        $selectionToken = $this->issueSelectionToken(
            $user,
            $this->criteria(),
            $this->trustedOffer(),
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $this->validTravelerPayload(
                    $selectionToken
                ),
            )
            ->assertCreated();

        $draftToken = $response->json(
            'data.booking_draft_token'
        );

        $cacheKey = sprintf(
            'flight_booking_draft:%d:%s',
            (int) $user->getAuthIdentifier(),
            hash('sha256', $draftToken),
        );

        $rawCachedValue = Cache::get(
            $cacheKey
        );

        $this->assertIsString(
            $rawCachedValue
        );

        $this->assertNotSame(
            '',
            $rawCachedValue
        );

        $this->assertStringNotContainsString(
            'SecureGivenName',
            $rawCachedValue
        );

        $this->assertStringNotContainsString(
            'SecureFamilyName',
            $rawCachedValue
        );

        $this->assertStringNotContainsString(
            'SERVER AIR',
            $rawCachedValue
        );
    }

    public function test_booking_draft_token_is_scoped_to_customer(): void
    {
        $user = $this->verifiedCustomer();

        $selectionToken = $this->issueSelectionToken(
            $user,
            $this->criteria(),
            $this->trustedOffer(),
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $this->validTravelerPayload(
                    $selectionToken
                ),
            )
            ->assertCreated();

        $draftToken = $response->json(
            'data.booking_draft_token'
        );

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $store = app(
            FlightBookingDraftStore::class
        );

        $this->assertNotNull(
            $store->get(
                (int) $user->getAuthIdentifier(),
                $draftToken,
            )
        );

        $this->assertNull(
            $store->get(
                (int) $otherUser->getAuthIdentifier(),
                $draftToken,
            )
        );
    }

    public function test_invalid_adult_age_returns_422_and_no_booking_draft_token(): void
    {
        $user = $this->verifiedCustomer();

        $selectionToken = $this->issueSelectionToken(
            $user,
            $this->criteria(),
            $this->trustedOffer(),
        );

        $payload = $this->validTravelerPayload(
            $selectionToken
        );

        $payload['travelers'][0]['date_of_birth'] = now()
            ->subYears(5)
            ->toDateString();

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $payload,
            );

        $response->assertUnprocessable();

        $this->assertNull(
            $response->json(
                'data.booking_draft_token'
            )
        );
    }

    public function test_unknown_selection_token_returns_410_with_existing_search_again_message(): void
    {
        $user = $this->verifiedCustomer();

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $this->validTravelerPayload(
                    str_repeat('f', 64)
                ),
            );

        $response
            ->assertStatus(410)
            ->assertJson([
                'message' =>
                    'available. Please search again.',
            ]);
    }

    public function test_booking_draft_creation_does_not_call_supplier_http(): void
    {
        Http::fake();

        $user = $this->verifiedCustomer();

        $selectionToken = $this->issueSelectionToken(
            $user,
            $this->criteria(),
            $this->trustedOffer(),
        );

        $this
            ->actingAs($user)
            ->postJson(
                route('flights.bookings.drafts.store'),
                $this->validTravelerPayload(
                    $selectionToken
                ),
            )
            ->assertCreated();

        Http::assertNothingSent();
    }

    private function verifiedCustomer(): User
    {
        $this->seed(
            RolePermissionSeeder::class
        );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        $this->assertTrue(
            $user->fresh()->can('flights.book'),
            'customer role must receive flights.book permission'
        );

        return $user;
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $offer
     */
    private function issueSelectionToken(
        User $user,
        array $criteria,
        array $offer,
    ): string {
        $store = app(
            FlightOfferSelectionStore::class
        );

        $reflection = new \ReflectionObject(
            $store
        );

        $methods = $reflection->getMethods(
            \ReflectionMethod::IS_PUBLIC
        );

        foreach ($methods as $method) {
            if (
                $method->getDeclaringClass()->getName()
                !== FlightOfferSelectionStore::class
            ) {
                continue;
            }

            if (
                in_array(
                    $method->getName(),
                    [
                        'get',
                        'expiresInSeconds',
                    ],
                    true
                )
            ) {
                continue;
            }

            $arguments = [];

            $supported = true;
            $hasUser = false;
            $hasCriteria = false;
            $hasOffer = false;

            foreach (
                $method->getParameters()
                as $parameter
            ) {
                $name = strtolower(
                    $parameter->getName()
                );

                if (
                    str_contains($name, 'user')
                    || str_contains($name, 'customer')
                ) {
                    $arguments[] =
                        (int) $user->getAuthIdentifier();

                    $hasUser = true;

                    continue;
                }

                if (
                    str_contains($name, 'criteria')
                    || str_contains($name, 'search')
                ) {
                    $arguments[] = $criteria;
                    $hasCriteria = true;

                    continue;
                }

                /*
                 * Important:
                 * FlightOfferSelectionStore accepts an offers
                 * collection and maps each individual offer.
                 */
                if (
                    $name === 'offers'
                    || str_ends_with($name, 'offers')
                ) {
                    $arguments[] = [$offer];
                    $hasOffer = true;

                    continue;
                }

                if (str_contains($name, 'offer')) {
                    $arguments[] = $offer;
                    $hasOffer = true;

                    continue;
                }

                if (
                    str_contains($name, 'selection')
                    || str_contains($name, 'payload')
                ) {
                    $arguments[] = [
                        'criteria' => $criteria,
                        'offers' => [$offer],
                        'offer' => $offer,
                    ];

                    $hasCriteria = true;
                    $hasOffer = true;

                    continue;
                }

                if (
                    $parameter->isDefaultValueAvailable()
                ) {
                    $arguments[] =
                        $parameter->getDefaultValue();

                    continue;
                }

                $supported = false;
                break;
            }

            if (
                ! $supported
                || ! $hasUser
                || ! $hasCriteria
                || ! $hasOffer
            ) {
                continue;
            }

            $result = $method->invokeArgs(
                $store,
                $arguments,
            );

            $token = $this->extractSelectionToken(
                $result
            );

            if ($token !== null) {
                return $token;
            }
        }

        $this->fail(
            'Could not issue selection token through FlightOfferSelectionStore.'
        );
    }

    private function extractSelectionToken(
        mixed $value,
    ): ?string {
        if (! is_array($value)) {
            return null;
        }

        foreach (
            [
                'selection_token',
                'token',
            ]
            as $key
        ) {
            if (
                isset($value[$key])
                && is_string($value[$key])
                && strlen($value[$key]) === 64
            ) {
                return $value[$key];
            }
        }

        foreach ($value as $nestedValue) {
            if (! is_array($nestedValue)) {
                continue;
            }

            $token = $this->extractSelectionToken(
                $nestedValue
            );

            if ($token !== null) {
                return $token;
            }
        }

        return null;
    }
    /**
     * @return array<string, mixed>
     */
    private function criteria(): array
    {
        return [
            'trip_type' => 'one_way',
            'origin' => 'DAC',
            'destination' => 'CXB',
            'departure_date' => now()
                ->addDays(30)
                ->toDateString(),
            'return_date' => null,
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
            'cabin_class' => 'economy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trustedOffer(): array
    {
        return [
            'id' => 'server-fixture-offer-1',
            'provider' => 'fixture',
            'total_amount' => '14850.00',
            'currency' => 'BDT',
            'owner' => [
                'code' => 'SVR',
                'name' => 'SERVER AIR',
            ],
            'origin' => 'DAC',
            'destination' => 'CXB',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validTravelerPayload(
        string $selectionToken,
    ): array {
        return [
            'selection_token' => $selectionToken,

            'travelers' => [
                [
                    'type' => 'adult',
                    'title' => 'mr',
                    'given_name' => 'SecureGivenName',
                    'family_name' => 'SecureFamilyName',
                    'date_of_birth' => now()
                        ->subYears(30)
                        ->toDateString(),
                ],
            ],
        ];
    }
}
