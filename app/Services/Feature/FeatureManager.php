<?php

namespace App\Services\Feature;

use App\Models\User;
use App\Services\SettingService;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use JsonException;

final class FeatureManager
{
    /**
     * @var array<string, array{
     *     enabled: bool,
     *     public_visible: bool,
     *     authenticated_visible: bool,
     *     admin_visible: bool,
     *     message: string|null
     * }>
     */
    private array $resolvedStates = [];

    private ?bool $settingsTableExists = null;

    public function __construct(
        private readonly FeatureRegistry $registry,
        private readonly SettingService $settings,
    ) {}

    /**
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     enabled: bool,
     *     public_visible: bool,
     *     authenticated_visible: bool,
     *     admin_visible: bool,
     *     message: string|null
     * }>
     */
    public function all(): array
    {
        $features = [];

        foreach ($this->registry->all() as $key => $definition) {
            $state = $this->state($key);

            if ($state === null) {
                continue;
            }

            $features[$key] = [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                ...$state,
            ];
        }

        return $features;
    }

    public function isRegistered(string $key): bool
    {
        return $this->registry->has($key);
    }

    public function isEnabled(string $key): bool
    {
        return ($this->state($key)['enabled'] ?? false) === true;
    }

    public function isVisibleTo(string $key, ?User $user): bool
    {
        $state = $this->state($key);

        if ($state === null || $state['enabled'] !== true) {
            return false;
        }

        if ($user === null) {
            return $state['public_visible'];
        }

        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return $state['admin_visible'];
        }

        return $state['authenticated_visible'];
    }

    public function unavailableMessage(string $key): string
    {
        $message = $this->state($key)['message'] ?? null;

        return is_string($message) && $message !== ''
            ? $message
            : 'This feature is currently unavailable.';
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, bool|string|null>
     */
    public function update(string $key, array $attributes): array
    {
        if (! $this->registry->has($key)) {
            throw new InvalidArgumentException(
                "Feature [{$key}] is not registered.",
            );
        }

        $state = $this->normalizePersistedState($attributes);

        $this->settings->set(
            'features',
            $key,
            $state,
            'json',
            false,
        );

        unset($this->resolvedStates[$key]);

        return $this->state($key) ?? $this->disabledState();
    }

    /**
     * @return array{
     *     enabled: bool,
     *     public_visible: bool,
     *     authenticated_visible: bool,
     *     admin_visible: bool,
     *     message: string|null
     * }|null
     */
    private function state(string $key): ?array
    {
        if (array_key_exists($key, $this->resolvedStates)) {
            return $this->resolvedStates[$key];
        }

        $definition = $this->registry->get($key);

        if ($definition === null) {
            return null;
        }

        if (! $this->settingsTableExists()) {
            $state = $this->normalizeDefaultState($definition['default']);
        } elseif (! $this->settings->exists('features', $key)) {
            $state = $this->normalizeDefaultState($definition['default']);
        } else {
            try {
                $stored = $this->settings->get('features', $key);
            } catch (JsonException) {
                $stored = null;
            }

            if (is_array($stored)) {
                $state = $this->normalizePersistedState($stored);
            } else {
                $state = $this->disabledState();
            }
        }

        return $this->resolvedStates[$key] = $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, bool|string|null>
     */
    private function normalizeDefaultState(array $state): array
    {
        foreach ($this->booleanKeys() as $key) {
            if (! is_bool($state[$key] ?? null)) {
                return $this->disabledState();
            }
        }

        return [
            'enabled' => $state['enabled'],
            'public_visible' => $state['public_visible'],
            'authenticated_visible' => $state['authenticated_visible'],
            'admin_visible' => $state['admin_visible'],
            'message' => is_string($state['message'] ?? null)
                ? trim($state['message']) ?: null
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, bool|string|null>
     */
    private function normalizePersistedState(array $state): array
    {
        foreach ($this->booleanKeys() as $key) {
            if (! is_bool($state[$key] ?? null)) {
                return $this->disabledState();
            }
        }

        $message = $state['message'] ?? null;

        if (! is_string($message) && $message !== null) {
            return $this->disabledState();
        }

        return [
            'enabled' => $state['enabled'],
            'public_visible' => $state['public_visible'],
            'authenticated_visible' => $state['authenticated_visible'],
            'admin_visible' => $state['admin_visible'],
            'message' => is_string($message)
                ? trim($message) ?: null
                : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function booleanKeys(): array
    {
        return [
            'enabled',
            'public_visible',
            'authenticated_visible',
            'admin_visible',
        ];
    }

    private function settingsTableExists(): bool
    {
        return $this->settingsTableExists ??= Schema::hasTable('settings');
    }

    /**
     * @return array{
     *     enabled: false,
     *     public_visible: false,
     *     authenticated_visible: false,
     *     admin_visible: false,
     *     message: null
     * }
     */
    private function disabledState(): array
    {
        return [
            'enabled' => false,
            'public_visible' => false,
            'authenticated_visible' => false,
            'admin_visible' => false,
            'message' => null,
        ];
    }
}
