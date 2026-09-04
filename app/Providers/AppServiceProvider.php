<?php

namespace App\Providers;

use App\Contracts\Flight\FlightSearchProvider;
use App\Contracts\Hotel\HotelSearchProvider;
use App\Contracts\Tour\TourSearchProvider;
use App\Contracts\Visa\VisaInformationProvider;
use App\Services\Flight\UnavailableFlightSearchProvider;
use App\Services\Hotel\UnavailableHotelSearchProvider;
use App\Services\Tour\UnavailableTourSearchProvider;
use App\Services\Travel\TravelServiceRegistry;
use App\Services\Visa\UnavailableVisaInformationProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

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

        $this->bindConfiguredProvider(
            HotelSearchProvider::class,
            'travel_services.services.hotels',
            UnavailableHotelSearchProvider::class,
        );

        $this->bindConfiguredProvider(
            TourSearchProvider::class,
            'travel_services.services.tours',
            UnavailableTourSearchProvider::class,
        );

        $this->bindConfiguredProvider(
            VisaInformationProvider::class,
            'travel_services.services.visa',
            UnavailableVisaInformationProvider::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(
            ['home', 'layouts.site'],
            function (IlluminateView $view): void {
                $view->with(
                    'travelServices',
                    app(TravelServiceRegistry::class)->all()
                );
            }
        );
    }

    /**
     * @param  class-string  $contract
     * @param  class-string  $fallback
     */
    private function bindConfiguredProvider(
        string $contract,
        string $configKey,
        string $fallback,
    ): void {
        $this->app->bind(
            $contract,
            function (Application $app) use (
                $contract,
                $configKey,
                $fallback,
            ): object {
                $providerName = (string) config(
                    $configKey.'.provider',
                    'unavailable'
                );

                $providers = config(
                    $configKey.'.providers',
                    []
                );

                $providerClass = is_array($providers)
                    ? ($providers[$providerName] ?? null)
                    : null;

                if (
                    ! is_string($providerClass) ||
                    ! is_a($providerClass, $contract, true)
                ) {
                    $providerClass = $fallback;
                }

                return $app->make($providerClass);
            }
        );
    }
}
