<?php

namespace App\Modules\Finance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomeHead extends BaseModel
{
    protected $table = 'income_head';

    public $timestamps = true;

    protected $fillable = [
        'income_category',
        'description',
        'is_active',
        'is_deleted',
    ];

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class, 'income_head_id');
    }
}
