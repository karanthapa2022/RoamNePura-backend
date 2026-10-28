<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ReviewController;

Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::post('/bookings', [BookingController::class, 'store']);

Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{slug}', [DestinationController::class, 'show']);
Route::post('/inquiries', [InquiryController::class, 'store']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);


Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    Route::get('/destinations', [DestinationController::class, 'adminIndex']);
    Route::get('/destinations/{id}', [DestinationController::class, 'adminShow']);
    Route::post('/destinations', [DestinationController::class, 'store']);
    Route::put('/destinations/{id}', [DestinationController::class, 'update']);
    Route::patch('/destinations/{id}/toggle-publish', [DestinationController::class, 'togglePublish']);
    Route::delete('/destinations/{id}', [DestinationController::class, 'destroy']);
    Route::get('/inquiries', [InquiryController::class, 'adminIndex']);
    Route::get('/bookings', [BookingController::class, 'adminIndex']);
    Route::patch('/inquiries/{inquiry}/status', [InquiryController::class, 'updateStatus']);
    Route::get('/reviews', [ReviewController::class, 'adminIndex']);
Route::patch('/reviews/{id}/approval', [ReviewController::class, 'updateApproval']);
Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);



    Route::post('/logout',[AdminAuthController::class, 'logout']);

    Route::get('/me',[AdminAuthController::class, 'me']);

});
