<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CI table: staff_payslip.
 */
class StaffPayslip extends BaseModel
{
    protected $table = 'staff_payslip';

    public $timestamps = true;

    protected $fillable = [
        'staff_id',
        'basic',
        'total_allowance',
        'total_deduction',
        'leave_deduction',
        'tax',
        'net_salary',
        'status',
        'month',
        'year',
        'payment_mode',
        'payment_date',
        'remark',
        'generated_by',
    ];

    public function allowances(): HasMany
    {
        return $this->hasMany(PayslipAllowance::class, 'payslip_id');
    }
}
