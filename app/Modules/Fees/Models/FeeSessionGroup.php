<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeSessionGroup extends BaseModel
{
    protected $table = 'fee_session_groups';

    public $timestamps = true;

    protected $fillable = [
        'fee_groups_id',
        'session_id',
        'is_active',
    ];

    public function feeGroup(): BelongsTo
    {
        return $this->belongsTo(FeeGroup::class, 'fee_groups_id');
    }

    public function feeTypes(): HasMany
    {
        return $this->hasMany(FeeGroupFeetype::class, 'fee_session_group_id');
    }
}
