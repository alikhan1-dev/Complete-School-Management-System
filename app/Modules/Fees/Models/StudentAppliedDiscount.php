<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAppliedDiscount extends BaseModel
{
    protected $table = 'student_applied_discounts';

    public $timestamps = true;

    protected $fillable = [
        'student_fees_deposite_id',
        'student_fees_discount_id',
        'invoice_id',
        'sub_invoice_id',
        'date',
    ];

    public function deposite(): BelongsTo
    {
        return $this->belongsTo(StudentFeesDeposite::class, 'student_fees_deposite_id');
    }

    public function studentFeesDiscount(): BelongsTo
    {
        return $this->belongsTo(StudentFeesDiscount::class, 'student_fees_discount_id');
    }
}
