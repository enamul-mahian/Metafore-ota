<?php

namespace App\Services\Flight;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class FlightBookingDraftStore
{
    private const TTL_SECONDS = 900;

    private const CACHE_PREFIX = 'flight_booking_draft:';

    public function __construct(
        private readonly Encrypter $encrypter,
    ) {
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $offer
     * @param array<int, array<string, mixed>> $travelers
     */
    public function put(
        int $userId,
        array $criteria,
        array $offer,
        array $travelers,
    ): string {
        do {
            $token = Str::random(64);
            $cacheKey = $this->cacheKey($userId, $token);
        } while (Cache::has($cacheKey));

        $encryptedPayload = $this->encrypter->encrypt([
            'criteria' => $criteria,
            'offer' => $offer,
            'travelers' => $travelers,
            'created_at' => now()->toIso8601String(),
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
     *     created_at: string
     * }|null
     */
    public function get(int $userId, string $token): ?array
    {
        if (strlen($token) !== 64) {
            return null;
        }

        $encryptedPayload = Cache::get(
            $this->cacheKey($userId, $token)
        );

        if (! is_string($encryptedPayload)) {
            return null;
        }

        try {
            $draft = $this->encrypter->decrypt(
                $encryptedPayload
            );
        } catch (DecryptException) {
            return null;
        }

        if (
            ! is_array($draft)
            || ! isset(
                $draft['criteria'],
                $draft['offer'],
                $draft['travelers'],
                $draft['created_at'],
            )
            || ! is_array($draft['criteria'])
            || ! is_array($draft['offer'])
            || ! is_array($draft['travelers'])
            || ! is_string($draft['created_at'])
        ) {
            return null;
        }

        return $draft;
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
            .$userId
            .':'
            .hash('sha256', $token);
    }
}
