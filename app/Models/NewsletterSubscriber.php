<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'email',
        'name',
        'phone',
        'source',
        'status',
        'subscribed_at',
        'unsubscribed_at',
        'unsubscribe_token',
        'verify_token',
        'verified_at',
        'preferences',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'verified_at' => 'datetime',
        'preferences' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscriber) {
            if (empty($subscriber->uuid)) {
                $subscriber->uuid = Str::uuid();
            }
            if (empty($subscriber->unsubscribe_token)) {
                $subscriber->unsubscribe_token = Str::random(32);
            }
            if (empty($subscriber->verify_token)) {
                $subscriber->verify_token = Str::random(32);
            }
            if (empty($subscriber->subscribed_at)) {
                $subscriber->subscribed_at = now();
            }
            if (empty($subscriber->preferences)) {
                $subscriber->preferences = [
                    'email_updates' => true,
                    'product_updates' => true,
                    'promotions' => true,
                ];
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function getUnsubscribeUrlAttribute()
    {
        return url('/unsubscribe/' . $this->unsubscribe_token);
    }

    public function getVerifyUrlAttribute()
    {
        return url('/verify/' . $this->verify_token);
    }

    public function getPreferencesUrlAttribute()
    {
        return url('/preferences/' . $this->unsubscribe_token);
    }

    public function unsubscribe()
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    public function resubscribe()
    {
        $this->update([
            'status' => 'active',
            'unsubscribed_at' => null,
        ]);
    }

    public function verify()
    {
        $this->update([
            'status' => 'active',
            'verified_at' => now(),
        ]);
    }
}
