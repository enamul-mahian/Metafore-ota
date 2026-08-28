<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin|super-admin')
        ->group(function () {
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
        });
});