<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI item — inventory item catalog.
 */
class InventoryItem extends BaseModel
{
    protected $table = 'item';

    public $timestamps = true;

    protected $fillable = [
        'item_category_id',
        'item_store_id',
        'item_supplier_id',
        'name',
        'unit',
        'item_photo',
        'description',
        'quantity',
        'date',
    ];
}
