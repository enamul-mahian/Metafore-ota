<?php

use App\Http\Controllers\Admin\WorkVisaApplicationController as AdminWorkVisaApplicationController;
use App\Http\Controllers\Admin\WorkVisaDestinationController;
use App\Http\Controllers\WorkVisa\WorkVisaApplicationController;
use App\Http\Controllers\WorkVisa\WorkVisaController;
use App\Http\Controllers\WorkVisa\WorkVisaDocumentController;
use Illuminate\Support\Facades\Route;

/**
 * |--------------------------------------------------------------------------
 * | Work Visa Processing (public landing)
 * |--------------------------------------------------------------------------
 */
Route::get('/work-visa', WorkVisaController::class)
    ->middleware('feature:work-visa')
    ->name('work-visa.index');

/**
 * |--------------------------------------------------------------------------
 * | Work Visa application (customers)
 * |--------------------------------------------------------------------------
 */
Route::middleware(['auth', 'verified', 'feature:work-visa'])->group(function () {
    Route::get('/work-visa/apply', [WorkVisaApplicationController::class, 'create'])
        ->middleware('can:work-visa.apply')
        ->name('work-visa.apply');

    Route::post('/work-visa/apply', [WorkVisaApplicationController::class, 'store'])
        ->middleware(['can:work-visa.apply', 'throttle:10,1'])
        ->name('work-visa.store');

    Route::get('/account/work-visa', [WorkVisaApplicationController::class, 'index'])
        ->name('work-visa.applications.index');

    Route::get('/account/work-visa/{application}', [WorkVisaApplicationController::class, 'show'])
        ->whereNumber('application')
        ->name('work-visa.applications.show');

    Route::post('/account/work-visa/{application}/documents', [WorkVisaDocumentController::class, 'store'])
        ->whereNumber('application')
        ->middleware('throttle:20,1')
        ->name('work-visa.applications.documents.store');
});

/**
 * Private document download: the applicant or admin staff with work-visa.view only.
 */
Route::get('/work-visa/documents/{document}/download', [WorkVisaDocumentController::class, 'download'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('document')
    ->name('work-visa.documents.download');

/**
 * |--------------------------------------------------------------------------
 * | Work Visa administration
 * |--------------------------------------------------------------------------
 */
Route::middleware(['auth', 'verified', 'role:admin|super-admin'])
    ->prefix('admin/work-visa')
    ->name('admin.work-visa.')
    ->group(function () {
        Route::middleware('permission:work-visa.view')->group(function () {
            Route::get('/applications', [AdminWorkVisaApplicationController::class, 'index'])
                ->name('applications.index');
            Route::get('/applications/{application}', [AdminWorkVisaApplicationController::class, 'show'])
                ->whereNumber('application')
                ->name('applications.show');
            Route::get('/destinations', [WorkVisaDestinationController::class, 'index'])
                ->name('destinations.index');
        });

        Route::middleware('permission:work-visa.manage')->group(function () {
            Route::patch('/applications/{application}/status', [AdminWorkVisaApplicationController::class, 'updateStatus'])
                ->whereNumber('application')
                ->name('applications.status');
            Route::get('/destinations/create', [WorkVisaDestinationController::class, 'create'])
                ->name('destinations.create');
            Route::post('/destinations', [WorkVisaDestinationController::class, 'store'])
                ->name('destinations.store');
            Route::get('/destinations/{destination}/edit', [WorkVisaDestinationController::class, 'edit'])
                ->whereNumber('destination')
                ->name('destinations.edit');
            Route::patch('/destinations/{destination}', [WorkVisaDestinationController::class, 'update'])
                ->whereNumber('destination')
                ->name('destinations.update');
            Route::delete('/destinations/{destination}', [WorkVisaDestinationController::class, 'destroy'])
                ->whereNumber('destination')
                ->name('destinations.destroy');
        });
    });
