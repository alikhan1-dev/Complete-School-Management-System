<?php

namespace App\Modules\Certificates\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI transfer_certificate_settings (single-row design/settings).
 * Column create_at (legacy spelling) — no Laravel timestamps.
 */
class TransferCertificateSetting extends BaseModel
{
    protected $table = 'transfer_certificate_settings';

    public $timestamps = false;

    protected $fillable = [
        'tc_no_start',
        'affiliation_no',
        'header_image',
        'footer_content',
        'class_teacher_signature',
        'checked_by',
        'signature_of_principle',
        'create_at',
    ];
}
