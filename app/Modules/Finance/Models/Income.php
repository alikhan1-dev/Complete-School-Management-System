<?php

namespace App\Modules\Finance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends BaseModel
{
    protected $table = 'income';

    public $timestamps = true;

    protected $fillable = [
        'income_head_id',
        'name',
        'invoice_no',
        'date',
        'amount',
        'note',
        'is_active',
        'documents',
        'is_deleted',
    ];

    public function head(): BelongsTo
    {
        return $this->belongsTo(IncomeHead::class, 'income_head_id');
    }
}
