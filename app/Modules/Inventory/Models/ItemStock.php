<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI item_stock — stock receipts/adjustments.
 */
class ItemStock extends BaseModel
{
    protected $table = 'item_stock';

    public $timestamps = true;

    protected $fillable = [
        'item_id',
        'supplier_id',
        'store_id',
        'symbol',
        'quantity',
        'purchase_price',
        'date',
        'attachment',
        'description',
        'is_active',
    ];
}
