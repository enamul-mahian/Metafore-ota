<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\FlightOrderAttemptStore;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class FlightOrderAttemptStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_same_provider_and_offer_can_only_be_claimed_once(): void
    {
        $store =
            $this->store();

        $this->assertTrue(
            $store->claim(
                ' Duffel ',
                'off_attempt_same_1',
            ),
        );

        $this->assertFalse(
            $store->claim(
                'duffel',
                'off_attempt_same_1',
            ),
        );
    }

    public function test_different_offer_ids_have_independent_claims(): void
    {
        $store =
            $this->store();

        $this->assertTrue(
            $store->claim(
                'duffel',
                'off_attempt_a_1',
            ),
        );

        $this->assertTrue(
            $store->claim(
                'duffel',
                'off_attempt_b_1',
            ),
        );
    }

    public function test_different_providers_have_independent_claims(): void
    {
        $store =
            $this->store();

        $this->assertTrue(
            $store->claim(
                'duffel',
                'shared_offer_1',
            ),
        );

        $this->assertTrue(
            $store->claim(
                'fixture',
                'shared_offer_1',
            ),
        );
    }

    public function test_invalid_attempt_identity_fails_closed(): void
    {
        $store =
            $this->store();

        $this->assertFalse(
            $store->claim(
                '',
                'off_attempt_invalid_1',
            ),
        );

        $this->assertFalse(
            $store->claim(
                'duffel',
                '',
            ),
        );

        $this->assertFalse(
            $store->claim(
                'invalid provider',
                'off_attempt_invalid_2',
            ),
        );
    }

    public function test_attempt_marker_lives_longer_than_confirmation_intent(): void
    {
        $store =
            $this->store();

        $this->assertSame(
            86400,
            $store->expiresInSeconds(),
        );

        $this->assertGreaterThan(
            600,
            $store->expiresInSeconds(),
        );
    }

    public function test_store_uses_atomic_add_without_release_boundary(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightOrderAttemptStore.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'Cache::add(',
            $source,
        );

        $this->assertStringContainsString(
            "'sha256'",
            $source,
        );

        $this->assertStringNotContainsString(
            'Cache::forget(',
            $source,
        );

        $this->assertStringNotContainsString(
            'Http::',
            $source,
        );

        $this->assertStringNotContainsString(
            'DB::',
            $source,
        );
    }

    private function store(): FlightOrderAttemptStore
    {
        return app(
            FlightOrderAttemptStore::class,
        );
    }
}