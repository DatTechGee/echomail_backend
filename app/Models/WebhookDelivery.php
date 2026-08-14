<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'status_code',
        'response',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
