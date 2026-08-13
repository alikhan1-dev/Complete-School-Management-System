<?php

namespace App\Modules\Hostel\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI hostel — hostel buildings.
 */
class Hostel extends BaseModel
{
    protected $table = 'hostel';

    public $timestamps = true;

    protected $fillable = [
        'hostel_name',
        'type',
        'address',
        'intake',
        'description',
        'is_active',
    ];
}
