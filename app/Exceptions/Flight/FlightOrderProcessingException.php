<?php

namespace App\Exceptions\Flight;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderProcessingException extends ServiceUnavailableHttpException
{
    private ?string $supplierOfferId = null;

    private ?string $attemptReference = null;

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

    public function withSupplierOfferId(
        string $supplierOfferId,
    ): self {
        $supplierOfferId =
            trim(
                $supplierOfferId,
            );

        if (
            $this->supplierOfferId !== null
            || $supplierOfferId === ''
            || strlen($supplierOfferId) > 255
            || preg_match(
                '/[\x00-\x1F\x7F]/',
                $supplierOfferId,
            ) === 1
        ) {
            return $this;
        }

        $this->supplierOfferId =
            $supplierOfferId;

        return $this;
    }

    public function supplierOfferId(): ?string
    {
        return $this->supplierOfferId;
    }

    public function withAttemptReference(
        string $attemptReference,
    ): self {
        $attemptReference =
            trim(
                $attemptReference,
            );

        if (
            $this->attemptReference !== null
            || strlen($attemptReference) !== 64
            || preg_match(
                '/^[A-Za-z0-9]+$/',
                $attemptReference,
            ) !== 1
        ) {
            return $this;
        }

        $this->attemptReference =
            $attemptReference;

        return $this;
    }

    public function attemptReference(): ?string
    {
        return $this->attemptReference;
    }
}