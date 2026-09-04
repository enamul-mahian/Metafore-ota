<?php

use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SettingPageController;
use App\Http\Controllers\Flight\FlightBookingConfirmationIntentController;
use App\Http\Controllers\Flight\FlightBookingDraftController;
use App\Http\Controllers\Flight\FlightBookingDraftReviewController;
use App\Http\Controllers\Flight\FlightOfferSelectionController;
use App\Http\Controllers\Flight\FlightOrderAttemptStatusController;
use App\Http\Controllers\Flight\FlightOrderConfirmationController;
use App\Http\Controllers\Flight\FlightOrderExecutionController;
use App\Http\Controllers\Flight\FlightOrderPaymentAttemptStatusController;
use App\Http\Controllers\Flight\FlightOrderPaymentExecutionController;
use App\Http\Controllers\Flight\FlightOrderPaymentReadinessController;
use App\Http\Controllers\Flight\FlightOrderPaymentReconciliationController;
use App\Http\Controllers\Flight\FlightOrderReconciliationController;
use App\Http\Controllers\Flight\FlightSearchController;
use App\Http\Controllers\Flight\FlightTravelerValidationController;
use App\Http\Controllers\FlightBookingController;
use App\Http\Controllers\Hotel\HotelController;
use App\Http\Controllers\Hotel\HotelSearchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::view('/about', 'public.about')
    ->name('about');

Route::view('/support', 'public.support')
    ->name('support');

Route::view('/terms', 'public.terms')
    ->name('terms');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::view('/account', 'account.overview')
        ->name('account.overview');

    Route::view('/flights', 'flights.search')
        ->middleware('permission:flights.search')
        ->name('flights.index');

    Route::get('/hotels', HotelController::class)
        ->middleware('permission:hotels.search')
        ->name('hotels.index');

    Route::post('/hotels/search', HotelSearchController::class)
        ->middleware('permission:hotels.search')
        ->name('hotels.search');

    Route::get('/bookings', [FlightBookingController::class, 'index'])
        ->middleware('permission:flights.book')
        ->name('bookings.index');

    Route::get('/bookings/{booking}', [FlightBookingController::class, 'show'])
        ->middleware('permission:flights.book')
        ->name('bookings.show');

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
        FlightBookingConfirmationIntentController::class,
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
        FlightOrderAttemptStatusController::class,
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
    '/flights/bookings/orders/attempts/{attemptReference}/reconcile',
    [
        FlightOrderReconciliationController::class,
        'store',
    ],
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name(
        'flights.bookings.orders.attempts.reconcile',
    );
Route::post(
    '/flights/bookings/orders/execute',
    [
        FlightOrderExecutionController::class,
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
Route::get(
    '/flights/bookings/orders/attempts/{attemptReference}/payment-readiness',
    FlightOrderPaymentReadinessController::class,
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name('flights.bookings.orders.attempts.payment-readiness.show');
Route::post(
    '/flights/bookings/orders/attempts/{attemptReference}/payments',
    FlightOrderPaymentExecutionController::class,
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name('flights.bookings.orders.attempts.payments.store');

Route::get(
    '/flights/bookings/orders/payments/attempts/{attemptReference}',
    FlightOrderPaymentAttemptStatusController::class,
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name('flights.bookings.orders.payments.attempts.show');

Route::post(
    '/flights/bookings/orders/payments/attempts/{attemptReference}/reconcile',
    FlightOrderPaymentReconciliationController::class,
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name('flights.bookings.orders.payments.attempts.reconcile');

Route::get(
    '/flights/bookings/orders/attempts/{attemptReference}/confirmation',
    FlightOrderConfirmationController::class,
)
    ->middleware([
        'auth',
        'verified',
        'permission:flights.book',
    ])
    ->name('flights.bookings.orders.attempts.confirmation.show');
