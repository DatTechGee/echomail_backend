<?php

use App\Http\Controllers\CampaignTrackingController;
use App\Http\Controllers\NewsletterOptInController;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/campaigns/{campaign}/open/{token}', [CampaignTrackingController::class, 'open'])
    ->name('campaign.track.open');

Route::get('/campaigns/{campaign}/click/{token}', [CampaignTrackingController::class, 'click'])
    ->name('campaign.track.click');

Route::get('/unsubscribe/{token}', function (string $token) {
    $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

    if ($subscriber) {
        $subscriber->unsubscribe();
    }

    return response('You have been unsubscribed from the newsletter.', 200);
});

Route::get('/verify/{token}', [NewsletterOptInController::class, 'verify'])
    ->name('newsletter.verify');

Route::get('/preferences/{token}', [NewsletterOptInController::class, 'preferences'])
    ->name('newsletter.preferences');

Route::post('/preferences/{token}', [NewsletterOptInController::class, 'updatePreferences'])
    ->name('newsletter.preferences.update');
