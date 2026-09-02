<?php

namespace Tests\Feature\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FlightOrderAttemptRecordStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_record_persists_only_safe_identity_state_and_returns_opaque_reference(): void
    {
        $user =
            User::factory()->create();

        $result =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    ' DUFFEL ',
                    ' off_durable_processing_1 ',
                );

        $this->assertIsArray(
            $result,
        );

        $reference =
            $result['reference'];

        $attempt =
            $result['attempt'];

        $this->assertIsString(
            $reference,
        );

        $this->assertSame(
            64,
            strlen(
                $reference,
            ),
        );

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]{64}$/',
            $reference,
        );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $attempt,
        );

        $this->assertSame(
            (int) $user->getKey(),
            $attempt->user_id,
        );

        $this->assertSame(
            'duffel',
            $attempt->provider,
        );

        $this->assertSame(
            'off_durable_processing_1',
            $attempt->supplier_offer_id,
        );

        $this->assertSame(
            FlightOrderAttempt::STATUS_PROCESSING,
            $attempt->status,
        );

        $this->assertNull(
            $attempt->supplier_order_id,
        );

        $this->assertNull(
            $attempt->resolved_at,
        );

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'user_id' =>
                    $user->getKey(),

                'reference_hash' =>
                    hash(
                        'sha256',
                        $reference,
                    ),

                'attempt_identity_hash' =>
                    hash(
                        'sha256',
                        "duffel\0off_durable_processing_1",
                    ),

                'provider' =>
                    'duffel',

                'supplier_offer_id' =>
                    'off_durable_processing_1',

                'status' =>
                    'processing',

                'supplier_order_id' =>
                    null,

                'resolved_at' =>
                    null,
            ],
        );

        $this->assertDatabaseMissing(
            'flight_order_attempts',
            [
                'reference_hash' =>
                    $reference,
            ],
        );

        $serialized =
            $attempt->toArray();

        $this->assertArrayNotHasKey(
            'reference_hash',
            $serialized,
        );

        $this->assertArrayNotHasKey(
            'attempt_identity_hash',
            $serialized,
        );
    }

    public function test_same_provider_and_supplier_offer_can_only_create_one_durable_record(): void
    {
        $firstUser =
            User::factory()->create();

        $secondUser =
            User::factory()->create();

        $first =
            $this->store()
                ->createProcessing(
                    (int) $firstUser->getKey(),
                    'duffel',
                    'off_durable_identity_1',
                );

        $second =
            $this->store()
                ->createProcessing(
                    (int) $secondUser->getKey(),
                    'duffel',
                    'off_durable_identity_1',
                );

        $this->assertIsArray(
            $first,
        );

        $this->assertNull(
            $second,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );
    }

    public function test_different_supplier_attempt_identities_are_independent(): void
    {
        $user =
            User::factory()->create();

        $first =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'duffel',
                    'off_durable_independent_1',
                );

        $second =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'duffel',
                    'off_durable_independent_2',
                );

        $third =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'fixture',
                    'off_durable_independent_1',
                );

        $this->assertIsArray(
            $first,
        );

        $this->assertIsArray(
            $second,
        );

        $this->assertIsArray(
            $third,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            3,
        );
    }

    public function test_opaque_reference_lookup_is_strictly_user_scoped(): void
    {
        $owner =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $created =
            $this->store()
                ->createProcessing(
                    (int) $owner->getKey(),
                    'duffel',
                    'off_user_scoped_lookup_1',
                );

        $this->assertIsArray(
            $created,
        );

        $reference =
            $created['reference'];

        $ownerAttempt =
            $this->store()
                ->findForUser(
                    (int) $owner->getKey(),
                    $reference,
                );

        $wrongUserAttempt =
            $this->store()
                ->findForUser(
                    (int) $otherUser->getKey(),
                    $reference,
                );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $ownerAttempt,
        );

        $this->assertSame(
            $created['attempt']->getKey(),
            $ownerAttempt->getKey(),
        );

        $this->assertNull(
            $wrongUserAttempt,
        );
    }

    public function test_server_side_provider_offer_lookup_uses_stable_identity(): void
    {
        $user =
            User::factory()->create();

        $created =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'duffel',
                    'off_server_resolution_1',
                );

        $this->assertIsArray(
            $created,
        );

        $found =
            $this->store()
                ->findByProviderAndOffer(
                    ' DUFFEL ',
                    ' off_server_resolution_1 ',
                );

        $missing =
            $this->store()
                ->findByProviderAndOffer(
                    'duffel',
                    'off_server_resolution_missing',
                );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $found,
        );

        $this->assertSame(
            $created['attempt']->getKey(),
            $found->getKey(),
        );

        $this->assertNull(
            $missing,
        );
    }

    public function test_invalid_identity_and_reference_fail_closed_without_attempt_write(): void
    {
        $user =
            User::factory()->create();

        $store =
            $this->store();

        $this->assertNull(
            $store->createProcessing(
                0,
                'duffel',
                'off_invalid_1',
            ),
        );

        $this->assertNull(
            $store->createProcessing(
                (int) $user->getKey(),
                'Duffel Invalid!',
                'off_invalid_2',
            ),
        );

        $this->assertNull(
            $store->createProcessing(
                (int) $user->getKey(),
                'duffel',
                '',
            ),
        );

        $this->assertNull(
            $store->createProcessing(
                (int) $user->getKey(),
                'duffel',
                str_repeat(
                    'x',
                    256,
                ),
            ),
        );

        $this->assertNull(
            $store->findForUser(
                (int) $user->getKey(),
                'short-reference',
            ),
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            0,
        );
    }

    public function test_schema_and_store_exclude_traveler_payment_and_confirmation_secrets(): void
    {
        $columns =
            Schema::getColumnListing(
                'flight_order_attempts',
            );

        foreach ([
            'id',
            'user_id',
            'reference_hash',
            'attempt_identity_hash',
            'provider',
            'supplier_offer_id',
            'status',
            'supplier_order_id',
            'resolved_at',
            'created_at',
            'updated_at',
        ] as $requiredColumn) {
            $this->assertContains(
                $requiredColumn,
                $columns,
            );
        }

        foreach ([
            'given_name',
            'family_name',
            'date_of_birth',
            'email',
            'phone_number',
            'passport',
            'passengers',
            'travelers',
            'payment',
            'payments',
            'card',
            'confirmation_intent_token',
            'supplier_request',
            'supplier_response',
        ] as $forbiddenColumn) {
            $this->assertNotContains(
                $forbiddenColumn,
                $columns,
            );
        }

        $storeSource =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightOrderAttemptRecordStore.php',
                ),
            );

        $this->assertIsString(
            $storeSource,
        );

        foreach ([
            'Http::',
            '->post(',
            '/air/orders',
            'confirmationIntentToken',
            'travelers',
            'passengers',
            'given_name',
            'family_name',
            'date_of_birth',
            'phone_number',
            'payments',
            'ticket',
        ] as $forbiddenSource) {
            $this->assertStringNotContainsString(
                $forbiddenSource,
                $storeSource,
            );
        }
    }

    private function store(): FlightOrderAttemptRecordStore
    {
        return app(
            FlightOrderAttemptRecordStore::class,
        );
    }
}