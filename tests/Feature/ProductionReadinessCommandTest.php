<?php

namespace Tests\Feature;

use App\Contracts\Hotel\HotelSearchProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ProductionReadinessCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $readinessPublicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->readinessPublicPath = storage_path(
            'framework/testing/readiness-public-'.Str::uuid(),
        );

        File::ensureDirectoryExists(
            $this->readinessPublicPath.DIRECTORY_SEPARATOR.'build',
        );
        File::put(
            $this->readinessPublicPath
                .DIRECTORY_SEPARATOR.'build'
                .DIRECTORY_SEPARATOR.'manifest.json',
            json_encode(
                [
                    'resources/css/app.css' => [
                        'file' => 'assets/app.css',
                        'src' => 'resources/css/app.css',
                        'isEntry' => true,
                    ],
                    'resources/js/app.js' => [
                        'file' => 'assets/app.js',
                        'src' => 'resources/js/app.js',
                        'isEntry' => true,
                    ],
                ],
                JSON_THROW_ON_ERROR,
            ),
        );

        $this->app->usePublicPath($this->readinessPublicPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->readinessPublicPath);

        parent::tearDown();
    }

    public function test_health_endpoint_is_coarse_and_reveals_no_sensitive_configuration(): void
    {
        $secrets = [
            'health-app-key-secret',
            'health-database-password',
            'health-provider-token',
        ];

        config([
            'app.key' => $secrets[0],
            'database.connections.sqlite.password' => $secrets[1],
            'travel_services.services.hotels.duffel.access_token' => $secrets[2],
        ]);

        $response = $this->get('/up')->assertOk();

        foreach ($secrets as $secret) {
            $response->assertDontSee($secret);
        }

        $response
            ->assertDontSee('DB_PASSWORD')
            ->assertDontSee('APP_KEY')
            ->assertDontSee(storage_path());
    }

    public function test_safe_production_configuration_passes_without_changing_state(): void
    {
        $this->configureSafeProductionEnvironment();

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[PASS] Application environment is production')
            ->expectsOutputToContain('[PASS] Database is reachable')
            ->expectsOutputToContain('[PASS] Migration repository exists and all migrations are applied')
            ->expectsOutputToContain('[PASS] Production asset manifest exists')
            ->expectsOutputToContain('[WARN] Public storage link is absent')
            ->expectsOutputToContain('[PASS] Flight HTTP order execution remains disabled')
            ->expectsOutputToContain('[PASS] Duffel live order creation remains disabled')
            ->expectsOutputToContain('[PASS] Hotels provider is intentionally disabled')
            ->expectsOutputToContain('Production readiness verification passed. No state was changed.')
            ->assertSuccessful();
    }

    public function test_production_with_debug_enabled_fails(): void
    {
        $this->configureSafeProductionEnvironment();
        config(['app.debug' => true]);

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[FAIL] Debug mode is disabled')
            ->assertFailed();
    }

    public function test_missing_application_key_fails(): void
    {
        $this->configureSafeProductionEnvironment();
        config(['app.key' => null]);

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[FAIL] Application key is configured')
            ->assertFailed();
    }

    public function test_non_https_production_url_fails(): void
    {
        $this->configureSafeProductionEnvironment();
        config(['app.url' => 'http://ota.example.test']);

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[FAIL] Application URL uses HTTPS')
            ->assertFailed();
    }

    public function test_disabled_optional_providers_do_not_require_credentials(): void
    {
        $this->configureSafeProductionEnvironment();

        config([
            'travel_services.services.hotels.duffel.access_token' => null,
            'travel_services.services.tours.viator.api_key' => null,
            'travel_services.services.visa.sherpa.api_key' => null,
        ]);

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[PASS] Hotels provider is intentionally disabled')
            ->expectsOutputToContain('[PASS] Tours provider is intentionally disabled')
            ->expectsOutputToContain('[PASS] Visa provider is intentionally disabled')
            ->assertSuccessful();
    }

    public function test_enabled_provider_without_required_credentials_fails(): void
    {
        $this->configureSafeProductionEnvironment();

        config([
            'travel_services.services.hotels.enabled' => true,
            'travel_services.services.hotels.provider' => 'duffel',
            'travel_services.services.hotels.duffel.access_token' => null,
        ]);

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[FAIL] Hotels enabled provider configuration is complete')
            ->assertFailed();
    }

    public function test_enabled_provider_with_complete_configuration_passes(): void
    {
        $this->configureSafeProductionEnvironment();

        config([
            'travel_services.services.hotels.enabled' => true,
            'travel_services.services.hotels.provider' => 'readiness-test',
            'travel_services.services.hotels.providers.readiness-test' => ReadinessHotelProvider::class,
            'travel_services.services.hotels.provider_requirements.readiness-test' => [
                'credentials.api_key',
            ],
            'travel_services.services.hotels.credentials.api_key' => 'complete-provider-secret',
        ]);

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[PASS] Hotels enabled provider configuration is complete')
            ->doesntExpectOutputToContain('complete-provider-secret')
            ->assertSuccessful();
    }

    public function test_unsafe_live_order_flags_fail(): void
    {
        $this->configureSafeProductionEnvironment();

        config([
            'flight_orders.http_execution_enabled' => true,
            'flight_orders.duffel.live_order_creation_enabled' => true,
        ]);

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[FAIL] Flight HTTP order execution remains disabled')
            ->expectsOutputToContain('[FAIL] Duffel live order creation remains disabled')
            ->assertFailed();
    }

    public function test_output_never_exposes_secret_values(): void
    {
        $this->configureSafeProductionEnvironment();

        $secrets = [
            'base64:readiness-application-secret',
            'readiness-database-secret',
            'readiness-mail-secret',
            'readiness-provider-secret',
        ];

        config([
            'app.key' => $secrets[0],
            'database.connections.sqlite.password' => $secrets[1],
            'mail.mailers.smtp.password' => $secrets[2],
            'travel_services.services.hotels.duffel.access_token' => $secrets[3],
        ]);

        $command = $this->artisan('app:production-readiness');

        foreach ($secrets as $secret) {
            $command->doesntExpectOutputToContain($secret);
        }

        $command->assertSuccessful();
    }

    public function test_missing_asset_manifest_fails(): void
    {
        $this->configureSafeProductionEnvironment();

        File::delete(
            public_path('build'.DIRECTORY_SEPARATOR.'manifest.json'),
        );

        $this->artisan('app:production-readiness')
            ->expectsOutputToContain('[FAIL] Production asset manifest exists')
            ->assertFailed();
    }

    public function test_normal_application_routes_remain_unaffected(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('about'))->assertOk();
    }

    private function configureSafeProductionEnvironment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        config([
            'app.debug' => false,
            'app.key' => 'base64:test-production-key',
            'app.url' => 'https://ota.example.test',
            'logging.channels.single.level' => 'info',
            'session.driver' => 'database',
            'session.secure' => true,
            'session.same_site' => 'lax',
            'cache.default' => 'database',
            'queue.default' => 'database',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'mail.example.test',
            'mail.from.address' => 'delivery@example.test',
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

final class ReadinessHotelProvider implements HotelSearchProvider
{
    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array
    {
        return [];
    }
}
