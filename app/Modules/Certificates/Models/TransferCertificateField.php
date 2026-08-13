<?php

namespace App\Modules\Certificates\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI transfer_certificate_fields — which student fields appear on the TC.
 */
class TransferCertificateField extends BaseModel
{
    protected $table = 'transfer_certificate_fields';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'lang_key',
        'status',
        'position',
        'is_default',
        'is_active',
    ];
}
