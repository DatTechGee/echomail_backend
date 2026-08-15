<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignAbTest extends Model
{
    protected $fillable = [
        'campaign_id',
        'name',
        'test_type',
        'status',
        'test_percentage',
        'winner_selected_at',
    ];

    protected $casts = [
        'test_percentage' => 'integer',
        'winner_selected_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(CampaignAbVariant::class, 'ab_test_id');
    }

    public function winner()
    {
        return $this->variants()->where('is_winner', true)->first();
    }

    public function getWinnerAttribute()
    {
        return $this->variants()->where('is_winner', true)->first();
    }
}
