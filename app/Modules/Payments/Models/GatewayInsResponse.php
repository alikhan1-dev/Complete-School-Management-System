<?php

namespace App\Modules\Payments\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: gateway_ins_response — raw gateway callback payloads.
 */
class GatewayInsResponse extends BaseModel
{
    protected $table = 'gateway_ins_response';

    public $timestamps = true;

    protected $guarded = [];
}
