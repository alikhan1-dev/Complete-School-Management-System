<?php

namespace App\Modules\Payments\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: online_admission_payment.
 */
class OnlineAdmissionPayment extends BaseModel
{
    protected $table = 'online_admission_payment';

    public $timestamps = true;

    protected $guarded = [];
}
