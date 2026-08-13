<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI item_category — inventory category master.
 */
class ItemCategory extends BaseModel
{
    protected $table = 'item_category';

    public $timestamps = true;

    protected $fillable = [
        'item_category',
        'is_active',
        'description',
    ];
}
