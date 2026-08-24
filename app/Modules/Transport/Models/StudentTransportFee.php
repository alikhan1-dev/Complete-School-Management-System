<?php

namespace App\Modules\Transport\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI student_transport_fees — assigned transport fee months per student session.
 */
class StudentTransportFee extends BaseModel
{
    protected $table = 'student_transport_fees';

    public $timestamps = true;

    protected $fillable = [
        'transport_feemaster_id',
        'student_session_id',
        'route_pickup_point_id',
        'generated_by',
    ];

    protected $casts = [
        'transport_feemaster_id' => 'integer',
        'student_session_id' => 'integer',
        'route_pickup_point_id' => 'integer',
        'generated_by' => 'integer',
    ];
}
