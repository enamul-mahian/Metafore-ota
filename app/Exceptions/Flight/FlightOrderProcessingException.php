<?php

namespace App\Exceptions\Flight;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderProcessingException extends ServiceUnavailableHttpException
{
    private readonly string $provider;

    public function __construct(
        string $provider,
    ) {
        $provider =
            strtolower(
                trim(
                    $provider,
                ),
            );

        if (
            $provider === ''
            || strlen($provider) > 64
            || preg_match(
                '/^[a-z0-9_-]+$/',
                $provider,
            ) !== 1
        ) {
            $provider =
                'unknown';
        }

        $this->provider =
            $provider;

        /*
         * Unknown callers continue to fail closed because this typed
         * processing signal is also a ServiceUnavailable exception.
         *
         * No retry instruction is attached because this supplier attempt
         * must not be blindly replayed.
         */
        parent::__construct(
            null,
            'Flight order creation is still processing.',
        );
    }

    public function provider(): string
    {
        return $this->provider;
    }
}