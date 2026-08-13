<?php

namespace App\Modules\Hostel\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI room_types — hostel room type master.
 */
class RoomType extends BaseModel
{
    protected $table = 'room_types';

    public $timestamps = true;

    protected $fillable = [
        'room_type',
        'description',
    ];
}
