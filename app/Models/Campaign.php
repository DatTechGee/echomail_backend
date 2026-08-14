<?php

namespace App\Models;

use App\Helper\BlockNoteParser;
use App\Jobs\SendCampaignEmail;
use App\Models\Contact;
use App\Models\NewsletterSubscriber;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'subject',
        'content',
        'html_content',
        'status',
        'recipient_config',
        'recipient_emails',
        'total_recipients',
        'total_sent',
        'total_failed',
        'opens',
        'clicks',
        'open_rate',
        'click_rate',
        'sent_at',
        'error_message',
        'created_by',
        'scheduled_at',
        'frequency',
        'next_run_at',
    ];

    protected $casts = [
        'recipient_config' => 'array',
        'recipient_emails' => 'array',
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (empty($campaign->uuid)) {
                $campaign->uuid = Str::uuid();
            }

            // Parse BlockNote content to HTML if content is provided
            if ($campaign->content && !$campaign->html_content) {
                $campaign->html_content = BlockNoteParser::parse($campaign->content);
            }
        });

        static::updating(function ($campaign) {
            // Re-parse content if it changed
            if ($campaign->isDirty('content')) {
                $campaign->html_content = BlockNoteParser::parse($campaign->content);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get recipient list based on configuration
     */
    public function getRecipientList(): array
    {
        $config = $this->recipient_config;
        $recipients = [];

        switch ($config['type']) {
            case 'all':
                // Get all contacts
                $contacts = Contact::byUser($this->user_id)->get();
                foreach ($contacts as $contact) {
                    $recipients[] = $contact->email;
                }

                // Get all newsletter subscribers (avoid duplicates)
                $newsletters = NewsletterSubscriber::whereIn('status', ['active', 'pending'])->get();
                foreach ($newsletters as $subscriber) {
                    if (!in_array($subscriber->email, $recipients)) {
                        $recipients[] = $subscriber->email;
                    }
                }
                break;

            case 'newsletter':
                $newsletters = NewsletterSubscriber::whereIn('status', ['active', 'pending'])->get();
                foreach ($newsletters as $subscriber) {
                    $recipients[] = $subscriber->email;
                }
                break;

            case 'groups':
                if (!empty($config['groups'])) {
                    $contacts = Contact::byUser($this->user_id)->get();
                    foreach ($contacts as $contact) {
                        $contactGroups = $contact->groups ?: [];
                        if (array_intersect($contactGroups, $config['groups'])) {
                            $recipients[] = $contact->email;
                        }
                    }
                }
                break;

            case 'manual':
                if (!empty($config['manual_emails'])) {
                    $recipients = $config['manual_emails'];
                }
                break;
        }

        // Remove duplicates and empty emails
        return array_unique(array_filter($recipients));
    }

    /**
     * Mark campaign as sent
     */
    public function markAsSent(int $successCount, int $failureCount): void
    {
        $totalSent = $successCount + $failureCount;

        $this->update([
            'status' => $failureCount === $totalSent ? 'failed' : 'sent',
            'total_sent' => $successCount,
            'total_failed' => $failureCount,
            'sent_at' => now(),
        ]);
    }

    /**
     * Register recipient rows and dispatch queued sending for each email.
     */
    public function dispatchTo(array $emails): void
    {
        $existing = $this->recipients()->pluck('email')->all();

        foreach (array_diff($emails, $existing) as $email) {
            $token = Str::random(32);
            $this->recipients()->create([
                'email' => $email,
                'token' => $token,
                'status' => 'pending',
            ]);
            SendCampaignEmail::dispatch($this, $email, $token);
        }

        if ($this->status !== 'scheduled') {
            $this->update(['status' => 'sending', 'error_message' => null]);
        }

        $this->dispatchWebhook('campaign.started');
    }

    /**
     * Recompute aggregate metrics from the recipient rows.
     */
    public function syncStats(): void
    {
        $totalSent = $this->recipients()->sent()->count();
        $totalFailed = $this->recipients()->failed()->count();
        $pending = $this->recipients()->pending()->count();
        $opens = $this->recipients()->opened()->count();
        $clicks = $this->recipients()->clicked()->count();

        $this->update([
            'total_sent' => $totalSent,
            'total_failed' => $totalFailed,
            'opens' => $opens,
            'clicks' => $clicks,
            'open_rate' => $totalSent > 0 ? round(($opens / $totalSent) * 100, 2) : 0,
            'click_rate' => $totalSent > 0 ? round(($clicks / $totalSent) * 100, 2) : 0,
        ]);

        if ($pending === 0 && in_array($this->status, ['sending', 'scheduled'])) {
            if ($this->status === 'scheduled' && $this->frequency) {
                return;
            }

            $this->update([
                'status' => $totalFailed === $totalSent + $totalFailed ? 'failed' : 'sent',
                'sent_at' => now(),
            ]);

            $this->dispatchWebhook(
                $totalFailed === $totalSent + $totalFailed ? 'campaign.failed' : 'campaign.sent',
                ['total_sent' => $totalSent, 'total_failed' => $totalFailed, 'opens' => $opens, 'clicks' => $clicks]
            );
        }
    }

    /**
     * Dispatch matching webhooks for this campaign's owner.
     */
    public function dispatchWebhook(string $event, array $extra = []): void
    {
        try {
            $webhooks = Webhook::where('user_id', $this->user_id)->get();

            if ($webhooks->isEmpty()) {
                return;
            }

            $payload = array_merge([
                'event' => $event,
                'campaign' => [
                    'id' => $this->uuid,
                    'name' => $this->name,
                    'subject' => $this->subject,
                    'status' => $this->status,
                ],
            ], $extra);

            foreach ($webhooks as $webhook) {
                if ($webhook->subscribesTo($event)) {
                    $webhook->dispatch($event, $payload);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook dispatch failed: ' . $e->getMessage());
        }
    }

    /**
     * Compute the next run timestamp for a recurring campaign.
     */
    public function computeNextRunAt(\DateTimeInterface|string|null $from = null): ?Carbon
    {
        if (!$this->frequency || !in_array($this->frequency, ['daily', 'weekly', 'monthly'])) {
            return null;
        }

        $base = $from ? Carbon::parse($from) : ($this->scheduled_at ?? now());

        return match ($this->frequency) {
            'daily' => Carbon::parse($base)->addDay(),
            'weekly' => Carbon::parse($base)->addWeek(),
            'monthly' => Carbon::parse($base)->addMonth(),
            default => null,
        };
    }

    /**
     * Reset failed recipients for a retry and re-dispatch them.
     */
    public function retryFailed(): void
    {
        $failed = $this->recipients()->failed()->get();

        foreach ($failed as $recipient) {
            $recipient->update(['status' => 'pending', 'error_message' => null]);
            SendCampaignEmail::dispatch($this, $recipient->email, $recipient->token);
        }

        if ($failed->isNotEmpty()) {
            $this->update(['status' => 'sending', 'error_message' => null]);
        }
    }

    /**
     * Update campaign metrics
     */
    public function updateMetrics(int $opens = null, int $clicks = null): void
    {
        if ($opens !== null) {
            $this->opens = $opens;
        }

        if ($clicks !== null) {
            $this->clicks = $clicks;
        }

        // Calculate rates
        if ($this->total_sent > 0) {
            $this->open_rate = round(($this->opens / $this->total_sent) * 100, 2);
            $this->click_rate = round(($this->clicks / $this->total_sent) * 100, 2);
        }

        $this->save();
    }

    /**
     * Get campaign statistics
     */
    public static function getStats(): array
    {
        return [
            'total_campaigns' => self::count(),
            'sent_campaigns' => self::where('status', 'sent')->count(),
            'draft_campaigns' => self::where('status', 'draft')->count(),
            'failed_campaigns' => self::where('status', 'failed')->count(),
            'total_emails_sent' => self::sum('total_sent'),
            'total_opens' => self::sum('opens'),
            'total_clicks' => self::sum('clicks'),
            'average_open_rate' => self::where('status', 'sent')->avg('open_rate') ?: 0,
            'average_click_rate' => self::where('status', 'sent')->avg('click_rate') ?: 0,
        ];
    }
}
