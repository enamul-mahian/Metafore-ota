<?php

namespace App\Services\Travel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Route;

class TravelServiceRegistry
{
    public function __construct(
        private readonly Repository $config
    ) {}

    /**
     * Return capabilities safe for rendering in customer-facing views.
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     available: bool,
     *     status: string,
     *     route_name: string|null,
     *     permission: string|null
     * }>
     */
    public function all(): array
    {
        $services = $this->config->get(
            'travel_services.services',
            []
        );

        if (! is_array($services)) {
            return [];
        }

        $capabilities = [];

        foreach ($services as $key => $service) {
            if (! is_string($key) || ! is_array($service)) {
                continue;
            }

            $available = $this->isAvailable($service);
            $routeName = $service['route_name'] ?? null;

            $capabilities[$key] = [
                'key' => $key,
                'label' => (string) ($service['label'] ?? ucfirst($key)),
                'available' => $available,
                'status' => $available
                    ? 'Available'
                    : (string) ($service['unavailable_label'] ?? 'Not Configured'),
                'route_name' => $available && is_string($routeName)
                    ? $routeName
                    : null,
                'permission' => is_string($service['permission'] ?? null)
                    ? $service['permission']
                    : null,
            ];
        }

        return $capabilities;
    }

    /**
     * @param  array<string, mixed>  $service
     */
    private function isAvailable(array $service): bool
    {
        if (($service['enabled'] ?? false) !== true) {
            return false;
        }

        $routeName = $service['route_name'] ?? null;

        if (! is_string($routeName) || ! Route::has($routeName)) {
            return false;
        }

        if (($service['provider_required'] ?? true) === false) {
            return true;
        }

        $providerName = $service['provider'] ?? null;
        $contract = $service['contract'] ?? null;
        $providers = $service['providers'] ?? [];

        if (
            ! is_string($providerName) ||
            $providerName === '' ||
            $providerName === 'unavailable' ||
            ! is_string($contract) ||
            ! is_array($providers)
        ) {
            return false;
        }

        $providerClass = $providers[$providerName] ?? null;

        if (
            ! is_string($providerClass) ||
            ! is_a($providerClass, $contract, true)
        ) {
            return false;
        }

        $requirements = $service['provider_requirements'][$providerName]
            ?? [];

        if (! is_array($requirements)) {
            return false;
        }

        foreach ($requirements as $requirement) {
            if (! is_string($requirement)) {
                return false;
            }

            $value = data_get($service, $requirement);

            if (! is_string($value) || trim($value) === '') {
                return false;
            }
        }

        return true;
    }
}
