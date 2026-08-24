<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI thermal_print addon settings row (thermal_print_model::get).
 */
class ThermalPrint extends BaseModel
{
    protected $table = 'thermal_print';

    public $timestamps = true;

    protected $fillable = [
        'school_name',
        'address',
        'footer_text',
        'is_print',
    ];

    protected $casts = [
        'is_print' => 'integer',
    ];
}
