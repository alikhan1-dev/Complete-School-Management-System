<?php

namespace App\Modules\Transport\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI vehicles — transport fleet rows.
 */
class Vehicle extends BaseModel
{
    protected $table = 'vehicles';

    public $timestamps = true;

    protected $fillable = [
        'vehicle_no',
        'vehicle_model',
        'vehicle_photo',
        'manufacture_year',
        'registration_number',
        'chasis_number',
        'max_seating_capacity',
        'driver_name',
        'driver_licence',
        'driver_contact',
        'note',
    ];
}
