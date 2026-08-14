<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: email_config — single-row mail engine settings.
 */
class EmailConfig extends BaseModel
{
    protected $table = 'email_config';

    public $timestamps = true;

    protected $fillable = [
        'email_type',
        'smtp_server',
        'smtp_port',
        'smtp_email',
        'smtp_username',
        'smtp_password',
        'ssl_tls',
        'smtp_auth',
        'api_key',
        'api_secret',
        'region',
        'is_active',
    ];
}
