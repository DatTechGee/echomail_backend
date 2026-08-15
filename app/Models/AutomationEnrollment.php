<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationEnrollment extends Model
{
    protected $fillable = [
        'automation_id',
        'email',
        'name',
        'status',
        'current_step',
        'next_action_at',
        'completed_at',
    ];

    protected $casts = [
        'current_step' => 'integer',
        'next_action_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }
}
