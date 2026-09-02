<?php

namespace App\Services\Flight;

use App\Contracts\Flight\FlightOfferRevalidationProvider;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOfferRevalidationService
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    /**
     * Revalidate a trusted server-side offer through its configured provider.
     *
     * The provider name is read from the trusted offer and used only as a key
     * into the server-owned configuration map. It is never treated as a class
     * name supplied dynamically by a client.
     *
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    public function revalidate(array $offer): array
    {
        $providerName = data_get(
            $offer,
            'provider',
        );

        $providerName = is_string($providerName)
            ? strtolower(trim($providerName))
            : '';

        $providers = config(
            'flight_revalidation.providers',
            [],
        );

        $fallbackClass = config(
            'flight_revalidation.fallback',
            UnavailableFlightOfferRevalidationProvider::class,
        );

        if (! is_array($providers)) {
            $providers = [];
        }

        $providerClass = data_get(
            $providers,
            $providerName,
            $fallbackClass,
        );

        if (
            ! is_string($providerClass)
            || $providerClass === ''
            || ! class_exists($providerClass)
        ) {
            $providerClass =
                UnavailableFlightOfferRevalidationProvider::class;
        }

        $provider = $this->container->make(
            $providerClass,
        );

        if (
            ! $provider instanceof FlightOfferRevalidationProvider
        ) {
            throw new ServiceUnavailableHttpException(
                60,
                'Flight offer revalidation provider is invalid.',
            );
        }

        return $provider->revalidate(
            $offer,
        );
    }
}
