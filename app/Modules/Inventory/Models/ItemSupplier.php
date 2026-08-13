<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI item_supplier — inventory supplier master.
 */
class ItemSupplier extends BaseModel
{
    protected $table = 'item_supplier';

    public $timestamps = true;

    protected $fillable = [
        'item_supplier',
        'phone',
        'email',
        'address',
        'contact_person_name',
        'contact_person_phone',
        'contact_person_email',
        'description',
    ];
}
