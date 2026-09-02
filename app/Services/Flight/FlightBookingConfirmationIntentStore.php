<?php

namespace App\Services\Flight;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class FlightBookingConfirmationIntentStore
{
    private const TTL_SECONDS = 600;

    private const CONSUME_LOCK_SECONDS = 15;

    private const CACHE_PREFIX =
        'flight_booking_confirmation_intent:';

    private const CONSUME_LOCK_PREFIX =
        'flight_booking_confirmation_intent_consume_lock:';

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
     * Read without consuming.
     *
     * This remains available for safe server-side inspection and
     * compatibility with the existing confirmation-intent foundation.
     *
     * Future supplier order execution must use take(), not get()
     * followed later by forget().
     *
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

        return $this->decryptPayload(
            Cache::get(
                $this->cacheKey(
                    $userId,
                    $token,
                ),
            ),
        );
    }

    /**
     * Atomically claim and consume a confirmation intent.
     *
     * The per-user, per-token cache lock prevents two execution
     * requests from both receiving the same trusted snapshot.
     *
     * The cached intent is removed while the lock is held and before
     * the trusted snapshot is returned to the future supplier-order
     * execution boundary.
     *
     * @return array{
     *     criteria: array<string, mixed>,
     *     offer: array<string, mixed>,
     *     travelers: array<int, array<string, mixed>>,
     *     revalidation: array<string, mixed>,
     *     created_at: string
     * }|null
     */
    public function take(
        int $userId,
        string $token,
    ): ?array {
        if (strlen($token) !== 64) {
            return null;
        }

        $cacheKey =
            $this->cacheKey(
                $userId,
                $token,
            );

        $lock =
            Cache::lock(
                $this->consumeLockKey(
                    $userId,
                    $token,
                ),
                self::CONSUME_LOCK_SECONDS,
            );

        if (! $lock->get()) {
            return null;
        }

        try {
            /*
             * Consume while the lock is held.
             *
             * Do not implement this as get() followed later by forget()
             * outside the lock; that would leave a replay race.
             */
            $encryptedPayload =
                Cache::get(
                    $cacheKey,
                );

            Cache::forget(
                $cacheKey,
            );

            return $this->decryptPayload(
                $encryptedPayload,
            );
        } finally {
            $lock->release();
        }
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

    /**
     * @return array{
     *     criteria: array<string, mixed>,
     *     offer: array<string, mixed>,
     *     travelers: array<int, array<string, mixed>>,
     *     revalidation: array<string, mixed>,
     *     created_at: string
     * }|null
     */
    private function decryptPayload(
        mixed $encryptedPayload,
    ): ?array {
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

    private function consumeLockKey(
        int $userId,
        string $token,
    ): string {
        return self::CONSUME_LOCK_PREFIX
            . $userId
            . ':'
            . hash(
                'sha256',
                $token,
            );
    }
}