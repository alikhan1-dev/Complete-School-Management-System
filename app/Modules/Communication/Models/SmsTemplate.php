<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: sms_template.
 */
class SmsTemplate extends BaseModel
{
    protected $table = 'sms_template';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'message',
        'created_at',
        'updated_at',
    ];
}
