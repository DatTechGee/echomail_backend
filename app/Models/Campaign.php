<?php

namespace App\Models;

use App\Helper\BlockNoteParser;
use App\Models\Contact;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    protected $casts = [
        'recipient_config' => 'array',
        'recipient_emails' => 'array',
        'sent_at' => 'datetime',
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
                $newsletters = NewsletterSubscriber::active()->get();
                foreach ($newsletters as $subscriber) {
                    if (!in_array($subscriber->email, $recipients)) {
                        $recipients[] = $subscriber->email;
                    }
                }
                break;

            case 'newsletter':
                $newsletters = NewsletterSubscriber::active()->get();
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
