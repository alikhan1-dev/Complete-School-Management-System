<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;

class FeesDiscount extends BaseModel
{
    protected $table = 'fees_discounts';

    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'name',
        'code',
        'type',
        'percentage',
        'amount',
        'discount_limit',
        'expire_date',
        'description',
        'is_active',
    ];
}
