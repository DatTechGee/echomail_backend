<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutomationController;
use App\Http\Controllers\Api\CampaignAbTestController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\WebhookController;

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
        Route::post('/newsletter/subscribe', 'subscribe')->middleware('throttle:10,1');
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

    Route::controller(ImageController::class)->group(function () {
            Route::post('/images/upload', 'upload');
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
            Route::post('/{uuid}/retry', 'retry');
            Route::post('/{uuid}/duplicate', 'duplicate');
            Route::post('/{uuid}/test-send', 'testSend');
            Route::get('/{uuid}/preview', 'preview');
            Route::get('/{uuid}/recipients', 'recipients');
            Route::get('/{uuid}/export', 'exportRecipients');
            Route::post('/{uuid}/mark-bounced', 'markBounced');
            Route::delete('/{uuid}', 'destroy');
        });

        // A/B Testing routes
        Route::controller(CampaignAbTestController::class)->prefix('campaigns/{campaignUuid}/ab-tests')->group(function () {
            Route::get('/', 'listForCampaign');
            Route::post('/', 'store');
            Route::get('/{abTestId}', 'show');
            Route::post('/{abTestId}/start', 'start');
            Route::post('/{abTestId}/select-winner', 'selectWinner');
            Route::delete('/{abTestId}', 'destroy');
        });

        // Automation routes
        Route::controller(AutomationController::class)->prefix('automations')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/stats', 'stats');
            Route::get('/{uuid}', 'show');
            Route::put('/{uuid}', 'update');
            Route::delete('/{uuid}', 'destroy');
            Route::post('/{uuid}/activate', 'activate');
            Route::post('/{uuid}/pause', 'pause');
            Route::post('/{uuid}/enroll', 'enroll');
            Route::get('/{uuid}/enrollments', 'enrollments');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(TemplateController::class)->prefix('templates')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{uuid}', 'show');
            Route::put('/{uuid}', 'update');
            Route::delete('/{uuid}', 'destroy');
        });
    });

    // Payment notification
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/payment/send', [PaymentController::class, 'send']);
    });

    // Audit logs (Admin only)
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(AuditLogController::class)->prefix('audit-logs')->group(function () {
            Route::get('/', 'index');
            Route::delete('/', 'destroy');
        });
    });

    // Webhooks
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(WebhookController::class)->prefix('webhooks')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::get('/{id}/deliveries', 'deliveries');
            Route::post('/{id}/test', 'test');
        });
    });
});
