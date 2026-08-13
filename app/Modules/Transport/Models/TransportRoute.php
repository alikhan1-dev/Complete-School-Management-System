<?php

namespace App\Modules\Transport\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI transport_route — bus route titles.
 */
class TransportRoute extends BaseModel
{
    protected $table = 'transport_route';

    public $timestamps = true;

    protected $fillable = [
        'route_title',
        'no_of_vehicle',
        'note',
        'is_active',
    ];
}
