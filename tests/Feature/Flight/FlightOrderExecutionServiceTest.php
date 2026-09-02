<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightOrderProvider;
use App\Services\Flight\FlightBookingConfirmationIntentStore;
use App\Services\Flight\FlightOrderExecutionService;
use App\Services\Flight\UnavailableFlightOrderProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class RecordingFlightOrderProvider implements FlightOrderProvider
{
    public static int $calls = 0;

    /**
     * @var array<string, mixed>
     */
    public static array $lastIntent = [];

    public static function reset(): void
    {
        self::$calls = 0;
        self::$lastIntent = [];
    }

    public function createFromTrustedConfirmationIntent(
        array $trustedConfirmationIntent
    ): array {
        self::$calls++;

        self::$lastIntent =
            $trustedConfirmationIntent;

        return [
            'status' => 'created',
            'live_order_creation' => true,
            'order_created' => true,
        ];
    }
}

final class FlightOrderExecutionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RecordingFlightOrderProvider::reset();

        config()->set(
            'flight_orders.providers.duffel',
            RecordingFlightOrderProvider::class,
        );
    }

    public function test_valid_intent_is_consumed_once_and_delegated_using_trusted_provider(): void
    {
        Http::fake();

        $store =
            $this->store();

        $userId =
            1001;

        $token =
            $this->putIntent(
                $store,
                $userId,
                'duffel',
                'duffel',
                true,
            );

        $first =
            $this->service()->execute(
                $userId,
                $token,
            );

        $this->assertSame([
            'status' => 'created',
            'provider' => 'duffel',
            'live_order_creation' => true,
            'order_created' => true,
            'confirmation_intent_consumed' => true,
        ], $first);

        $this->assertSame(
            1,
            RecordingFlightOrderProvider::$calls,
        );

        $this->assertSame(
            'duffel',
            RecordingFlightOrderProvider::$lastIntent[
                'provider'
            ]
                ?? null,
        );

        $this->assertSame(
            'duffel',
            data_get(
                RecordingFlightOrderProvider::$lastIntent,
                'offer.provider',
            ),
        );

        $this->assertNull(
            $store->get(
                $userId,
                $token,
            ),
        );

        $replay =
            $this->service()->execute(
                $userId,
                $token,
            );

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => null,
            'live_order_creation' => false,
            'order_created' => false,
            'confirmation_intent_consumed' => false,
        ], $replay);

        $this->assertSame(
            1,
            RecordingFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_wrong_user_cannot_execute_or_consume_owner_intent(): void
    {
        Http::fake();

        $store =
            $this->store();

        $ownerUserId =
            2001;

        $otherUserId =
            2002;

        $token =
            $this->putIntent(
                $store,
                $ownerUserId,
                'duffel',
                'duffel',
                true,
            );

        $wrongUserResult =
            $this->service()->execute(
                $otherUserId,
                $token,
            );

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => null,
            'live_order_creation' => false,
            'order_created' => false,
            'confirmation_intent_consumed' => false,
        ], $wrongUserResult);

        $this->assertSame(
            0,
            RecordingFlightOrderProvider::$calls,
        );

        $this->assertNotNull(
            $store->get(
                $ownerUserId,
                $token,
            ),
        );

        $ownerResult =
            $this->service()->execute(
                $ownerUserId,
                $token,
            );

        $this->assertTrue(
            $ownerResult['order_created'],
        );

        $this->assertSame(
            1,
            RecordingFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_offer_and_revalidation_provider_mismatch_fails_closed_after_single_use_claim(): void
    {
        Http::fake();

        $store =
            $this->store();

        $userId =
            3001;

        $token =
            $this->putIntent(
                $store,
                $userId,
                'duffel',
                'fixture',
                true,
            );

        $result =
            $this->service()->execute(
                $userId,
                $token,
            );

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => null,
            'live_order_creation' => false,
            'order_created' => false,
            'confirmation_intent_consumed' => true,
        ], $result);

        $this->assertSame(
            0,
            RecordingFlightOrderProvider::$calls,
        );

        $this->assertNull(
            $store->get(
                $userId,
                $token,
            ),
        );

        Http::assertNothingSent();
    }

    public function test_non_live_revalidation_fails_closed_and_is_not_delegated(): void
    {
        Http::fake();

        $store =
            $this->store();

        $userId =
            4001;

        $token =
            $this->putIntent(
                $store,
                $userId,
                'duffel',
                'duffel',
                false,
            );

        $result =
            $this->service()->execute(
                $userId,
                $token,
            );

        $this->assertFalse(
            $result['order_created'],
        );

        $this->assertTrue(
            $result[
                'confirmation_intent_consumed'
            ],
        );

        $this->assertSame(
            0,
            RecordingFlightOrderProvider::$calls,
        );

        $this->assertNull(
            $store->get(
                $userId,
                $token,
            ),
        );

        Http::assertNothingSent();
    }

    public function test_invalid_token_fails_without_consumption_or_delegation(): void
    {
        Http::fake();

        $result =
            $this->service()->execute(
                5001,
                'short',
            );

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => null,
            'live_order_creation' => false,
            'order_created' => false,
            'confirmation_intent_consumed' => false,
        ], $result);

        $this->assertSame(
            0,
            RecordingFlightOrderProvider::$calls,
        );

        Http::assertNothingSent();
    }

    public function test_unavailable_provider_execution_attempt_is_still_single_use(): void
    {
        Http::fake();

        config()->set(
            'flight_orders.providers.fixture',
            UnavailableFlightOrderProvider::class,
        );

        $store =
            $this->store();

        $userId =
            6001;

        $token =
            $this->putIntent(
                $store,
                $userId,
                'fixture',
                'fixture',
                true,
            );

        $first =
            $this->service()->execute(
                $userId,
                $token,
            );

        $this->assertSame([
            'status' => 'unavailable',
            'provider' => 'fixture',
            'live_order_creation' => false,
            'order_created' => false,
            'confirmation_intent_consumed' => true,
        ], $first);

        $second =
            $this->service()->execute(
                $userId,
                $token,
            );

        $this->assertFalse(
            $second[
                'confirmation_intent_consumed'
            ],
        );

        $this->assertNull(
            $store->get(
                $userId,
                $token,
            ),
        );

        Http::assertNothingSent();
    }

    public function test_execution_service_source_has_no_direct_supplier_or_persistence_boundary(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightOrderExecutionService.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            '->take(',
            $source,
        );

        $this->assertStringContainsString(
            'FlightOrderService',
            $source,
        );

        $this->assertStringContainsString(
            "'offer.provider'",
            $source,
        );

        $this->assertStringContainsString(
            "'revalidation.provider'",
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
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }

    private function service(): FlightOrderExecutionService
    {
        return app(
            FlightOrderExecutionService::class,
        );
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
        string $offerProvider,
        string $revalidationProvider,
        bool $liveRevalidation,
    ): string {
        return $store->put(
            $userId,
            [
                'trip_type' => 'one_way',
                'origin' => 'DAC',
                'destination' => 'CXB',
            ],
            [
                'id' => 'off_execution_safe_1',
                'provider' => $offerProvider,
                'total_amount' => '15000.00',
                'currency' => 'BDT',
            ],
            [
                [
                    'type' => 'adult',
                    'title' => 'mr',
                    'given_name' => 'Safe',
                    'family_name' => 'Traveler',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'm',
                    'email' => 'safe@example.test',
                    'phone_number' => '+14155550101',
                ],
            ],
            [
                'status' => 'revalidated',
                'provider' =>
                    $revalidationProvider,
                'live_revalidation' =>
                    $liveRevalidation,
                'price_changed' => false,
            ],
        );
    }
}