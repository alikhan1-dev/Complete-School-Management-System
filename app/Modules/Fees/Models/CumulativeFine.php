<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CI cumulative_fine — overdue-day slabs for fine_type=cumulative.
 */
class CumulativeFine extends BaseModel
{
    protected $table = 'cumulative_fine';

    public $timestamps = true;

    protected $fillable = [
        'overdue_day',
        'fine_amount',
        'fee_groups_feetype_id',
        'fee_session_group_id',
    ];

    public function feeGroupFeetype(): BelongsTo
    {
        return $this->belongsTo(FeeGroupFeetype::class, 'fee_groups_feetype_id');
    }

    public function sessionGroup(): BelongsTo
    {
        return $this->belongsTo(FeeSessionGroup::class, 'fee_session_group_id');
    }
}
