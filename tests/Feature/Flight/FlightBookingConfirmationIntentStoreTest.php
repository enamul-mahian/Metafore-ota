<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\FlightBookingConfirmationIntentStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FlightBookingConfirmationIntentStoreTest extends TestCase
{
    public function test_get_remains_non_consuming(): void
    {
        Http::fake();

        $store =
            $this->store();

        $token =
            $this->putIntent(
                $store,
                101,
            );

        $first =
            $store->get(
                101,
                $token,
            );

        $second =
            $store->get(
                101,
                $token,
            );

        $this->assertNotNull(
            $first,
        );

        $this->assertNotNull(
            $second,
        );

        $this->assertSame(
            'off_safe_1',
            data_get(
                $first,
                'offer.id',
            ),
        );

        Http::assertNothingSent();
    }

    public function test_take_returns_trusted_intent_once_and_then_rejects_replay(): void
    {
        Http::fake();

        $store =
            $this->store();

        $userId =
            201;

        $token =
            $this->putIntent(
                $store,
                $userId,
            );

        $cacheKey =
            $this->cacheKey(
                $userId,
                $token,
            );

        $this->assertTrue(
            Cache::has(
                $cacheKey,
            ),
        );

        $first =
            $store->take(
                $userId,
                $token,
            );

        $this->assertNotNull(
            $first,
        );

        $this->assertSame(
            'duffel',
            data_get(
                $first,
                'offer.provider',
            ),
        );

        $this->assertSame(
            'Alice',
            data_get(
                $first,
                'travelers.0.given_name',
            ),
        );

        $this->assertFalse(
            Cache::has(
                $cacheKey,
            ),
        );

        $this->assertNull(
            $store->take(
                $userId,
                $token,
            ),
        );

        $this->assertNull(
            $store->get(
                $userId,
                $token,
            ),
        );

        Http::assertNothingSent();
    }

    public function test_wrong_user_cannot_consume_owner_intent(): void
    {
        Http::fake();

        $store =
            $this->store();

        $ownerUserId =
            301;

        $otherUserId =
            302;

        $token =
            $this->putIntent(
                $store,
                $ownerUserId,
            );

        $this->assertNull(
            $store->take(
                $otherUserId,
                $token,
            ),
        );

        $this->assertNotNull(
            $store->get(
                $ownerUserId,
                $token,
            ),
        );

        $this->assertNotNull(
            $store->take(
                $ownerUserId,
                $token,
            ),
        );

        Http::assertNothingSent();
    }

    public function test_lock_contention_does_not_consume_intent(): void
    {
        Http::fake();

        $store =
            $this->store();

        $userId =
            401;

        $token =
            $this->putIntent(
                $store,
                $userId,
            );

        $lock =
            Cache::lock(
                $this->consumeLockKey(
                    $userId,
                    $token,
                ),
                15,
            );

        $this->assertTrue(
            $lock->get(),
        );

        try {
            $this->assertNull(
                $store->take(
                    $userId,
                    $token,
                ),
            );

            /*
             * A competing execution request must not destroy the token.
             */
            $this->assertNotNull(
                $store->get(
                    $userId,
                    $token,
                ),
            );
        } finally {
            $lock->release();
        }

        $this->assertNotNull(
            $store->take(
                $userId,
                $token,
            ),
        );

        $this->assertNull(
            $store->take(
                $userId,
                $token,
            ),
        );

        Http::assertNothingSent();
    }

    public function test_invalid_token_is_rejected_without_http(): void
    {
        Http::fake();

        $store =
            $this->store();

        $this->assertNull(
            $store->take(
                501,
                'short',
            ),
        );

        $this->assertNull(
            $store->get(
                501,
                'short',
            ),
        );

        Http::assertNothingSent();
    }

    public function test_corrupt_cached_payload_is_consumed_fail_closed(): void
    {
        Http::fake();

        $store =
            $this->store();

        $userId =
            601;

        $token =
            str_repeat(
                'c',
                64,
            );

        $cacheKey =
            $this->cacheKey(
                $userId,
                $token,
            );

        Cache::put(
            $cacheKey,
            'not-an-encrypted-confirmation-intent',
            600,
        );

        $this->assertTrue(
            Cache::has(
                $cacheKey,
            ),
        );

        $this->assertNull(
            $store->take(
                $userId,
                $token,
            ),
        );

        $this->assertFalse(
            Cache::has(
                $cacheKey,
            ),
        );

        Http::assertNothingSent();
    }

    public function test_store_source_uses_cache_lock_for_single_use_take_boundary(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightBookingConfirmationIntentStore.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'public function take(',
            $source,
        );

        $this->assertStringContainsString(
            'Cache::lock(',
            $source,
        );

        $this->assertStringContainsString(
            'Cache::forget(',
            $source,
        );

        $this->assertStringNotContainsString(
            'Cache::pull(',
            $source,
        );

        foreach ([
            'FlightOrderService',
            'DuffelFlightOrderProvider',
            '/air/orders',
            'payment_intent',
            'ticket_number',
            'DB::',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function store(): FlightBookingConfirmationIntentStore
    {
        return app(
            FlightBookingConfirmationIntentStore::class,
        );
    }

    private function putIntent(
        FlightBookingConfirmationIntentStore $store,
        int $userId,
    ): string {
        return $store->put(
            $userId,
            [
                'trip_type' => 'one_way',
                'origin' => 'DAC',
                'destination' => 'CXB',
            ],
            [
                'id' => 'off_safe_1',
                'provider' => 'duffel',
                'total_amount' => '15000.00',
                'currency' => 'BDT',
            ],
            [
                [
                    'type' => 'adult',
                    'given_name' => 'Alice',
                    'family_name' => 'Traveler',
                ],
            ],
            [
                'status' => 'revalidated',
                'provider' => 'duffel',
                'live_revalidation' => true,
                'price_changed' => false,
            ],
        );
    }

    private function cacheKey(
        int $userId,
        string $token,
    ): string {
        return 'flight_booking_confirmation_intent:'
            . $userId
            . ':'
            . hash(
                'sha256',
                $token,
            );
    }

    private function consumeLockKey(
        int $userId,
        string $token,
    ): string {
        return 'flight_booking_confirmation_intent_consume_lock:'
            . $userId
            . ':'
            . hash(
                'sha256',
                $token,
            );
    }
}