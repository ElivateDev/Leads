<?php

use App\Http\Controllers\Api\LeadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Simple auth check for API
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('api.auth');

// API endpoints with token authentication
Route::middleware(['api.auth'])->group(function () {
    // Leads endpoints for reporting
    Route::get('/leads', [LeadController::class, 'index']);
    Route::get('/leads/stats', [LeadController::class, 'stats']);
    Route::get('/leads/{lead}', [LeadController::class, 'show']);
    
    // Clients endpoint (admin only)
    Route::get('/clients', [LeadController::class, 'clients']);
});
