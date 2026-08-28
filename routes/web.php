<?php

use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin|super-admin')
        ->group(function () {
            /*
            |--------------------------------------------------------------------------
            | Authorization Test Routes
            |--------------------------------------------------------------------------
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

            /*
            |--------------------------------------------------------------------------
            | Settings
            |--------------------------------------------------------------------------
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
        });
});