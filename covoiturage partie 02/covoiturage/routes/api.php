<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\BookingController;

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes pour les trajets (lecture publique)
Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/search', [TripController::class, 'search']);
Route::get('/trips/{trip}', [TripController::class, 'show']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Trajets
    Route::post('/trips', [TripController::class, 'store']);
    
    // Réservations
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // Réservations avec logging
    Route::middleware(['auth:sanctum', 'booking.logger'])->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
});
});