<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI item_issue — issued inventory to staff.
 * is_returned: 1 = still issued, 0 = returned (CI parity).
 */
class ItemIssue extends BaseModel
{
    protected $table = 'item_issue';

    public $timestamps = true;

    protected $fillable = [
        'issue_type',
        'issue_to',
        'issue_by',
        'issue_date',
        'return_date',
        'item_category_id',
        'item_id',
        'quantity',
        'note',
        'is_returned',
        'is_active',
    ];
}
