<?php

namespace App\Models;

use App\Helper\BlockNoteParser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CampaignTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'subject',
        'content',
        'html_content',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->uuid)) {
                $template->uuid = Str::uuid();
            }

            if ($template->content && !$template->html_content) {
                $template->html_content = BlockNoteParser::parse($template->content);
            }
        });

        static::updating(function ($template) {
            if ($template->isDirty('content')) {
                $template->html_content = BlockNoteParser::parse($template->content);
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

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('subject', 'like', "%{$search}%");
        });
    }
}
