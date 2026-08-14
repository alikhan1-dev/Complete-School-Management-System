<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: notification_setting — event mail/SMS/app flags and templates.
 */
class NotificationSetting extends BaseModel
{
    protected $table = 'notification_setting';

    public $timestamps = true;

    protected $fillable = [
        'type',
        'is_mail',
        'is_whatsapp',
        'is_sms',
        'is_notification',
        'display_notification',
        'display_sms',
        'display_whatsapp',
        'is_student_recipient',
        'is_guardian_recipient',
        'is_staff_recipient',
        'display_student_recipient',
        'display_guardian_recipient',
        'display_staff_recipient',
        'subject',
        'template_id',
        'whatsapp_template_id',
        'template',
        'variables',
        'notification_order',
    ];
}
