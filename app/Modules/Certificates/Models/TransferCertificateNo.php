<?php

namespace App\Modules\Certificates\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI transfer_certificate_no — issued TC serials.
 * Column is_regenerte / create_at (legacy spellings) — no Laravel timestamps.
 */
class TransferCertificateNo extends BaseModel
{
    protected $table = 'transfer_certificate_no';

    public $timestamps = false;

    protected $fillable = [
        'student_session_id',
        'tc_no',
        'is_regenerte',
        'create_at',
    ];
}
