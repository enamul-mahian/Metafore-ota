<?php

namespace App\Services\Flight;

use Illuminate\Support\Facades\Cache;

final class FlightOrderAttemptStore
{
    /*
     * Longer than the current confirmation-intent TTL.
     *
     * This is not booking/order persistence. It is a volatile safety
     * marker that prevents another local supplier-order POST attempt
     * for the same trusted provider + supplier offer identity.
     */
    private const TTL_SECONDS = 86400;

    private const CACHE_PREFIX =
        'flight_order_attempt:';

    /**
     * Atomically claim one supplier-order attempt.
     *
     * Different confirmation-intent tokens that resolve to the same
     * trusted provider + supplier offer ID resolve to the same key.
     *
     * The claim deliberately has no release method. Once supplier HTTP
     * is about to begin, timeout/error/202/malformed responses can have
     * an uncertain supplier outcome and must not become blind retries.
     */
    public function claim(
        string $provider,
        string $offerId,
    ): bool {
        $provider =
            strtolower(
                trim(
                    $provider,
                ),
            );

        $offerId =
            trim(
                $offerId,
            );

        if (
            $provider === ''
            || strlen($provider) > 64
            || preg_match(
                '/^[a-z0-9_-]+$/',
                $provider,
            ) !== 1
            || $offerId === ''
            || strlen($offerId) > 255
        ) {
            return false;
        }

        return Cache::add(
            $this->cacheKey(
                $provider,
                $offerId,
            ),
            true,
            self::TTL_SECONDS,
        );
    }

    public function expiresInSeconds(): int
    {
        return self::TTL_SECONDS;
    }

    private function cacheKey(
        string $provider,
        string $offerId,
    ): string {
        return self::CACHE_PREFIX
            . hash(
                'sha256',
                $provider
                    . "\0"
                    . $offerId,
            );
    }
}