<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending online-gateway fee rows (CI student_fees_processing).
 */
class StudentFeesProcessing extends BaseModel
{
    protected $table = 'student_fees_processing';

    public $timestamps = true;

    protected $fillable = [
        'gateway_ins_id',
        'fee_category',
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

    /**
     * Processing amount_detail is a single flat object (not the deposit invoice map).
     *
     * @return array{amount:float,amount_discount:float,amount_fine:float,payment_mode:string,date:string,description:string}|null
     */
    public function decodedAmountDetail(): ?array
    {
        return self::parseAmountDetail($this->amount_detail);
    }

    /**
     * @return array{amount:float,amount_discount:float,amount_fine:float,payment_mode:string,date:string,description:string}|null
     */
    public static function parseAmountDetail(mixed $raw): ?array
    {
        if ($raw === null || $raw === '' || $raw === '0' || $raw === 0) {
            return null;
        }

        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (! is_array($decoded) || ! array_key_exists('amount', $decoded)) {
            return null;
        }

        return [
            'amount' => (float) ($decoded['amount'] ?? 0),
            'amount_discount' => (float) ($decoded['amount_discount'] ?? 0),
            'amount_fine' => (float) ($decoded['amount_fine'] ?? 0),
            'payment_mode' => (string) ($decoded['payment_mode'] ?? ''),
            'date' => (string) ($decoded['date'] ?? ''),
            'description' => (string) ($decoded['description'] ?? ''),
        ];
    }
}
