<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: send_notification — notice board posts.
 */
class SendNotification extends BaseModel
{
    protected $table = 'send_notification';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'publish_date',
        'date',
        'attachment',
        'message',
        'visible_student',
        'visible_staff',
        'visible_parent',
        'created_by',
        'created_id',
        'is_active',
    ];
}
