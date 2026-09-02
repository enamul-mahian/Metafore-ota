<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightOrderProvider;
use App\Exceptions\Flight\FlightOrderProcessingException;
use App\Models\FlightOrderAttempt;
use App\Models\User;
use App\Services\Flight\FlightBookingConfirmationIntentStore;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use App\Services\Flight\FlightOrderExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class DurableProcessingPersistenceTestFlightOrderProvider implements FlightOrderProvider
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

        $supplierOfferId =
            data_get(
                $trustedConfirmationIntent,
                'offer.id',
            );

        if (! is_string($supplierOfferId)) {
            throw new \RuntimeException(
                'Trusted supplier offer ID is required by the test fixture.',
            );
        }

        $supplierOfferId =
            trim(
                $supplierOfferId,
            );

        if ($supplierOfferId === '') {
            throw new \RuntimeException(
                'Trusted supplier offer ID is required by the test fixture.',
            );
        }

        throw (new FlightOrderProcessingException(
            'processing_persistence',
        ))->withSupplierOfferId(
            $supplierOfferId,
        );
    }
}

final class FlightOrderProcessingPersistenceBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DurableProcessingPersistenceTestFlightOrderProvider::reset();

        config()->set(
            'flight_orders.providers.processing_persistence',
            DurableProcessingPersistenceTestFlightOrderProvider::class,
        );
    }

    public function test_real_execution_service_persists_processing_attempt_and_preserves_single_use_semantics(): void
    {
        Http::fake();

        $user =
            User::factory()->create();

        $userId =
            (int) $user->getKey();

        $intentStore =
            app(
                FlightBookingConfirmationIntentStore::class,
            );

        $token =
            $intentStore->put(
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
                        'off_processing_persistence_behavior_1',

                    'provider' =>
                        'processing_persistence',

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
                            'durable-processing@example.test',

                        'phone_number' =>
                            '+14155550101',
                    ],
                ],
                [
                    'status' =>
                        'revalidated',

                    'provider' =>
                        'processing_persistence',

                    'live_revalidation' =>
                        true,

                    'price_changed' =>
                        false,
                ],
            );

        $service =
            app(
                FlightOrderExecutionService::class,
            );

        $caught =
            null;

        try {
            $service->execute(
                $userId,
                $token,
            );

            $this->fail(
                'Expected a typed processing exception.',
            );
        } catch (FlightOrderProcessingException $exception) {
            $caught =
                $exception;
        }

        $this->assertInstanceOf(
            FlightOrderProcessingException::class,
            $caught,
        );

        $this->assertSame(
            'processing_persistence',
            $caught->provider(),
        );

        $this->assertSame(
            'off_processing_persistence_behavior_1',
            $caught->supplierOfferId(),
        );

        $reference =
            $caught->attemptReference();

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

        $referenceHash =
            hash(
                'sha256',
                $reference,
            );

        $attemptIdentityHash =
            hash(
                'sha256',
                "processing_persistence\0off_processing_persistence_behavior_1",
            );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );

        $this->assertDatabaseHas(
            'flight_order_attempts',
            [
                'user_id' =>
                    $userId,

                'reference_hash' =>
                    $referenceHash,

                'attempt_identity_hash' =>
                    $attemptIdentityHash,

                'provider' =>
                    'processing_persistence',

                'supplier_offer_id' =>
                    'off_processing_persistence_behavior_1',

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

        $durableAttempt =
            app(
                FlightOrderAttemptRecordStore::class,
            )->findForUser(
                $userId,
                $reference,
            );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $durableAttempt,
        );

        $this->assertSame(
            $userId,
            $durableAttempt->user_id,
        );

        $this->assertSame(
            'processing_persistence',
            $durableAttempt->provider,
        );

        $this->assertSame(
            'off_processing_persistence_behavior_1',
            $durableAttempt->supplier_offer_id,
        );

        $this->assertSame(
            FlightOrderAttempt::STATUS_PROCESSING,
            $durableAttempt->status,
        );

        $this->assertNull(
            $durableAttempt->supplier_order_id,
        );

        $this->assertNull(
            $durableAttempt->resolved_at,
        );

        $this->assertNull(
            $intentStore->get(
                $userId,
                $token,
            ),
        );

        $this->assertSame(
            1,
            DurableProcessingPersistenceTestFlightOrderProvider::$calls,
        );

        $second =
            $service->execute(
                $userId,
                $token,
            );

        $this->assertSame(
            'unavailable',
            $second['status'],
        );

        $this->assertNull(
            $second['provider'],
        );

        $this->assertFalse(
            $second['live_order_creation'],
        );

        $this->assertFalse(
            $second['order_created'],
        );

        $this->assertFalse(
            $second['confirmation_intent_consumed'],
        );

        $this->assertSame(
            1,
            DurableProcessingPersistenceTestFlightOrderProvider::$calls,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );

        Http::assertNothingSent();
    }
}