<?php

use App\Http\Controllers\ImpersonationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('homepage');
});

// Impersonation routes
Route::middleware(['auth'])->group(function () {
    Route::get('/impersonate-landing', [ImpersonationController::class, 'landing'])->name('impersonate-landing');
    Route::post('/stop-impersonating', [ImpersonationController::class, 'stopImpersonating'])->name('stop-impersonating');

    // Admin-only impersonation routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/impersonate-form/{user}', [ImpersonationController::class, 'showForm'])->name('impersonate-form');
        Route::post('/impersonate/{user}', [ImpersonationController::class, 'impersonate'])->name('impersonate');
    });
});
