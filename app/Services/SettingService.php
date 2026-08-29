<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use JsonException;

class SettingService
{
    private const CACHE_PREFIX = 'settings:';

    private const CACHE_TTL_SECONDS = 3600;

    /**
     * Get a setting value.
     */
    public function get(
        string $group,
        string $key,
        mixed $default = null
    ): mixed {
        $cached = Cache::remember(
            $this->cacheKey($group, $key),
            self::CACHE_TTL_SECONDS,
            function () use ($group, $key): ?array {
                $setting = Setting::query()
                    ->where('group', $group)
                    ->where('key', $key)
                    ->first();

                if (! $setting) {
                    return null;
                }

                return [
                    'value' => $setting->value,
                    'type' => $setting->type,
                ];
            }
        );

        if ($cached === null) {
            return $default;
        }

        return $this->castValue(
            $cached['value'],
            $cached['type']
        );
    }

    /**
     * Create or update a setting.
     */
    public function set(
        string $group,
        string $key,
        mixed $value,
        string $type = 'string',
        bool $isPublic = false
    ): Setting {
        $type = strtolower($type);

        $this->validateType($type);

        $setting = Setting::query()->updateOrCreate(
            [
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => $this->prepareValueForStorage(
                    $value,
                    $type
                ),
                'type' => $type,
                'is_public' => $isPublic,
            ]
        );

        $this->forget($group, $key);

        return $setting->refresh();
    }

    /**
     * Remove one cached setting.
     */
    public function forget(
        string $group,
        string $key
    ): void {
        Cache::forget(
            $this->cacheKey($group, $key)
        );
    }

    /**
     * Delete a setting.
     */
    public function delete(
        string $group,
        string $key
    ): bool {
        $deleted = Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->delete();

        $this->forget($group, $key);

        return $deleted > 0;
    }

    /**
     * Build the cache key.
     */
    private function cacheKey(
        string $group,
        string $key
    ): string {
        return self::CACHE_PREFIX.$group.'.'.$key;
    }

    /**
     * Validate supported setting types.
     */
    private function validateType(string $type): void
    {
        $supportedTypes = [
            'string',
            'integer',
            'float',
            'boolean',
            'json',
        ];

        if (! in_array($type, $supportedTypes, true)) {
            throw new InvalidArgumentException(
                "Unsupported setting type [{$type}]."
            );
        }
    }

    /**
     * Convert a PHP value into its database representation.
     *
     * @throws JsonException
     */
    private function prepareValueForStorage(
        mixed $value,
        string $type
    ): ?string {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (string) ((int) $value),

            'float' => (string) ((float) $value),

            'boolean' => $this->normalizeBoolean($value)
                ? '1'
                : '0',

            'json' => json_encode(
                $value,
                JSON_THROW_ON_ERROR
            ),

            default => (string) $value,
        };
    }

    /**
     * Convert a stored database value into its PHP type.
     *
     * @throws JsonException
     */
    private function castValue(
        ?string $value,
        string $type
    ): mixed {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,

            'float' => (float) $value,

            'boolean' => in_array(
                strtolower($value),
                ['1', 'true', 'yes', 'on'],
                true
            ),

            'json' => json_decode(
                $value,
                true,
                512,
                JSON_THROW_ON_ERROR
            ),

            default => $value,
        };
    }

    /**
     * Normalize common boolean representations safely.
     */
    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));

            if (in_array(
                $value,
                ['1', 'true', 'yes', 'on'],
                true
            )) {
                return true;
            }

            if (in_array(
                $value,
                ['0', 'false', 'no', 'off', ''],
                true
            )) {
                return false;
            }
        }

        throw new InvalidArgumentException(
            'Invalid boolean setting value.'
        );
    }
}