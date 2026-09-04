<?php

namespace App\Services\Feature;

use Illuminate\Contracts\Config\Repository;

final class FeatureRegistry
{
    public function __construct(
        private readonly Repository $config,
    ) {}

    /**
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     default: array<string, mixed>
     * }>
     */
    public function all(): array
    {
        $features = $this->config->get('features', []);

        if (! is_array($features)) {
            return [];
        }

        return array_filter(
            $features,
            static fn (mixed $feature, mixed $key): bool => is_string($key)
                && preg_match('/^[a-z][a-z0-9-]*$/', $key) === 1
                && is_array($feature)
                && is_string($feature['label'] ?? null)
                && is_string($feature['description'] ?? null)
                && is_array($feature['default'] ?? null),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @return array{
     *     label: string,
     *     description: string,
     *     default: array<string, mixed>
     * }|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
}
