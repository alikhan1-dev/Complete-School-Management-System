<?php

namespace App\Modules\FrontOffice\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: follow_up.
 */
class FollowUp extends BaseModel
{
    protected $table = 'follow_up';

    public $timestamps = true;

    protected $fillable = [
        'enquiry_id',
        'date',
        'next_date',
        'response',
        'note',
        'followup_by',
    ];
}
