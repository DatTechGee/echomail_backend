<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'email',
        'name',
        'groups',
        'source',
        'added_at',
    ];

    protected $casts = [
        'groups' => 'array',
        'added_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contact) {
            if (empty($contact->uuid)) {
                $contact->uuid = Str::uuid();
            }
            if (empty($contact->added_at)) {
                $contact->added_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeInGroup($query, $groupName)
    {
        return $query->whereJsonContains('groups', $groupName);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('email', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }

    public function hasGroup($groupName): bool
    {
        return in_array($groupName, $this->groups ?? []);
    }

    public function addGroup($groupName): void
    {
        $groups = $this->groups ?? [];
        if (!in_array($groupName, $groups)) {
            $groups[] = $groupName;
            $this->update(['groups' => $groups]);
        }
    }

    public function removeGroup($groupName): void
    {
        $groups = $this->groups ?? [];
        $groups = array_values(array_filter($groups, fn($g) => $g !== $groupName));
        $this->update(['groups' => $groups]);
    }

    public function getInitialsAttribute(): string
    {
        if ($this->name) {
            return collect(explode(' ', $this->name))
                ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                ->take(2)
                ->join('');
        }
        return strtoupper(substr($this->email, 0, 1));
    }
}
