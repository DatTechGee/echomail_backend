<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch';

    protected $description = 'Dispatch one-time and recurring campaigns that are due';

    public function handle(): int
    {
        $now = now();

        $oneTime = Campaign::where('status', 'scheduled')
            ->whereNull('frequency')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->get();

        foreach ($oneTime as $campaign) {
            $this->dispatchCampaign($campaign, false);
        }

        $recurring = Campaign::where('status', 'scheduled')
            ->whereNotNull('frequency')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->get();

        foreach ($recurring as $campaign) {
            $this->dispatchCampaign($campaign, true);
        }

        $dispatched = $oneTime->count() + $recurring->count();

        if ($dispatched > 0) {
            $this->info("Dispatched {$dispatched} campaign(s).");
        }

        return 0;
    }

    private function dispatchCampaign(Campaign $campaign, bool $recurring): void
    {
        $emails = $campaign->recipient_emails ?: $campaign->getRecipientList();

        if (empty($emails)) {
            Log::warning("Campaign {$campaign->uuid} has no recipients to dispatch.");
            return;
        }

        if ($recurring) {
            $campaign->recipients()->delete();
        }

        $campaign->dispatchTo($emails);

        if ($recurring) {
            $campaign->update([
                'next_run_at' => $campaign->computeNextRunAt(),
                'sent_at' => null,
            ]);
        }

        Log::info("Campaign {$campaign->uuid} dispatched to " . count($emails) . " recipient(s).");
    }
}
