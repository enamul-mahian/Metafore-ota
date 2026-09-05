<?php

namespace App\Console\Commands;

use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('app:production-readiness')]
#[Description('Verify production readiness without contacting providers or changing state')]
final class CheckProductionReadiness extends Command
{
    private const FAIL = 'FAIL';

    private const PASS = 'PASS';

    private const WARN = 'WARN';

    public function handle(): int
    {
        $checks = [
            $this->check(
                app()->environment('production'),
                'Application environment is production',
            ),
            $this->check(
                config('app.debug') === false,
                'Debug mode is disabled',
            ),
            $this->check(
                filled(config('app.key')),
                'Application key is configured',
            ),
            $this->check(
                $this->applicationUrlUsesHttps(),
                'Application URL uses HTTPS',
            ),
            $this->check(
                config('logging.channels.single.level') !== 'debug',
                'Application log level is not debug',
            ),
            ...$this->databaseChecks(),
            $this->check(
                $this->sessionDriverIsPersistent(),
                'Session driver is persistent and configured',
            ),
            $this->check(
                config('session.secure') === true,
                'Session cookies require HTTPS',
            ),
            $this->check(
                in_array(config('session.same_site'), ['lax', 'strict'], true),
                'Session SameSite policy is restrictive',
            ),
            $this->check(
                $this->configuredStoreIsPersistent(
                    'cache.default',
                    'cache.stores',
                    ['array', 'null'],
                ),
                'Cache store is persistent and configured',
            ),
            $this->check(
                $this->configuredStoreIsPersistent(
                    'queue.default',
                    'queue.connections',
                    ['sync', 'null'],
                ),
                'Queue connection is asynchronous and configured',
            ),
            $this->check(
                $this->mailTransportIsConfigured(),
                'Mail transport is configured for delivery',
            ),
            ...$this->runtimeFilesystemChecks(),
            $this->check(
                is_file(public_path('build/manifest.json')),
                'Production asset manifest exists',
            ),
            $this->publicStorageLinkCheck(),
            $this->check(
                config('flight_orders.http_execution_enabled') === false,
                'Flight HTTP order execution remains disabled',
            ),
            $this->check(
                config('flight_orders.duffel.live_order_creation_enabled') === false,
                'Duffel live order creation remains disabled',
            ),
            ...$this->travelProviderChecks(),
        ];

        $this->newLine();
        $this->info('Production readiness checks');

        $hasFailures = false;

        foreach ($checks as $check) {
            $line = "[{$check['status']}] {$check['label']}";

            match ($check['status']) {
                self::FAIL => $this->error($line),
                self::WARN => $this->warn($line),
                default => $this->line($line),
            };

            $hasFailures = $hasFailures || $check['status'] === self::FAIL;
        }

        $this->newLine();

        if ($hasFailures) {
            $this->error('Production readiness verification failed. No state was changed.');

            return self::FAILURE;
        }

        $this->info('Production readiness verification passed. No state was changed.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{status: string, label: string}>
     */
    private function databaseChecks(): array
    {
        $connectionName = config('database.default');
        $connection = is_string($connectionName)
            ? config("database.connections.{$connectionName}")
            : null;

        $configured = is_string($connectionName)
            && $connectionName !== ''
            && is_array($connection)
            && filled($connection['database'] ?? null);

        $checks = [
            $this->check(
                $configured,
                'Database connection is configured',
            ),
        ];

        if (! $configured) {
            $checks[] = $this->check(false, 'Database is reachable');
            $checks[] = $this->warning(
                'Migration status was not checked because the database is not configured',
            );

            return $checks;
        }

        try {
            DB::connection($connectionName)->getPdo();
            $checks[] = $this->check(true, 'Database is reachable');
        } catch (Throwable) {
            $checks[] = $this->check(false, 'Database is reachable');
            $checks[] = $this->warning(
                'Migration status was not checked because the database is unreachable',
            );

            return $checks;
        }

        try {
            /** @var Migrator $migrator */
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                $checks[] = $this->check(
                    false,
                    'Migration repository exists and all migrations are applied',
                );

                return $checks;
            }

            $migrationFiles = array_keys(
                $migrator->getMigrationFiles(database_path('migrations')),
            );
            $pendingMigrations = array_diff(
                $migrationFiles,
                $migrator->getRepository()->getRan(),
            );

            $checks[] = $this->check(
                $pendingMigrations === [],
                'Migration repository exists and all migrations are applied',
            );
        } catch (Throwable) {
            $checks[] = $this->check(
                false,
                'Migration status can be queried safely',
            );
        }

        return $checks;
    }

    /**
     * @param  list<string>  $disallowed
     */
    private function configuredStoreIsPersistent(
        string $selectionKey,
        string $storesKey,
        array $disallowed,
    ): bool {
        $selected = config($selectionKey);

        return is_string($selected)
            && ! in_array($selected, $disallowed, true)
            && is_array(config("{$storesKey}.{$selected}"));
    }

    private function applicationUrlUsesHttps(): bool
    {
        $url = config('app.url');

        return is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && filled(parse_url($url, PHP_URL_HOST));
    }

    private function sessionDriverIsPersistent(): bool
    {
        return in_array(
            config('session.driver'),
            ['file', 'cookie', 'database', 'memcached', 'redis', 'dynamodb'],
            true,
        );
    }

    private function mailTransportIsConfigured(): bool
    {
        $mailer = config('mail.default');

        if (
            ! is_string($mailer)
            || in_array($mailer, ['array', 'log'], true)
            || ! is_array(config("mail.mailers.{$mailer}"))
        ) {
            return false;
        }

        if ($mailer === 'smtp' && ! filled(config('mail.mailers.smtp.host'))) {
            return false;
        }

        return filter_var(
            config('mail.from.address'),
            FILTER_VALIDATE_EMAIL,
        ) !== false;
    }

    /**
     * @return list<array{status: string, label: string}>
     */
    private function runtimeFilesystemChecks(): array
    {
        return [
            $this->check(
                is_dir(storage_path()) && is_writable(storage_path()),
                'Storage directory is writable',
            ),
            $this->check(
                is_dir(base_path('bootstrap/cache'))
                    && is_writable(base_path('bootstrap/cache')),
                'Bootstrap cache directory is writable',
            ),
        ];
    }

    /**
     * @return array{status: string, label: string}
     */
    private function publicStorageLinkCheck(): array
    {
        if (is_link(public_path('storage'))) {
            return $this->check(true, 'Public storage link exists');
        }

        return $this->warning(
            'Public storage link is absent; current application features do not require public uploads',
        );
    }

    /**
     * @return list<array{status: string, label: string}>
     */
    private function travelProviderChecks(): array
    {
        $checks = [];
        $capabilities = app(TravelServiceRegistry::class)->all();

        foreach (['hotels', 'tours', 'visa'] as $service) {
            $label = ucfirst($service);
            $enabled = config(
                "travel_services.services.{$service}.enabled",
            ) === true;

            if (! $enabled) {
                $checks[] = $this->check(
                    true,
                    "{$label} provider is intentionally disabled",
                );

                continue;
            }

            $checks[] = $this->check(
                ($capabilities[$service]['available'] ?? false) === true,
                "{$label} enabled provider configuration is complete",
            );
        }

        return $checks;
    }

    /**
     * @return array{status: string, label: string}
     */
    private function check(bool $passed, string $label): array
    {
        return [
            'status' => $passed ? self::PASS : self::FAIL,
            'label' => $label,
        ];
    }

    /**
     * @return array{status: string, label: string}
     */
    private function warning(string $label): array
    {
        return [
            'status' => self::WARN,
            'label' => $label,
        ];
    }
}
