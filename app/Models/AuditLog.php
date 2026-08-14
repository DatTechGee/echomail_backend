<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public static function log(?int $userId, string $action, ?string $entityType = null, ?string $entityId = null, array $details = []): void
    {
        try {
            static::create([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed: ' . $e->getMessage());
        }
    }
}
