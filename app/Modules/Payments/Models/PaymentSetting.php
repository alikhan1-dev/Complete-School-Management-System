<?php

namespace App\Modules\Payments\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: payment_settings — one row per gateway payment_type.
 */
class PaymentSetting extends BaseModel
{
    protected $table = 'payment_settings';

    public $timestamps = true;

    protected $guarded = [];
}
