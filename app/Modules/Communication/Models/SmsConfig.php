<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: sms_config — one row per gateway type.
 * is_active: enabled | disabled
 */
class SmsConfig extends BaseModel
{
    protected $table = 'sms_config';

    public $timestamps = true;

    protected $fillable = [
        'type',
        'name',
        'api_id',
        'authkey',
        'senderid',
        'contact',
        'username',
        'url',
        'password',
        'is_active',
    ];
}
