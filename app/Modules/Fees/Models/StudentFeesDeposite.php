<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CI table name spelling preserved: student_fees_deposite.
 */
class StudentFeesDeposite extends BaseModel
{
    protected $table = 'student_fees_deposite';

    public $timestamps = true;

    protected $fillable = [
        'student_fees_master_id',
        'fee_groups_feetype_id',
        'student_transport_fee_id',
        'amount_detail',
        'is_active',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(StudentFeesMaster::class, 'student_fees_master_id');
    }

    public function feeGroupFeetype(): BelongsTo
    {
        return $this->belongsTo(FeeGroupFeetype::class, 'fee_groups_feetype_id');
    }

    public function appliedDiscounts(): HasMany
    {
        return $this->hasMany(StudentAppliedDiscount::class, 'student_fees_deposite_id');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function decodedAmountDetail(): array
    {
        $raw = $this->amount_detail;
        if ($raw === null || $raw === '' || $raw === '0') {
            return [];
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
