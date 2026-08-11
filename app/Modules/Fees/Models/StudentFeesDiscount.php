<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;

class StudentFeesDiscount extends BaseModel
{
    protected $table = 'student_fees_discounts';

    public $timestamps = true;

    protected $fillable = [
        'student_session_id',
        'fees_discount_id',
        'status',
        'payment_id',
        'description',
        'is_active',
    ];
}
