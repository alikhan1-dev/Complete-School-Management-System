<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI fees_reminder — before/after due-date reminder day rules.
 */
class FeesReminder extends BaseModel
{
    protected $table = 'fees_reminder';

    public $timestamps = true;

    protected $fillable = [
        'reminder_type',
        'day',
        'is_active',
    ];

    protected $casts = [
        'day' => 'integer',
        'is_active' => 'integer',
    ];
}
