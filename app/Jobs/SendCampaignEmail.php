<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 60;

    public Campaign $campaign;

    public string $email;

    public string $token;

    public function __construct(Campaign $campaign, string $email, string $token)
    {
        $this->campaign = $campaign;
        $this->email = $email;
        $this->token = $token;
    }

    public function handle(): void
    {
        $recipient = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->where('token', $this->token)
            ->first();

        if (!$recipient || $recipient->status === 'sent') {
            return;
        }

        $personalization = $this->personalization();

        Mail::to($this->email)->send(
            new CampaignMail($this->campaign, $this->email, $this->token, $personalization)
        );

        $recipient->update(['status' => 'sent']);
        $this->campaign->syncStats();

        Log::info("Campaign {$this->campaign->uuid} sent to: {$this->email}");
    }

    public function failed(Throwable $e): void
    {
        $recipient = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->where('token', $this->token)
            ->first();

        if ($recipient) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 500),
            ]);
            $this->campaign->syncStats();
        }

        Log::error("Campaign {$this->campaign->uuid} failed for {$this->email}: " . $e->getMessage());
    }

    private function personalization(): array
    {
        $name = null;

        $contact = Contact::byUser($this->campaign->user_id)->where('email', $this->email)->first();
        if ($contact && $contact->name) {
            $name = $contact->name;
        }

        if (!$name) {
            $subscriber = NewsletterSubscriber::where('email', $this->email)->first();
            if ($subscriber && $subscriber->name) {
                $name = $subscriber->name;
            }
        }

        $first = null;
        $last = null;

        if ($name) {
            $parts = explode(' ', trim($name), 2);
            $first = $parts[0] ?? null;
            $last = $parts[1] ?? null;
        }

        return [
            'email' => $this->email,
            'first_name' => $first ?? '',
            'last_name' => $last ?? '',
            'full_name' => $name ?? $this->email,
        ];
    }
}
