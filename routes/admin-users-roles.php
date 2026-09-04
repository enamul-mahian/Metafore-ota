<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('can:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}', [UserController::class, 'show'])
                ->whereNumber('user')
                ->name('users.show');
        });

        Route::middleware('can:users.manage')->group(function () {
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])
                ->whereNumber('user')
                ->name('users.edit');
            Route::patch('/users/{user}', [UserController::class, 'update'])
                ->whereNumber('user')
                ->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->whereNumber('user')
                ->name('users.destroy');
        });

        Route::middleware('can:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/{role}', [RoleController::class, 'show'])
                ->whereNumber('role')
                ->name('roles.show');
        });

        Route::middleware(['role:super-admin', 'can:roles.manage'])->group(function () {
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
                ->whereNumber('role')
                ->name('roles.edit');
            Route::patch('/roles/{role}', [RoleController::class, 'update'])
                ->whereNumber('role')
                ->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
                ->whereNumber('role')
                ->name('roles.destroy');
        });
    });
