<?php

namespace App\Modules\Students\Models;

use App\Modules\Shared\Models\BaseModel;

class DisableReason extends BaseModel
{
    protected $table = 'disable_reason';

    public $timestamps = true;

    protected $fillable = [
        'reason',
    ];
}
