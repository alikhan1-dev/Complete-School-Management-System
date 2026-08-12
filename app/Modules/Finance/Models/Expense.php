<?php

namespace App\Modules\Finance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends BaseModel
{
    protected $table = 'expenses';

    public $timestamps = true;

    protected $fillable = [
        'exp_head_id',
        'name',
        'invoice_no',
        'date',
        'amount',
        'documents',
        'note',
        'is_active',
        'is_deleted',
    ];

    public function head(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class, 'exp_head_id');
    }
}
