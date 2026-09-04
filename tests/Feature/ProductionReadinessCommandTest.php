<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionReadinessCommandTest extends TestCase
{
    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_safe_production_configuration_passes_without_changing_state(): void
    {
        $this->configureSafeProductionEnvironment();

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[PASS] Application environment is production')
            ->expectsOutputToContain('[PASS] Flight HTTP order execution remains disabled')
            ->expectsOutputToContain('[PASS] Duffel live order creation remains disabled')
            ->expectsOutputToContain('[PASS] Hotels provider activation remains disabled')
            ->expectsOutputToContain('Production readiness verification passed. No state was changed.')
            ->assertSuccessful();
    }

    public function test_unsafe_configuration_fails_and_reports_every_safety_gate(): void
    {
        $this->configureSafeProductionEnvironment();

        config([
            'app.debug' => true,
            'flight_orders.http_execution_enabled' => true,
            'flight_orders.duffel.live_order_creation_enabled' => true,
            'travel_services.services.hotels.enabled' => true,
            'travel_services.services.hotels.provider' => 'duffel',
            'travel_services.services.tours.enabled' => true,
            'travel_services.services.tours.provider' => 'viator',
            'travel_services.services.visa.enabled' => true,
            'travel_services.services.visa.provider' => 'sherpa',
        ]);

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[FAIL] Debug mode is disabled')
            ->expectsOutputToContain('[FAIL] Flight HTTP order execution remains disabled')
            ->expectsOutputToContain('[FAIL] Duffel live order creation remains disabled')
            ->expectsOutputToContain('[FAIL] Hotels provider activation remains disabled')
            ->expectsOutputToContain('[FAIL] Tours provider activation remains disabled')
            ->expectsOutputToContain('[FAIL] Visa provider activation remains disabled')
            ->expectsOutputToContain('Production readiness verification failed. No state was changed.')
            ->assertFailed();
    }

    public function test_output_never_exposes_the_application_key(): void
    {
        $this->configureSafeProductionEnvironment();

        $applicationKey = 'base64:do-not-render-this-production-secret';

        config(['app.key' => $applicationKey]);

        $this->artisan('app:production-readiness')
            ->doesntExpectOutputToContain($applicationKey)
            ->assertSuccessful();
    }

    private function configureSafeProductionEnvironment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        config([
            'app.debug' => false,
            'app.key' => 'base64:test-production-key',
            'app.url' => 'https://ota.example.com',
            'logging.channels.single.level' => 'info',
            'session.driver' => 'database',
            'cache.default' => 'database',
            'queue.default' => 'database',
            'mail.default' => 'smtp',
            'flight_orders.http_execution_enabled' => false,
            'flight_orders.duffel.live_order_creation_enabled' => false,
            'travel_services.services.hotels.enabled' => false,
            'travel_services.services.hotels.provider' => 'unavailable',
            'travel_services.services.tours.enabled' => false,
            'travel_services.services.tours.provider' => 'unavailable',
            'travel_services.services.visa.enabled' => false,
            'travel_services.services.visa.provider' => 'unavailable',
        ]);
    }
}
