<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = ['user_id', 'name', 'key', 'permissions', 'active', 'last_used_at'];

    protected $hidden = ['key'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateKey(): string
    {
        return 'em_' . Str::random(32);
    }
}
