<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeGroupFeetype extends BaseModel
{
    protected $table = 'fee_groups_feetype';

    public $timestamps = true;

    protected $fillable = [
        'fee_session_group_id',
        'fee_groups_id',
        'feetype_id',
        'session_id',
        'amount',
        'fine_type',
        'due_date',
        'fine_percentage',
        'fine_amount',
        'fine_per_day',
        'is_active',
    ];

    public function sessionGroup(): BelongsTo
    {
        return $this->belongsTo(FeeSessionGroup::class, 'fee_session_group_id');
    }

    public function feeGroup(): BelongsTo
    {
        return $this->belongsTo(FeeGroup::class, 'fee_groups_id');
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class, 'feetype_id');
    }
}
