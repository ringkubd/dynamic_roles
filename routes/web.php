<?php

use Illuminate\Support\Facades\Route;
use Anwar\DynamicRoles\Http\Controllers\WebController;

/*
|--------------------------------------------------------------------------
| Dynamic Roles Web Routes
|--------------------------------------------------------------------------
|
| Here are the web routes for the Dynamic Roles package. These routes
| provide a web interface for managing roles, permissions, URLs, and menus
| for applications that don't use API-only architecture.
|
*/

Route::middleware(['web', 'auth'])->prefix('dynamic-roles')->name('dynamic-roles.')->group(function () {
    
    // Dashboard
    Route::get('/', [WebController::class, 'dashboard'])->name('dashboard');
    
    // Roles management
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [WebController::class, 'roles'])->name('index');
        Route::get('/{role}', [WebController::class, 'showRole'])->name('show');
        Route::get('/{role}/edit', [WebController::class, 'editRole'])->name('edit');
        Route::put('/{role}', [WebController::class, 'updateRole'])->name('update');
    });
    
    // Permissions management
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [WebController::class, 'permissions'])->name('index');
    });
    
    // URLs management
    Route::prefix('urls')->name('urls.')->group(function () {
        Route::get('/', [WebController::class, 'urls'])->name('index');
        Route::get('/{id}', [WebController::class, 'showUrl'])->name('show');
        Route::get('/{id}/edit', [WebController::class, 'editUrl'])->name('edit');
        Route::put('/{id}', [WebController::class, 'updateUrl'])->name('update');
    });
    
    // Menus management
    Route::prefix('menus')->name('menus.')->group(function () {
        Route::get('/', [WebController::class, 'menus'])->name('index');
        Route::get('/{id}', [WebController::class, 'showMenu'])->name('show');
    });
    
    // Cache management
    Route::prefix('cache')->name('cache.')->group(function () {
        Route::get('/', [WebController::class, 'cache'])->name('index');
        Route::post('/clear', [WebController::class, 'clearCache'])->name('clear');
    });
    
});
