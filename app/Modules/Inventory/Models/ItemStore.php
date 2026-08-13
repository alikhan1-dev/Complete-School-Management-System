<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI item_store — inventory store master.
 */
class ItemStore extends BaseModel
{
    protected $table = 'item_store';

    public $timestamps = true;

    protected $fillable = [
        'item_store',
        'code',
        'description',
    ];
}
