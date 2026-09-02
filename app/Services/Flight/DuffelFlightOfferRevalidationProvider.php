<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOfferRevalidationProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class DuffelFlightOfferRevalidationProvider implements FlightOfferRevalidationProvider
{
    /**
     * Refresh the latest supplier state for a trusted server-side Duffel offer.
     *
     * This adapter only retrieves an existing offer.
     * It never creates an order, booking, payment, or ticket.
     *
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    public function revalidate(array $offer): array
    {
        $this->assertTrustedDuffelOffer(
            $offer,
        );

        $offerId = trim(
            (string) data_get(
                $offer,
                'id',
                '',
            ),
        );

        $accessToken = trim(
            (string) config(
                'flight.duffel.access_token',
                '',
            ),
        );

        if ($accessToken === '') {
            throw new ServiceUnavailableHttpException(
                60,
                'Duffel flight offer revalidation is not configured.',
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'flight.duffel.base_url',
                'https://api.duffel.com',
            ),
            '/',
        );

        $apiVersion = trim(
            (string) config(
                'flight.duffel.api_version',
                'v2',
            ),
        );

        $httpTimeout = max(
            1,
            (int) config(
                'flight.duffel.http_timeout',
                30,
            ),
        );

        try {
            $response = Http::baseUrl(
                $baseUrl,
            )
                ->withToken(
                    $accessToken,
                )
                ->acceptJson()
                ->withHeaders([
                    'Duffel-Version' => $apiVersion,
                ])
                ->timeout(
                    $httpTimeout,
                )
                ->get(
                    '/air/offers/' . rawurlencode(
                        $offerId,
                    ),
                );
        } catch (ConnectionException) {
            throw $this->supplierUnavailable();
        }

        if ($response->failed()) {
            throw $this->supplierUnavailable();
        }

        $supplierOffer = $response->json(
            'data',
        );

        if (! is_array($supplierOffer)) {
            throw $this->supplierUnavailable();
        }

        $supplierOfferId = trim(
            (string) (
                $supplierOffer['id']
                ?? ''
            ),
        );

        if (
            $supplierOfferId === ''
            || ! hash_equals(
                $offerId,
                $supplierOfferId,
            )
        ) {
            throw $this->supplierUnavailable();
        }

        $totalAmount =
            $supplierOffer['total_amount']
            ?? null;

        $currency =
            $supplierOffer['total_currency']
            ?? null;

        if (
            ! is_string($totalAmount)
            || trim($totalAmount) === ''
            || ! is_string($currency)
            || trim($currency) === ''
        ) {
            throw $this->supplierUnavailable();
        }

        $totalAmount =
            trim(
                $totalAmount,
            );

        $currency =
            strtoupper(
                trim(
                    $currency,
                ),
            );

        $trustedAmount = trim(
            (string) data_get(
                $offer,
                'total_amount',
                '',
            ),
        );

        $trustedCurrency = strtoupper(
            trim(
                (string) data_get(
                    $offer,
                    'currency',
                    '',
                ),
            ),
        );

        $refreshedOffer = $offer;

        $refreshedOffer['id'] =
            $supplierOfferId;

        $refreshedOffer['provider'] =
            'duffel';

        $refreshedOffer['total_amount'] =
            $totalAmount;

        $refreshedOffer['currency'] =
            $currency;

        $refreshedOffer['expires_at'] =
            $supplierOffer['expires_at']
            ?? null;

        $paymentRequirements =
            data_get(
                $supplierOffer,
                'payment_requirements',
            );

        $hasExplicitPaymentRequirement =
            is_array($paymentRequirements)
            && array_key_exists(
                'requires_instant_payment',
                $paymentRequirements,
            )
            && is_bool(
                $paymentRequirements[
                    'requires_instant_payment'
                ],
            );

        if (! $hasExplicitPaymentRequirement) {
            throw new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(
                60,
                'Flight offer revalidation is currently unavailable.',
            );
        }

        $requiresInstantPayment =
            $paymentRequirements[
                'requires_instant_payment'
            ];

        $refreshedOffer['requires_instant_payment'] =
            $requiresInstantPayment;

        /*
         * Never retain stale hold-deadline metadata from the original
         * trusted snapshot. Only a currently valid supplier deadline
         * may make it into the refreshed trusted offer.
         */
        unset(
            $refreshedOffer[
                'payment_required_by'
            ],
        );

        if ($requiresInstantPayment === false) {
            $paymentRequiredBy =
                $this->normalizeFuturePaymentRequiredBy(
                    $paymentRequirements[
                        'payment_required_by'
                    ]
                    ?? null,
                );

            if ($paymentRequiredBy !== null) {
                $refreshedOffer[
                    'payment_required_by'
                ] = $paymentRequiredBy;
            }
        }

        /*
         * Supplier passenger IDs originate from the trusted Duffel offer.
         * Never retain stale/client-provided passenger IDs when the live
         * supplier response does not provide a safe passenger list.
         */
        unset($refreshedOffer['passengers']);

        $safePassengers =
            $this->normalizePassengers(
                $supplierOffer['passengers']
                ?? null,
            );

        if ($safePassengers !== null) {
            $refreshedOffer['passengers'] =
                $safePassengers;
        }

        $refreshedOffer['owner'] =
            $this->normalizeOwner(
                $supplierOffer['owner']
                ?? null,
                data_get(
                    $offer,
                    'owner',
                ),
            );

        return [
            'status' => 'revalidated',
            'provider' => 'duffel',
            'live_revalidation' => true,

            /*
             * A successful GET refreshes the current supplier offer state,
             * but this adapter deliberately does not claim that a booking
             * has been made or guaranteed.
             */
            'price_changed' => (
                $trustedAmount !== $totalAmount
                || $trustedCurrency !== $currency
            ),

            /*
             * Route/slice data remains the trusted normalized selection.
             * Fare, expiry, payment requirement, and carrier summary above
             * are refreshed from the same supplier offer ID.
             */
            'offer' => $refreshedOffer,
        ];
    }

    /**
     * @param array<string, mixed> $offer
     */
    private function assertTrustedDuffelOffer(
        array $offer,
    ): void {
        $provider = data_get(
            $offer,
            'provider',
        );

        $offerId = data_get(
            $offer,
            'id',
        );

        if (
            $provider !== 'duffel'
            || ! is_string($offerId)
            || trim($offerId) === ''
            || strlen(trim($offerId)) > 255
        ) {
            throw new ServiceUnavailableHttpException(
                60,
                'Duffel flight offer revalidation is unavailable for this offer.',
            );
        }
    }

    /**
     * @param mixed $supplierOwner
     * @param mixed $trustedOwner
     * @return array<string, mixed>|mixed
     */
    private function normalizeFuturePaymentRequiredBy(
        mixed $value
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $paymentRequiredBy =
            trim($value);

        if ($paymentRequiredBy === '') {
            return null;
        }

        try {
            $deadline =
                new \DateTimeImmutable(
                    $paymentRequiredBy,
                );
        } catch (\Throwable) {
            return null;
        }

        if (
            $deadline->getTimestamp()
            <= time()
        ) {
            return null;
        }

        return $paymentRequiredBy;
    }

    /**
     * @return array<int, array{id: string, type: string}>|null
     */
    private function normalizePassengers(
        mixed $passengers
    ): ?array {
        if (
            ! is_array($passengers)
            || ! array_is_list($passengers)
            || count($passengers) < 1
            || count($passengers) > 9
        ) {
            return null;
        }

        $safePassengers = [];

        foreach ($passengers as $passenger) {
            if (! is_array($passenger)) {
                return null;
            }

            $id = trim(
                (string) data_get(
                    $passenger,
                    'id',
                    '',
                ),
            );

            $type =
                $this->normalizePassengerType(
                    data_get(
                        $passenger,
                        'type',
                    ),
                );

            if (
                ! $this->isPassengerId($id)
                || $type === null
            ) {
                return null;
            }

            $safePassengers[] = [
                'id' => $id,
                'type' => $type,
            ];
        }

        return $safePassengers;
    }

    private function normalizePassengerType(
        mixed $value
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $type = strtolower(
            trim($value),
        );

        return match ($type) {
            'adult' => 'adult',
            'child' => 'child',
            'infant_without_seat' => 'infant',
            default => null,
        };
    }

    private function isPassengerId(
        string $value
    ): bool {
        if (
            $value === ''
            || strlen($value) > 255
            || ! str_starts_with(
                $value,
                'pas_',
            )
        ) {
            return false;
        }

        return preg_match(
            '/^[A-Za-z0-9_]+$/',
            $value,
        ) === 1;
    }
    private function normalizeOwner(
        mixed $supplierOwner,
        mixed $trustedOwner,
    ): mixed {
        if (! is_array($supplierOwner)) {
            return $trustedOwner;
        }

        $code = data_get(
            $supplierOwner,
            'iata_code',
        );

        $name = data_get(
            $supplierOwner,
            'name',
        );

        if (
            ! is_string($code)
            || trim($code) === ''
            || ! is_string($name)
            || trim($name) === ''
        ) {
            return $trustedOwner;
        }

        return [
            'code' => strtoupper(
                trim(
                    $code,
                ),
            ),
            'name' => trim(
                $name,
            ),
        ];
    }

    private function supplierUnavailable(): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(
            60,
            'Duffel flight offer revalidation is temporarily unavailable.',
        );
    }
}
