<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationStep extends Model
{
    protected $fillable = [
        'automation_id',
        'step_order',
        'step_type',
        'step_config',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'step_config' => 'array',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }
}
