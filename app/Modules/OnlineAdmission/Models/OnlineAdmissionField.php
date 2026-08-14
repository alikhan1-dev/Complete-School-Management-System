<?php

namespace App\Modules\OnlineAdmission\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: online_admission_fields.
 */
class OnlineAdmissionField extends BaseModel
{
    protected $table = 'online_admission_fields';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'status',
    ];
}
