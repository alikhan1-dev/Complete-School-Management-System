<?php

namespace App\Modules\Transport\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI pickup_point — pickup locations.
 */
class PickupPoint extends BaseModel
{
    protected $table = 'pickup_point';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
    ];
}
