<?php

namespace App\Modules\FrontOffice\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: dispatch_receive (type = dispatch|receive).
 */
class DispatchReceive extends BaseModel
{
    protected $table = 'dispatch_receive';

    public $timestamps = true;

    protected $fillable = [
        'reference_no',
        'to_title',
        'type',
        'address',
        'note',
        'from_title',
        'date',
        'image',
    ];
}
