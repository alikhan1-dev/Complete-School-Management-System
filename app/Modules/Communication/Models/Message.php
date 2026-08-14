<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: messages — email/SMS compose log.
 */
class Message extends BaseModel
{
    protected $table = 'messages';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'template_id',
        'email_template_id',
        'sms_template_id',
        'send_through',
        'message',
        'send_mail',
        'send_sms',
        'is_group',
        'is_individual',
        'is_class',
        'is_schedule',
        'sent',
        'schedule_date_time',
        'group_list',
        'user_list',
        'send_to',
        'schedule_class',
        'schedule_section',
    ];
}
