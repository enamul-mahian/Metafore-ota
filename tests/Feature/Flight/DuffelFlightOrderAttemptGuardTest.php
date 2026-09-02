<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOrderProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class DuffelFlightOrderAttemptGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->enableProvider();
    }

    public function test_same_trusted_offer_can_only_post_once(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_attempt_guard_1',
                    ],
                ], 201),
        ]);

        $intent =
            $this->completeIntent(
                'off_attempt_guard_same_1',
            );

        $first =
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $intent,
                );

        $this->assertSame([
            'status' => 'created',
            'live_order_creation' => true,
            'order_created' => true,
        ], $first);

        try {
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $intent,
                );

            $this->fail(
                'Second supplier attempt should be blocked.',
            );
        } catch (
            ServiceUnavailableHttpException $exception
        ) {
            $this->assertSame(
                'Duffel flight order is temporarily unavailable.',
                $exception->getMessage(),
            );
        }

        Http::assertSentCount(
            1,
        );
    }

    public function test_different_trusted_offer_ids_can_each_post_once(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_attempt_guard_distinct_1',
                    ],
                ], 201),
        ]);

        $first =
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $this->completeIntent(
                        'off_attempt_guard_a_1',
                    ),
                );

        $second =
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $this->completeIntent(
                        'off_attempt_guard_b_1',
                    ),
                );

        $this->assertTrue(
            $first['order_created'],
        );

        $this->assertTrue(
            $second['order_created'],
        );

        Http::assertSentCount(
            2,
        );
    }

    public function test_supplier_failure_keeps_claim_and_blocks_second_post(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response([
                    'errors' => [
                        [
                            'message' =>
                                'simulated supplier failure',
                        ],
                    ],
                ], 500),
        ]);

        $intent =
            $this->completeIntent(
                'off_attempt_guard_failure_1',
            );

        $this->assertSupplierUnavailable(
            $intent,
        );

        $this->assertSupplierUnavailable(
            $intent,
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_accepted_ambiguous_response_keeps_claim_and_blocks_second_post(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response(
                    [],
                    202,
                ),
        ]);

        $intent =
            $this->completeIntent(
                'off_attempt_guard_accepted_1',
            );

        $this->assertSupplierUnavailable(
            $intent,
        );

        $this->assertSupplierUnavailable(
            $intent,
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_missing_token_failure_does_not_burn_attempt_claim(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_attempt_guard_token_1',
                    ],
                ], 201),
        ]);

        $intent =
            $this->completeIntent(
                'off_attempt_guard_token_1',
            );

        config()->set(
            'flight.duffel.access_token',
            '',
        );

        $this->assertSupplierUnavailable(
            $intent,
        );

        Http::assertNothingSent();

        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        $result =
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $intent,
                );

        $this->assertTrue(
            $result['order_created'],
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_disabled_live_gate_does_not_burn_attempt_claim(): void
    {
        Http::fake([
            'https://api.duffel.test/air/orders' =>
                Http::response([
                    'data' => [
                        'id' =>
                            'ord_attempt_guard_gate_1',
                    ],
                ], 201),
        ]);

        $intent =
            $this->completeIntent(
                'off_attempt_guard_gate_1',
            );

        config()->set(
            'flight_orders.duffel.live_order_creation_enabled',
            false,
        );

        $disabled =
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $intent,
                );

        $this->assertSame([
            'status' => 'unavailable',
            'live_order_creation' => false,
            'order_created' => false,
        ], $disabled);

        Http::assertNothingSent();

        config()->set(
            'flight_orders.duffel.live_order_creation_enabled',
            true,
        );

        $enabled =
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $intent,
                );

        $this->assertTrue(
            $enabled['order_created'],
        );

        Http::assertSentCount(
            1,
        );
    }

    public function test_provider_source_claims_after_local_validation_and_before_post(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Services/Flight/DuffelFlightOrderProvider.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $claimPosition =
            strpos(
                $source,
                '$this->attemptStore->claim(',
            );

        $postPosition =
            strpos(
                $source,
                "->post(",
            );

        $tokenPosition =
            strpos(
                $source,
                '$accessToken ===',
            );

        $this->assertNotFalse(
            $claimPosition,
        );

        $this->assertNotFalse(
            $postPosition,
        );

        $this->assertNotFalse(
            $tokenPosition,
        );

        $this->assertGreaterThan(
            $tokenPosition,
            $claimPosition,
        );

        $this->assertGreaterThan(
            $claimPosition,
            $postPosition,
        );

        $this->assertStringNotContainsString(
            '->retry(',
            $source,
        );

        $this->assertStringNotContainsString(
            'Cache::forget(',
            $source,
        );
    }

    /**
     * @param array<string, mixed> $intent
     */
    private function assertSupplierUnavailable(
        array $intent,
    ): void {
        try {
            $this->provider()
                ->createFromTrustedConfirmationIntent(
                    $intent,
                );

            $this->fail(
                'Expected supplier unavailable exception.',
            );
        } catch (
            ServiceUnavailableHttpException $exception
        ) {
            $this->assertSame(
                'Duffel flight order is temporarily unavailable.',
                $exception->getMessage(),
            );
        }
    }

    private function enableProvider(): void
    {
        config()->set(
            'flight_orders.duffel.live_order_creation_enabled',
            true,
        );

        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        config()->set(
            'flight.duffel.base_url',
            'https://api.duffel.test',
        );

        config()->set(
            'flight.duffel.api_version',
            'v2',
        );

        config()->set(
            'flight.duffel.http_timeout',
            130,
        );
    }

    private function provider(): DuffelFlightOrderProvider
    {
        return app(
            DuffelFlightOrderProvider::class,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function completeIntent(
        string $offerId,
    ): array {
        return [
            'provider' => 'duffel',

            'offer' => [
                'id' =>
                    $offerId,

                'provider' =>
                    'duffel',

                'total_amount' =>
                    '125.50',

                'currency' =>
                    'USD',

                'requires_instant_payment' =>
                    false,

                'payment_required_by' =>
                    '2099-01-01T00:00:00Z',

                'passengers' => [
                    [
                        'id' =>
                            'pas_adult_1',

                        'type' =>
                            'adult',
                    ],
                ],
            ],

            'travelers' => [
                [
                    'type' =>
                        'adult',

                    'title' =>
                        'mr',

                    'given_name' =>
                        'Tony',

                    'family_name' =>
                        'Stark',

                    'date_of_birth' =>
                        '1980-07-24',

                    'gender' =>
                        'm',

                    'email' =>
                        'tony@example.test',

                    'phone_number' =>
                        '+14155550101',
                ],
            ],

            'revalidation' => [
                'status' =>
                    'revalidated',

                'provider' =>
                    'duffel',

                'live_revalidation' =>
                    true,

                'price_changed' =>
                    false,
            ],
        ];
    }
}