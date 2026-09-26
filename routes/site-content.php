<?php

use App\Http\Controllers\Admin\WebsiteContentController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)
    ->name('sitemap');

Route::prefix('admin/website')
    ->name('admin.website.')
    ->middleware([
        'auth',
        'verified',
        'role:admin|super-admin',
    ])
    ->group(function (): void {
        Route::middleware('permission:content.view')->group(function (): void {
            Route::get('/images', [WebsiteContentController::class, 'images'])
                ->name('images');
            Route::get('/destinations', [WebsiteContentController::class, 'destinations'])
                ->name('destinations');
            Route::get('/seo', [WebsiteContentController::class, 'seo'])
                ->name('seo');
            Route::get('/tracking', [WebsiteContentController::class, 'tracking'])
                ->name('tracking');
            Route::get('/contact', [WebsiteContentController::class, 'contact'])
                ->name('contact');
        });

        Route::middleware('permission:content.manage')->group(function (): void {
            Route::post('/images/{key}', [WebsiteContentController::class, 'updateImage'])
                ->where('key', '[a-z0-9_.\-]+')
                ->name('images.update');
            Route::delete('/images/{key}', [WebsiteContentController::class, 'resetImage'])
                ->where('key', '[a-z0-9_.\-]+')
                ->name('images.reset');
            Route::put('/destinations', [WebsiteContentController::class, 'updateDestinations'])
                ->name('destinations.update');
            Route::put('/seo', [WebsiteContentController::class, 'updateSeo'])
                ->name('seo.update');
            Route::put('/tracking', [WebsiteContentController::class, 'updateTracking'])
                ->name('tracking.update');
            Route::put('/contact', [WebsiteContentController::class, 'updateContact'])
                ->name('contact.update');
        });

        // Custom code runs on every public page, so only super admins may change it.
        Route::put('/custom-code', [WebsiteContentController::class, 'updateCustomCode'])
            ->middleware(['role:super-admin', 'permission:content.manage'])
            ->name('custom-code.update');
    });

Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /account',
        'Disallow: /bookings',
        'Disallow: /dashboard',
        '',
        'Sitemap: '.route('sitemap'),
    ];

    return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');
