<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

class Webhook extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'events',
        'secret',
        'active',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webhook) {
            if (empty($webhook->secret)) {
                $webhook->secret = \Illuminate\Support\Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscribesTo(string $event): bool
    {
        if (!$this->active) {
            return false;
        }

        $events = $this->events ?? [];

        return in_array('*', $events) || in_array($event, $events);
    }

    public function dispatch(string $event, array $payload): void
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Webhook-Secret' => $this->secret,
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Delivery' => (string) \Illuminate\Support\Str::uuid(),
                ])
                ->acceptJson()
                ->post($this->url, $payload);

            WebhookDelivery::create([
                'webhook_id' => $this->id,
                'event' => $event,
                'payload' => $payload,
                'status_code' => $response->status(),
                'response' => $response->body(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            WebhookDelivery::create([
                'webhook_id' => $this->id,
                'event' => $event,
                'payload' => $payload,
                'status_code' => 0,
                'response' => $e->getMessage(),
                'created_at' => now(),
            ]);
        }
    }
}
