<?php

namespace App\Modules\Settings\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table currencies.
 */
class Currency extends BaseModel
{
    protected $table = 'currencies';

    public $timestamps = true;

    protected $guarded = [];
}
