<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CategoryPageController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\CurrencyPageController;
use App\Http\Controllers\Admin\FeatureControlController;
use App\Http\Controllers\Admin\MasterDataPageController;
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
use App\Http\Controllers\Tour\TourController;
use App\Http\Controllers\Tour\TourSearchController;
use App\Http\Controllers\Visa\VisaController;
use App\Http\Controllers\Visa\VisaRequirementController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::view('/about', 'public.about')
    ->middleware('feature:about')
    ->name('about');

Route::view('/support', 'public.support')
    ->middleware('feature:support')
    ->name('support');

Route::view('/terms', 'public.terms')
    ->name('terms');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::view('/dashboard', 'dashboard')
        ->middleware('feature:dashboard')
        ->name('dashboard');

    Route::view('/account', 'account.overview')
        ->middleware('feature:account')
        ->name('account.overview');

    Route::view('/flights', 'flights.search')
        ->middleware(['feature:flights', 'permission:flights.search'])
        ->name('flights.index');

    Route::get('/hotels', HotelController::class)
        ->middleware(['feature:hotels', 'permission:hotels.search'])
        ->name('hotels.index');

    Route::post('/hotels/search', HotelSearchController::class)
        ->middleware(['feature:hotels', 'permission:hotels.search'])
        ->name('hotels.search');

    Route::get('/tours', TourController::class)
        ->middleware(['feature:tours', 'permission:tours.search'])
        ->name('tours.index');

    Route::post('/tours/search', TourSearchController::class)
        ->middleware(['feature:tours', 'permission:tours.search'])
        ->name('tours.search');

    Route::get('/visa', VisaController::class)
        ->middleware(['feature:visa', 'permission:visa.view'])
        ->name('visa.index');

    Route::post('/visa/requirements', VisaRequirementController::class)
        ->middleware(['feature:visa', 'permission:visa.view'])
        ->name('visa.requirements');

    Route::get('/bookings', [FlightBookingController::class, 'index'])
        ->middleware(['feature:bookings', 'permission:flights.book'])
        ->name('bookings.index');

    Route::get('/bookings/{booking}', [FlightBookingController::class, 'show'])
        ->middleware(['feature:bookings', 'permission:flights.book'])
        ->name('bookings.show');

    Route::post(
        '/flights/search',
        FlightSearchController::class
    )
        ->middleware(['feature:flights', 'permission:flights.search'])
        ->name('flights.search');
    Route::post(
        '/flights/offers/select',
        FlightOfferSelectionController::class
    )
        ->middleware(['feature:flights', 'permission:flights.search'])
        ->name('flights.offers.select');
    Route::post(
        '/flights/travelers/validate',
        FlightTravelerValidationController::class
    )
        ->middleware(['feature:flights', 'permission:flights.search'])
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

            Route::prefix('features')
                ->name('features.')
                ->middleware('role:super-admin')
                ->group(function () {
                    Route::get(
                        '/',
                        [FeatureControlController::class, 'index']
                    )->name('index');

                    Route::patch(
                        '/{feature}',
                        [FeatureControlController::class, 'update']
                    )
                        ->where('feature', '[a-z][a-z0-9-]*')
                        ->name('update');
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
             * Master Data - Categories
             */
            Route::prefix('master-data/categories')
                ->name('master-data.categories.')
                ->group(function () {

                    Route::get(
                        '/',
                        [CategoryController::class, 'index']
                    )
                        ->middleware('permission:master-data.view')
                        ->name('index');

                    Route::post(
                        '/',
                        [CategoryController::class, 'store']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('store');

                    Route::get(
                        '/{category}',
                        [CategoryController::class, 'show']
                    )
                        ->middleware('permission:master-data.view')
                        ->name('show');

                    Route::match(
                        ['put', 'patch'],
                        '/{category}',
                        [CategoryController::class, 'update']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('update');

                    Route::delete(
                        '/{category}',
                        [CategoryController::class, 'destroy']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('destroy');
                });

            /**
             * Master Data - Currencies
             */
            Route::prefix('master-data/currencies')
                ->name('master-data.currencies.')
                ->group(function () {

                    Route::get(
                        '/',
                        [CurrencyController::class, 'index']
                    )
                        ->middleware('permission:master-data.view')
                        ->name('index');

                    Route::post(
                        '/',
                        [CurrencyController::class, 'store']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('store');

                    Route::get(
                        '/{currency}',
                        [CurrencyController::class, 'show']
                    )
                        ->middleware('permission:master-data.view')
                        ->name('show');

                    Route::match(
                        ['put', 'patch'],
                        '/{currency}',
                        [CurrencyController::class, 'update']
                    )
                        ->middleware('permission:master-data.manage')
                        ->name('update');

                    Route::delete(
                        '/{currency}',
                        [CurrencyController::class, 'destroy']
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
    ->middleware(['auth', 'verified', 'feature:flights,bookings', 'permission:flights.book'])
    ->name('flights.bookings.drafts.store');

Route::post('/flights/bookings/drafts/review', [FlightBookingDraftReviewController::class, 'store'])
    ->middleware(['auth', 'verified', 'feature:flights,bookings', 'permission:flights.book'])
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
        'feature:flights,bookings',
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
        'feature:flights,bookings',
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
        'feature:flights,bookings',
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
        'feature:flights,bookings',
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
        'feature:flights,bookings,payments',
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
        'feature:flights,bookings,payments',
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
        'feature:flights,bookings,payments',
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
        'feature:flights,bookings,payments',
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
        'feature:flights,bookings',
        'permission:flights.book',
    ])
    ->name('flights.bookings.orders.attempts.confirmation.show');

require __DIR__.'/admin-users-roles.php';
Route::get(
    '/admin/categories',
    CategoryPageController::class
)
    ->middleware([
        'auth',
        'verified',
        'role:admin|super-admin',
        'permission:master-data.view',
    ])
    ->name('admin.categories.manage');

Route::get(
    '/admin/currencies',
    CurrencyPageController::class
)
    ->middleware([
        'auth',
        'verified',
        'role:admin|super-admin',
        'permission:master-data.view',
    ])
    ->name('admin.currencies.manage');

Route::get(
    '/admin/master-data',
    MasterDataPageController::class
)
    ->middleware([
        'auth',
        'verified',
        'role:admin|super-admin',
        'permission:master-data.view',
    ])
    ->name('admin.master-data.manage');
