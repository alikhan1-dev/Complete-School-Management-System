<?php

namespace App\Modules\OnlineAdmission\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: online_admissions.
 */
class OnlineAdmission extends BaseModel
{
    protected $table = 'online_admissions';

    public $timestamps = true;

    protected $guarded = [];
}
