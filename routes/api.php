<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ContactController;

Route::prefix('/v1')->group(function () {
    // Auth routes
    Route::controller(AuthController::class)->group(function () {
        // Public routes
        Route::post('/login', 'login');
        Route::post('/verify-two-factor', 'verifyTwoFactor');
        Route::post('/forgot-password', 'forgotPassword');
        Route::post('/reset-password', 'resetPassword');
        Route::post('/refresh-token', 'refreshToken');
        Route::post('/resend-otp', 'resendOtp');

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', 'logout');
            Route::get('/profile', 'getUserProfile');
            Route::put('/profile', 'updateUserProfile');
            Route::post('/change-password', 'changePassword');
            Route::post('/toggle-two-factor', 'toggleTwoFactor');
        });
    });

    // Public newsletter routes
    Route::controller(NewsletterController::class)->group(function () {
        Route::post('/newsletter/subscribe', 'subscribe');
        Route::get('/newsletter/unsubscribe/{token}', 'unsubscribe');
    });

    // Protected newsletter routes (Admin only)
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(NewsletterController::class)->prefix('newsletter')->group(function () {
            Route::get('/subscribers', 'index');
            Route::get('/subscribers/{uuid}', 'show');
            Route::delete('/subscribers/{uuid}', 'destroy');
            Route::delete('/subscribers', 'bulkDelete');
            Route::get('/export', 'export');
            Route::get('/stats', 'stats');
        });
    });

     // Protected contact routes (Admin only)
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(ContactController::class)->prefix('contacts')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/groups', 'getGroups');
            Route::post('/groups', 'createGroup');
            Route::get('/stats', 'getStats');
            Route::post('/import-csv', 'importCsv');
            Route::get('/export', 'export');
            Route::delete('/bulk-delete', 'bulkDelete');
            Route::get('/{uuid}', 'show');
            Route::put('/{uuid}', 'update');
            Route::delete('/{uuid}', 'destroy');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(CampaignController::class)->prefix('campaigns')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/stats', 'getStats');
            Route::post('/recipient-preview', 'getRecipientPreview');
            Route::get('/{uuid}', 'show');
            Route::post('/{uuid}/send', 'send');
            Route::post('/{uuid}/duplicate', 'duplicate');
            Route::delete('/{uuid}', 'destroy');
        });
    });
});
