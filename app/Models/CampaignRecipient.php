<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'email',
        'token',
        'status',
        'opened_at',
        'clicked_at',
        'error_message',
        'bounced_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    public function scopeBounced($query)
    {
        return $query->whereNotNull('bounced_at');
    }
}
