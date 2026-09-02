<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOrderProvider;

final class FlightOrderService
{
    /**
     * @param  array<string, mixed>  $trustedConfirmationIntent
     * @return array{
     *     status: string,
     *     provider: string|null,
     *     live_order_creation: bool,
     *     order_created: bool
     * }
     */
    public function createFromTrustedConfirmationIntent(
        array $trustedConfirmationIntent
    ): array {
        $provider = $this->normalizeProvider(
            $trustedConfirmationIntent['provider'] ?? null
        );

        $providers = config('flight_orders.providers', []);

        if (! is_array($providers)) {
            $providers = [];
        }

        $providerIsConfigured = $provider !== null
            && array_key_exists($provider, $providers);

        $providerClass = $providerIsConfigured
            ? $providers[$provider]
            : null;

        if (
            ! is_string($providerClass)
            || ! is_subclass_of($providerClass, FlightOrderProvider::class)
        ) {
            $providerClass = UnavailableFlightOrderProvider::class;
        }

        /** @var FlightOrderProvider $orderProvider */
        $orderProvider = app($providerClass);

        $result = $orderProvider->createFromTrustedConfirmationIntent(
            $trustedConfirmationIntent
        );

        $status = $result['status'] ?? null;

        $normalized = [
            'status' => is_string($status) && $status !== ''
                ? $status
                : 'unavailable',

            'provider' => $providerIsConfigured
                ? $provider
                : null,

            'live_order_creation' =>
                ($result['live_order_creation'] ?? false) === true,

            'order_created' =>
                ($result['order_created'] ?? false) === true,
        ];

        $supplierOrderId =
            $result['supplier_order_id']
                ?? null;

        if (is_string($supplierOrderId)) {
            $normalized['supplier_order_id'] =
                $supplierOrderId;
        }

        return $normalized;
    }

    private function normalizeProvider(mixed $provider): ?string
    {
        if (! is_string($provider)) {
            return null;
        }

        $provider = strtolower(trim($provider));

        return $provider !== ''
            ? $provider
            : null;
    }
}
