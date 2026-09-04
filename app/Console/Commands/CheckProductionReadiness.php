<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:production-readiness')]
#[Description('Verify production configuration without contacting providers or changing state')]
final class CheckProductionReadiness extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checks = [
            'Application environment is production' => app()->environment('production'),
            'Debug mode is disabled' => config('app.debug') === false,
            'Application key is configured' => filled(config('app.key')),
            'Application URL uses HTTPS' => str_starts_with(
                (string) config('app.url'),
                'https://',
            ),
            'Log level is not debug' => config('logging.channels.single.level') !== 'debug',
            'Session driver is persistent' => config('session.driver') !== 'array',
            'Cache store is persistent' => ! in_array(
                config('cache.default'),
                ['array', 'null'],
                true,
            ),
            'Queue connection is asynchronous' => ! in_array(
                config('queue.default'),
                ['sync', 'null'],
                true,
            ),
            'Mail transport is configured for delivery' => ! in_array(
                config('mail.default'),
                ['array', 'log'],
                true,
            ),
            'Flight HTTP order execution remains disabled' => config(
                'flight_orders.http_execution_enabled',
            ) === false,
            'Duffel live order creation remains disabled' => config(
                'flight_orders.duffel.live_order_creation_enabled',
            ) === false,
            ...$this->inactiveTravelProviderChecks(),
        ];

        $this->newLine();
        $this->info('Production readiness checks');

        $hasFailures = false;

        foreach ($checks as $label => $passed) {
            if ($passed) {
                $this->line("[PASS] {$label}");

                continue;
            }

            $hasFailures = true;
            $this->error("[FAIL] {$label}");
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
     * @return array<string, bool>
     */
    private function inactiveTravelProviderChecks(): array
    {
        $checks = [];

        foreach (['hotels', 'tours', 'visa'] as $service) {
            $label = ucfirst($service);
            $configPrefix = "travel_services.services.{$service}";

            $checks["{$label} provider activation remains disabled"] = config(
                "{$configPrefix}.enabled",
            ) === false;
            $checks["{$label} provider remains unavailable"] = config(
                "{$configPrefix}.provider",
            ) === 'unavailable';
        }

        return $checks;
    }
}
