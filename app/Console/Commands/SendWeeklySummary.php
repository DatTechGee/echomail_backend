<?php

namespace App\Console\Commands;

use App\Mail\WeeklySummaryMail;
use App\Models\Campaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklySummary extends Command
{
    protected $signature = 'summary:send-weekly';

    protected $description = 'Send a weekly activity summary email to all users';

    public function handle(): int
    {
        $start = now()->subWeek();
        $end = now();

        $users = User::all();

        if ($users->isEmpty()) {
            $this->info('No users to send summaries to.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($users as $user) {
            $campaigns = Campaign::byUser($user->id)
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->get();

            $stats = [
                'campaigns_created' => $campaigns->count(),
                'emails_sent' => (int) $campaigns->sum('total_sent'),
                'opens' => (int) $campaigns->sum('opens'),
                'clicks' => (int) $campaigns->sum('clicks'),
                'failed' => (int) $campaigns->sum('total_failed'),
                'open_rate' => 0,
                'click_rate' => 0,
            ];

            $emailsSent = $stats['emails_sent'];
            if ($emailsSent > 0) {
                $stats['open_rate'] = round(($stats['opens'] / $emailsSent) * 100, 2);
                $stats['click_rate'] = round(($stats['clicks'] / $emailsSent) * 100, 2);
            }

            $stats['subscribers'] = NewsletterSubscriber::count();
            $stats['new_subscribers'] = NewsletterSubscriber::where('subscribed_at', '>=', $start)->count();

            Mail::to($user->email)->send(new WeeklySummaryMail($user, $start, $end, $stats));
            $sent++;
        }

        $this->info("Weekly summaries sent to {$sent} user(s).");
        return self::SUCCESS;
    }
}
