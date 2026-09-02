<?php

use App\Http\Controllers\Flight\FlightBookingDraftController;
use App\Http\Controllers\Flight\FlightBookingDraftReviewController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SettingPageController;
use App\Http\Controllers\Flight\FlightOfferSelectionController;
use App\Http\Controllers\Flight\FlightTravelerValidationController;
use App\Http\Controllers\Flight\FlightSearchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::view('/flights', 'flights.search')
        ->middleware('permission:flights.search')
        ->name('flights.index');

    Route::post(
        '/flights/search',
        FlightSearchController::class
    )
        ->middleware('permission:flights.search')
        ->name('flights.search');
    Route::post(
        '/flights/offers/select',
        FlightOfferSelectionController::class
    )
        ->middleware('permission:flights.search')
        ->name('flights.offers.select');
    Route::post(
        '/flights/travelers/validate',
        FlightTravelerValidationController::class
    )
        ->middleware('permission:flights.search')
        ->name('flights.travelers.validate');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin|super-admin')
        ->group(function () {

            /**
             * |--------------------------------------------------------------------------
             * | Authorization Test Routes
             * |--------------------------------------------------------------------------
             */

            Route::get('/role-test', function () {
                return response()->json([
                    'message' => 'Admin role authorized',
                ]);
            })->name('role-test');

            Route::get('/access-test', function () {
                return response()->json([
                    'message' => 'Authorized',
                ]);
            })
                ->middleware('can:users.manage')
                ->name('access-test');

            /**
             * |--------------------------------------------------------------------------
             * | Settings JSON Endpoints
             * |--------------------------------------------------------------------------
             */

            Route::prefix('settings')
                ->name('settings.')
                ->group(function () {

                    Route::get(
                        '/',
                        [SettingController::class, 'index']
                    )
                        ->middleware('permission:settings.view')
                        ->name('index');

                    Route::post(
                        '/{group}/{key}',
                        [SettingController::class, 'store']
                    )
                        ->middleware('permission:settings.manage')
                        ->name('store');

                    Route::get(
                        '/{group}/{key}',
                        [SettingController::class, 'show']
                    )
                        ->middleware('permission:settings.view')
                        ->name('show');

                    Route::match(
                        ['put', 'patch'],
                        '/{group}/{key}',
                        [SettingController::class, 'update']
                    )
                        ->middleware('permission:settings.manage')
                        ->name('update');

                    Route::delete(
                        '/{group}/{key}',
                        [SettingController::class, 'destroy']
                    )
                        ->middleware('permission:settings.manage')
                        ->name('destroy');
                });

            /**
             * Master Data - Countries
             */
            Route::prefix('master-data/countries')
                ->name('master-data.countries.')
                ->group(function () {

                    Route::get(
                        '/',
                        [CountryController::class, 'index']
                    )
                        ->middleware('permission:master-data.view')
                        ->name('index');

                    Route::post(
                        '/',
                        [CountryController::class, 'store']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('store');

                    Route::get(
                        '/{country}',
                        [CountryController::class, 'show']
                    )
                        ->middleware('permission:master-data.view')
                        ->name('show');

                    Route::match(
                        ['put', 'patch'],
                        '/{country}',
                        [CountryController::class, 'update']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('update');

                    Route::delete(
                        '/{country}',
                        [CountryController::class, 'destroy']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('destroy');
                });

            /**
             * Master Data - Cities
             */
            Route::prefix('master-data/cities')
                ->name('master-data.cities.')
                ->group(function () {

                    Route::get(
                        '/',
                        [CityController::class, 'index']
                    )
                        ->middleware('permission:master-data.view')
                        ->name('index');

                    Route::post(
                        '/',
                        [CityController::class, 'store']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('store');

                    Route::get(
                        '/{city}',
                        [CityController::class, 'show']
                    )
                        ->middleware('permission:master-data.view')
                        ->name('show');

                    Route::match(
                        ['put', 'patch'],
                        '/{city}',
                        [CityController::class, 'update']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('update');

                    Route::delete(
                        '/{city}',
                        [CityController::class, 'destroy']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('destroy');
                });
        });
});

/**
 * |--------------------------------------------------------------------------
 * | Settings Admin UI
 * |--------------------------------------------------------------------------
 */

Route::get(
    '/admin/settings/manage',
    SettingPageController::class
)
    ->middleware([
        'auth',
        'verified',
        'role:admin|super-admin',
        'permission:settings.view',
    ])
    ->name('admin.settings.manage');

Route::post('/flights/bookings/drafts', [FlightBookingDraftController::class, 'store'])
    ->middleware(['auth', 'verified', 'permission:flights.book'])
    ->name('flights.bookings.drafts.store');

Route::post('/flights/bookings/drafts/review', [FlightBookingDraftReviewController::class, 'store'])
    ->middleware(['auth', 'verified', 'permission:flights.book'])
    ->name('flights.bookings.drafts.review');

Route::post(
    '/flights/bookings/confirmation-intents',
    [
        \App\Http\Controllers\Flight\FlightBookingConfirmationIntentController::class,
        'store',
    ],
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name(
        'flights.bookings.confirmation-intents.store'
    );

Route::get(
    '/flights/bookings/orders/attempts/{attemptReference}',
    [
        \App\Http\Controllers\Flight\FlightOrderAttemptStatusController::class,
        'show',
    ],
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name(
        'flights.bookings.orders.attempts.show',
    );
Route::post(
    '/flights/bookings/orders/execute',
    [
        \App\Http\Controllers\Flight\FlightOrderExecutionController::class,
        'store',
    ],
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name(
        'flights.bookings.orders.execute'
    );
