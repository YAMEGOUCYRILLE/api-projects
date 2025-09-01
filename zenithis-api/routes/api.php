<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // User routes
    Route::apiResource('users', UserController::class);
    
    // Trip routes
    Route::apiResource('trips', TripController::class);
    Route::get('my-trips', [TripController::class, 'myTrips']);
});