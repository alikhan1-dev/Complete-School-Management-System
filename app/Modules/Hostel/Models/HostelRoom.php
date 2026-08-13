<?php

namespace App\Modules\Hostel\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI hostel_rooms — rooms within hostels.
 */
class HostelRoom extends BaseModel
{
    protected $table = 'hostel_rooms';

    public $timestamps = true;

    protected $fillable = [
        'hostel_id',
        'room_type_id',
        'room_no',
        'no_of_bed',
        'cost_per_bed',
        'title',
        'description',
    ];
}
