<?php

namespace App\Modules\FrontOffice\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: general_calls.
 */
class GeneralCall extends BaseModel
{
    protected $table = 'general_calls';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'contact',
        'date',
        'description',
        'follow_up_date',
        'call_duration',
        'note',
        'call_type',
    ];
}
