<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\DestinationController;

Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::post('/bookings', [BookingController::class, 'store']);

Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{id}', [DestinationController::class, 'show']);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('/destinations', [DestinationController::class, 'store']);
    Route::put('/destinations/{id}', [DestinationController::class, 'update']);
    Route::delete('/destinations/{id}', [DestinationController::class, 'destroy']);
    Route::middleware(['auth:sanctum', 'admin'])->post(
    '/logout',
    [AdminAuthController::class, 'logout']);

Route::middleware(['auth:sanctum', 'admin'])->get(
    '/me',
    [AdminAuthController::class, 'me']);

});
