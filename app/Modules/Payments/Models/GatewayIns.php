<?php

namespace App\Modules\Payments\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: gateway_ins — pending gateway checkout instances.
 */
class GatewayIns extends BaseModel
{
    protected $table = 'gateway_ins';

    public $timestamps = true;

    protected $guarded = [];
}
