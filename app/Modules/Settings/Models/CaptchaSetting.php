<?php

namespace App\Modules\Settings\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: captcha.
 */
class CaptchaSetting extends BaseModel
{
    protected $table = 'captcha';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'status',
    ];
}
