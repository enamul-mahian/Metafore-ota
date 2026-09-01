<?php

namespace App\Services\Flight;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class FlightBookingConfirmationIntentStore
{
    private const TTL_SECONDS = 600;

    private const CACHE_PREFIX =
        'flight_booking_confirmation_intent:';

    public function __construct(
        private readonly Encrypter $encrypter,
    ) {
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $offer
     * @param array<int, array<string, mixed>> $travelers
     * @param array<string, mixed> $revalidation
     */
    public function put(
        int $userId,
        array $criteria,
        array $offer,
        array $travelers,
        array $revalidation,
    ): string {
        do {
            $token = Str::random(64);

            $cacheKey =
                $this->cacheKey(
                    $userId,
                    $token,
                );
        } while (Cache::has($cacheKey));

        $encryptedPayload =
            $this->encrypter->encrypt([
                'criteria' => $criteria,
                'offer' => $offer,
                'travelers' => $travelers,
                'revalidation' => $revalidation,
                'created_at' =>
                    now()->toIso8601String(),
            ]);

        Cache::put(
            $cacheKey,
            $encryptedPayload,
            self::TTL_SECONDS,
        );

        return $token;
    }

    /**
     * @return array{
     *     criteria: array<string, mixed>,
     *     offer: array<string, mixed>,
     *     travelers: array<int, array<string, mixed>>,
     *     revalidation: array<string, mixed>,
     *     created_at: string
     * }|null
     */
    public function get(
        int $userId,
        string $token,
    ): ?array {
        if (strlen($token) !== 64) {
            return null;
        }

        $encryptedPayload =
            Cache::get(
                $this->cacheKey(
                    $userId,
                    $token,
                ),
            );

        if (! is_string($encryptedPayload)) {
            return null;
        }

        try {
            $intent =
                $this->encrypter->decrypt(
                    $encryptedPayload,
                );
        } catch (DecryptException) {
            return null;
        }

        if (
            ! is_array($intent)
            || ! isset(
                $intent['criteria'],
                $intent['offer'],
                $intent['travelers'],
                $intent['revalidation'],
                $intent['created_at'],
            )
            || ! is_array($intent['criteria'])
            || ! is_array($intent['offer'])
            || ! is_array($intent['travelers'])
            || ! is_array($intent['revalidation'])
            || ! is_string($intent['created_at'])
        ) {
            return null;
        }

        return $intent;
    }

    public function forget(
        int $userId,
        string $token,
    ): void {
        if (strlen($token) !== 64) {
            return;
        }

        Cache::forget(
            $this->cacheKey(
                $userId,
                $token,
            ),
        );
    }

    public function expiresInSeconds(): int
    {
        return self::TTL_SECONDS;
    }

    private function cacheKey(
        int $userId,
        string $token,
    ): string {
        return self::CACHE_PREFIX
            . $userId
            . ':'
            . hash(
                'sha256',
                $token,
            );
    }
}
