<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    protected $hidden = ['value'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function get(int $userId, string $key, mixed $default = null): mixed
    {
        $setting = static::where('user_id', $userId)->where('key', $key)->first();
        if (!$setting) return $default;

        return json_decode($setting->value, true) ?? $setting->value;
    }

    public static function set(int $userId, string $key, mixed $value): static
    {
        $encoded = is_array($value) ? json_encode($value) : (string) $value;

        return static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $encoded]
        );
    }
}
