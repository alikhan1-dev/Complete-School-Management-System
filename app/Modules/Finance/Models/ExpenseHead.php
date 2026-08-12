<?php

namespace App\Modules\Finance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseHead extends BaseModel
{
    protected $table = 'expense_head';

    public $timestamps = true;

    protected $fillable = [
        'exp_category',
        'description',
        'is_active',
        'is_deleted',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'exp_head_id');
    }
}
