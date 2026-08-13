<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: payslip_allowance.
 */
class PayslipAllowance extends BaseModel
{
    protected $table = 'payslip_allowance';

    public $timestamps = true;

    protected $fillable = [
        'payslip_id',
        'allowance_type',
        'amount',
        'staff_id',
        'cal_type',
    ];
}
