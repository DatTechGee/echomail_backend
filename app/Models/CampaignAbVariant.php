<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAbVariant extends Model
{
    protected $fillable = [
        'ab_test_id',
        'variant_key',
        'subject',
        'content',
        'recipients_sent',
        'opens',
        'clicks',
        'open_rate',
        'click_rate',
        'is_winner',
    ];

    protected $casts = [
        'recipients_sent' => 'integer',
        'opens' => 'integer',
        'clicks' => 'integer',
        'open_rate' => 'float',
        'click_rate' => 'float',
        'is_winner' => 'boolean',
    ];

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(CampaignAbTest::class, 'ab_test_id');
    }

    public function computeRates(): void
    {
        $this->open_rate = $this->recipients_sent > 0
            ? round(($this->opens / $this->recipients_sent) * 100, 2)
            : 0;
        $this->click_rate = $this->recipients_sent > 0
            ? round(($this->clicks / $this->recipients_sent) * 100, 2)
            : 0;
        $this->save();
    }
}
