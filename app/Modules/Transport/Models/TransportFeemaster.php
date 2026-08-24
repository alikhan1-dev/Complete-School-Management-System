<?php

namespace App\Modules\Transport\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI transport_feemaster — monthly transport fee due dates / fines per session.
 */
class TransportFeemaster extends BaseModel
{
    protected $table = 'transport_feemaster';

    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'month',
        'due_date',
        'fine_amount',
        'fine_type',
        'fine_percentage',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'fine_amount' => 'float',
        'fine_percentage' => 'float',
    ];
}
