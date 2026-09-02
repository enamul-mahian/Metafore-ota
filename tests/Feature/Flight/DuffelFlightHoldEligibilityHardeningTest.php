<?php

namespace Tests\Feature\Flight;

use App\Services\Flight\DuffelFlightOfferRevalidationProvider;
use App\Services\Flight\DuffelFlightOrderRequestBuilder;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;

final class DuffelFlightHoldEligibilityHardeningTest extends TestCase
{
    public function test_revalidation_fails_when_supplier_omits_instant_payment_flag(): void
    {
        $this->fakeSupplierOffer(
            [],
        );

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        app(
            DuffelFlightOfferRevalidationProvider::class,
        )->revalidate(
            $this->trustedOffer(),
        );
    }

    public function test_revalidation_fails_when_supplier_instant_payment_flag_is_not_boolean(): void
    {
        $this->fakeSupplierOffer(
            [
                'requires_instant_payment' =>
                    'false',
            ],
        );

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        app(
            DuffelFlightOfferRevalidationProvider::class,
        )->revalidate(
            $this->trustedOffer(),
        );
    }

    public function test_revalidation_preserves_only_a_future_hold_payment_deadline(): void
    {
        $this->fakeSupplierOffer(
            [
                'requires_instant_payment' =>
                    false,
                'payment_required_by' =>
                    '2099-01-01T00:00:00Z',
            ],
        );

        $result =
            app(
                DuffelFlightOfferRevalidationProvider::class,
            )->revalidate(
                $this->trustedOffer(),
            );

        $this->assertFalse(
            data_get(
                $result,
                'offer.requires_instant_payment',
            ),
        );

        $this->assertSame(
            '2099-01-01T00:00:00Z',
            data_get(
                $result,
                'offer.payment_required_by',
            ),
        );
    }

    public function test_revalidation_drops_expired_hold_payment_deadline(): void
    {
        $trustedOffer =
            $this->trustedOffer();

        $trustedOffer['payment_required_by'] =
            '2099-01-01T00:00:00Z';

        $this->fakeSupplierOffer(
            [
                'requires_instant_payment' =>
                    false,
                'payment_required_by' =>
                    '2000-01-01T00:00:00Z',
            ],
        );

        $result =
            app(
                DuffelFlightOfferRevalidationProvider::class,
            )->revalidate(
                $trustedOffer,
            );

        $this->assertArrayNotHasKey(
            'payment_required_by',
            $result['offer'],
        );
    }

    public function test_order_builder_fails_when_hold_payment_deadline_is_missing(): void
    {
        $intent =
            $this->trustedIntent();

        unset(
            $intent['offer'][
                'payment_required_by'
            ],
        );

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        app(
            DuffelFlightOrderRequestBuilder::class,
        )->buildHold(
            $intent,
        );
    }

    public function test_order_builder_fails_when_hold_payment_deadline_is_expired(): void
    {
        $intent =
            $this->trustedIntent();

        $intent['offer'][
            'payment_required_by'
        ] = '2000-01-01T00:00:00Z';

        $this->expectException(
            ServiceUnavailableHttpException::class,
        );

        app(
            DuffelFlightOrderRequestBuilder::class,
        )->buildHold(
            $intent,
        );
    }

    public function test_order_builder_uses_future_deadline_only_as_a_gate_and_never_sends_it_to_duffel_order_body(): void
    {
        $result =
            app(
                DuffelFlightOrderRequestBuilder::class,
            )->buildHold(
                $this->trustedIntent(),
            );

        $this->assertSame(
            'hold',
            data_get(
                $result,
                'data.type',
            ),
        );

        $this->assertArrayNotHasKey(
            'payment_required_by',
            $result['data'],
        );

        $this->assertArrayNotHasKey(
            'payments',
            $result['data'],
        );
    }

    /**
     * @param array<string, mixed> $paymentRequirements
     */
    private function fakeSupplierOffer(
        array $paymentRequirements
    ): void {
        config()->set(
            'flight.duffel.access_token',
            'test-token',
        );

        Http::fake([
            '*' => Http::response(
                [
                    'data' => [
                        'id' =>
                            'off_trusted_1',
                        'total_amount' =>
                            '125.50',
                        'total_currency' =>
                            'USD',
                        'expires_at' =>
                            '2099-01-01T00:00:00Z',
                        'payment_requirements' =>
                            $paymentRequirements,
                        'passengers' => [
                            [
                                'id' =>
                                    'pas_adult_1',
                                'type' =>
                                    'adult',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function trustedOffer(): array
    {
        return [
            'id' =>
                'off_trusted_1',
            'provider' =>
                'duffel',
            'total_amount' =>
                '125.50',
            'currency' =>
                'USD',
            'owner' => [
                'code' =>
                    'BA',
                'name' =>
                    'British Airways',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trustedIntent(): array
    {
        return [
            'offer' => [
                'id' =>
                    'off_trusted_1',
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
                        'tony@example.com',
                    'phone_number' =>
                        '+14155550101',
                ],
            ],
        ];
    }
}
