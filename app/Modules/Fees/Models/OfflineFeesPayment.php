<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI offline_fees_payments — student offline bank payment requests.
 */
class OfflineFeesPayment extends BaseModel
{
    protected $table = 'offline_fees_payments';

    public $timestamps = true;

    protected $fillable = [
        'invoice_id',
        'student_session_id',
        'student_fees_master_id',
        'fee_groups_feetype_id',
        'student_transport_fee_id',
        'payment_date',
        'bank_from',
        'bank_account_transferred',
        'reference',
        'amount',
        'submit_date',
        'approve_date',
        'attachment',
        'reply',
        'approved_by',
        'is_active',
    ];
}
