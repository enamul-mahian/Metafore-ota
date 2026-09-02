<?php

namespace App\Providers;

use App\Contracts\Flight\FlightSearchProvider;
use App\Services\Flight\UnavailableFlightSearchProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            FlightSearchProvider::class,
            function (Application $app): FlightSearchProvider {
                $providerName = (string) config(
                    'flight.search_provider',
                    'unavailable'
                );

                $providers = config(
                    'flight.providers',
                    []
                );

                $providerClass = is_array($providers)
                    ? ($providers[$providerName] ?? null)
                    : null;

                if (
                    ! is_string($providerClass) ||
                    ! is_a(
                        $providerClass,
                        FlightSearchProvider::class,
                        true
                    )
                ) {
                    $providerClass =
                        UnavailableFlightSearchProvider::class;
                }

                return $app->make($providerClass);
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
