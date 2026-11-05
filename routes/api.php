
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Api\SiteSettingController;

// Admin login
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Public booking
Route::post('/bookings', [BookingController::class, 'store']);

// Public destinations
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{slug}', [DestinationController::class, 'show']);

// Public tours
Route::get('/tours', [TourController::class, 'index']);
Route::get('/tours/{slug}', [TourController::class, 'show']);

// Public inquiries
Route::post('/inquiries', [InquiryController::class, 'store']);

// Public reviews
Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);

Route::get('/site-settings', [SiteSettingController::class, 'show']);

// Protected admin routes
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {

    Route::put('/site-settings', [SiteSettingController::class, 'update']);

        // Destinations management
        Route::get('/destinations', [DestinationController::class, 'adminIndex']);
        Route::get('/destinations/{id}', [DestinationController::class, 'adminShow']);
        Route::post('/destinations', [DestinationController::class, 'store']);
        Route::put('/destinations/{id}', [DestinationController::class, 'update']);
        Route::patch('/destinations/{id}/toggle-publish', [DestinationController::class, 'togglePublish']);
        Route::delete('/destinations/{id}', [DestinationController::class, 'destroy']);

        // Tours management
        Route::get('/tours', [TourController::class, 'adminIndex']);
        Route::get('/tours/{id}', [TourController::class, 'adminShow']);
        Route::post('/tours', [TourController::class, 'store']);
        Route::put('/tours/{id}', [TourController::class, 'update']);
        Route::patch('/tours/{id}/toggle-publish', [TourController::class, 'togglePublish']);
        Route::delete('/tours/{id}', [TourController::class, 'destroy']);

        // Inquiries management
        Route::get('/inquiries', [InquiryController::class, 'adminIndex']);
        Route::patch('/inquiries/{inquiry}/status', [InquiryController::class, 'updateStatus']);


        // Bookings management
Route::get('/bookings', [BookingController::class, 'adminIndex']);
Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);

        // Reviews management
        Route::get('/reviews', [ReviewController::class, 'adminIndex']);
        Route::patch('/reviews/{id}/approval', [ReviewController::class, 'updateApproval']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

        // Admin authentication
Route::post('/logout', [AdminAuthController::class, 'logout']);
Route::get('/me', [AdminAuthController::class, 'me']);
Route::patch('/change-password', [AdminAuthController::class, 'changePassword']);
Route::patch('/profile', [AdminAuthController::class, 'updateProfile']);
    });
