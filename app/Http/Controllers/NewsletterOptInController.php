<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterOptInController extends Controller
{
    public function verify(string $token)
    {
        $subscriber = NewsletterSubscriber::where('verify_token', $token)->first();

        if (!$subscriber) {
            return response('Invalid or expired confirmation link.', 404);
        }

        if ($subscriber->status === 'active') {
            return view('newsletter.confirmed', ['message' => 'Your email is already confirmed. Thank you!']);
        }

        $subscriber->verify();

        \App\Models\AuditLog::log(
            null,
            'subscriber.verified',
            'NewsletterSubscriber',
            $subscriber->uuid,
            ['email' => $subscriber->email]
        );

        return view('newsletter.confirmed', ['message' => 'Your subscription has been confirmed. Welcome to the community!']);
    }

    public function preferences(string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (!$subscriber) {
            return response('Invalid or expired link.', 404);
        }

        return view('newsletter.preferences', ['subscriber' => $subscriber]);
    }

    public function updatePreferences(Request $request, string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (!$subscriber) {
            return response('Invalid or expired link.', 404);
        }

        $preferences = [
            'email_updates' => $request->boolean('email_updates'),
            'product_updates' => $request->boolean('product_updates'),
            'promotions' => $request->boolean('promotions'),
        ];

        $subscriber->update([
            'preferences' => $preferences,
            'status' => $request->boolean('email_updates') ? 'active' : 'unsubscribed',
            'unsubscribed_at' => $request->boolean('email_updates') ? null : now(),
        ]);

        \App\Models\AuditLog::log(
            null,
            'subscriber.preferences_updated',
            'NewsletterSubscriber',
            $subscriber->uuid,
            ['preferences' => $preferences]
        );

        return back()->with('status', 'Your preferences have been saved.');
    }
}
