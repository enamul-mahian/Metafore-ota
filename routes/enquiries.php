<?php

use App\Http\Controllers\Admin\EnquiryController as AdminEnquiryController;
use App\Http\Controllers\Admin\NewsletterSubscriberController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\NewsletterSubscriptionController;
use Illuminate\Support\Facades\Route;

/**
 * |--------------------------------------------------------------------------
 * | Public enquiries, contact and newsletter (guests allowed)
 * |--------------------------------------------------------------------------
 */
$enquiryServices = array_keys(config('enquiries.services', []));

Route::get('/request/{service}', [EnquiryController::class, 'create'])
    ->whereIn('service', $enquiryServices)
    ->name('enquiries.create');

Route::post('/request/{service}', [EnquiryController::class, 'store'])
    ->whereIn('service', $enquiryServices)
    ->middleware('throttle:10,1')
    ->name('enquiries.store');

Route::get('/contact', [ContactController::class, 'show'])
    ->name('contact');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');

Route::post('/newsletter', [NewsletterSubscriptionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('newsletter.store');

Route::view('/holiday-packages', 'packages.index')
    ->name('packages.index');

/**
 * |--------------------------------------------------------------------------
 * | Admin: enquiries and newsletter subscribers
 * |--------------------------------------------------------------------------
 */
Route::middleware(['auth', 'verified', 'role:admin|super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/enquiries', [AdminEnquiryController::class, 'index'])
            ->middleware('permission:enquiries.view')
            ->name('enquiries.index');

        Route::get('/enquiries/{enquiry}', [AdminEnquiryController::class, 'show'])
            ->whereNumber('enquiry')
            ->middleware('permission:enquiries.view')
            ->name('enquiries.show');

        Route::patch('/enquiries/{enquiry}', [AdminEnquiryController::class, 'update'])
            ->whereNumber('enquiry')
            ->middleware('permission:enquiries.manage')
            ->name('enquiries.update');

        Route::get('/newsletter', [NewsletterSubscriberController::class, 'index'])
            ->middleware('permission:enquiries.view')
            ->name('newsletter.index');

        Route::get('/newsletter/export', [NewsletterSubscriberController::class, 'export'])
            ->middleware('permission:enquiries.manage')
            ->name('newsletter.export');
    });
