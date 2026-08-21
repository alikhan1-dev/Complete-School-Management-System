<?php

namespace App\Modules\Students\Models;

use App\Modules\Shared\Models\BaseModel;

class AlumniEvent extends BaseModel
{
    protected $table = 'alumni_events';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'event_for',
        'session_id',
        'class_id',
        'section',
        'from_date',
        'to_date',
        'note',
        'photo',
        'is_active',
        'event_notification_message',
        'show_onwebsite',
    ];
}
