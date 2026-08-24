<?php

namespace App\Modules\Fees\Services;

use App\Modules\Payments\Services\PaymentSettingService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * CI user/User::geBalanceFee + getcollectfee + user/gateway/Payment::pay (online_payment).
 * Live gateway charge APIs remain deferred (Payments module).
 */
class PortalOnlinePayService
{
    public const SESSION_PARAMS_KEY = 'online_payment_params';

    public function __construct(
        protected FeeCollectService $collect,
        protected PaymentSettingService $paymentSettings,
        protected SchoolContext $school,
        protected StudentFeesPortalService $portal,
    ) {
    }

    public function hasActivePaymentMethod(): bool
    {
        return $this->paymentSettings->activeMethod() !== null;
    }

    public function allowPartialPayment(): bool
    {
        $flag = $this->school->get('student_partial_payment', 0);
        $normalized = strtolower(trim((string) $flag));

        return in_array($normalized, ['enabled', '1', 'true', 'yes'], true)
            || $flag === 1
            || $flag === true;
    }

    /**
     * CI User::geBalanceFee success payload (currency conversion left as raw numbers for parity with Laravel fee ledger).
     *
     * @return array{
     *     status:string,
     *     error:string,
     *     balance:string,
     *     remain_amount_fine:string,
     *     student_fees:string,
     *     discount_not_applied:list<object>
     * }
     */
    public function balanceFee(array $input, int $studentSessionId): array
    {
        if ($studentSessionId <= 0) {
            throw new RuntimeException('Student session is required.');
        }

        $student = $this->collect->findStudentBySession($studentSessionId);
        if (! $student) {
            throw new RuntimeException('Student not found.');
        }
        $this->portal->assertOwnsStudent((int) $student->id);

        $category = (string) ($input['fee_category'] ?? 'fees');
        if ($category === 'transport') {
            $transportId = (int) ($input['trans_fee_id'] ?? $input['student_transport_fee_id'] ?? 0);
            if ($transportId <= 0) {
                throw new InvalidArgumentException('Transport fee is required.');
            }
            $this->assertOwnsTransportFee($transportId, $studentSessionId);
            $info = $this->collect->getTransportBalance($transportId);
        } else {
            $masterId = (int) ($input['student_fees_master_id'] ?? 0);
            $feetypeId = (int) ($input['fee_groups_feetype_id'] ?? 0);
            if ($masterId <= 0 || $feetypeId <= 0) {
                throw new InvalidArgumentException('Fee line is required.');
            }
            $this->assertOwnsFeeMaster($masterId, $studentSessionId);
            $info = $this->collect->getBalance($masterId, $feetypeId);
        }

        $discounts = $this->collect->getAvailableDiscounts($studentSessionId);

        return [
            'status' => 'success',
            'error' => '',
            'balance' => number_format(max(0, (float) $info['balance']), 2, '.', ''),
            'remain_amount_fine' => number_format(max(0, (float) $info['remaining_fine']), 2, '.', ''),
            'student_fees' => number_format((float) ($info['student_fees'] ?? $info['due']), 2, '.', ''),
            'discount_not_applied' => $discounts,
        ];
    }

    /**
     * CI User::getcollectfee — selected unpaid lines for Pay Selected modal.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<object>
     */
    public function collectFeeLines(array $items, int $studentSessionId): array
    {
        if ($studentSessionId <= 0) {
            throw new RuntimeException('Student session is required.');
        }

        $student = $this->collect->findStudentBySession($studentSessionId);
        if (! $student) {
            throw new RuntimeException('Student not found.');
        }
        $this->portal->assertOwnsStudent((int) $student->id);

        $lines = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $category = (string) ($item['fee_category'] ?? 'fees');
            if ($category === 'transport') {
                $transportId = (int) ($item['trans_fee_id'] ?? 0);
                if ($transportId <= 0) {
                    continue;
                }
                $this->assertOwnsTransportFee($transportId, $studentSessionId);
                $info = $this->collect->getTransportBalance($transportId);
                if ($info['balance'] <= 0) {
                    continue;
                }
                $lines[] = (object) [
                    'fee_category' => 'transport',
                    'student_transport_fee_id' => $transportId,
                    'student_fees_master_id' => 0,
                    'fee_groups_feetype_id' => 0,
                    'fee_session_group_id' => 0,
                    'fee_group_name' => $info['fee_group_name'],
                    'fee_type' => $info['fee_type'],
                    'fee_code' => '',
                    'due_amount' => $info['due'],
                    'balance' => $info['balance'],
                    'remaining_fine' => $info['remaining_fine'],
                ];

                continue;
            }

            $masterId = (int) ($item['fee_master_id'] ?? $item['student_fees_master_id'] ?? 0);
            $feetypeId = (int) ($item['fee_groups_feetype_id'] ?? 0);
            if ($masterId <= 0 || $feetypeId <= 0) {
                continue;
            }
            $this->assertOwnsFeeMaster($masterId, $studentSessionId);
            $info = $this->collect->getBalance($masterId, $feetypeId);
            if ($info['balance'] <= 0) {
                continue;
            }
            $lines[] = (object) [
                'fee_category' => 'fees',
                'student_transport_fee_id' => 0,
                'student_fees_master_id' => $masterId,
                'fee_groups_feetype_id' => $feetypeId,
                'fee_session_group_id' => $info['fee_session_group_id'],
                'fee_group_name' => $info['fee_group_name'],
                'fee_type' => $info['fee_type'],
                'fee_code' => $info['fee_code'],
                'due_amount' => $info['due'],
                'balance' => $info['balance'],
                'remaining_fine' => $info['remaining_fine'],
            ];
        }

        return $lines;
    }

    /**
     * CI Payment::pay online_payment — stash checkout params; live charge deferred.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function startOnlinePayment(array $input, int $studentSessionId): array
    {
        if (! $this->hasActivePaymentMethod()) {
            throw new InvalidArgumentException('No active payment method.');
        }
        if ($studentSessionId <= 0) {
            throw new InvalidArgumentException('Student session is required.');
        }

        $student = $this->collect->findStudentBySession($studentSessionId);
        if (! $student) {
            throw new InvalidArgumentException('Student not found.');
        }
        $this->portal->assertOwnsStudent((int) $student->id);

        $method = $this->paymentSettings->activeMethod();
        $lines = [];

        // Multi-pay: row_counter[] from collect modal
        $rows = $input['row_counter'] ?? null;
        if (is_array($rows) && $rows !== []) {
            foreach ($rows as $row) {
                $row = (int) $row;
                $category = (string) ($input['fee_category_'.$row] ?? 'fees');
                $amount = (float) ($input['fee_amount_'.$row] ?? 0);
                $fine = (float) ($input['fee_groups_feetype_fine_amount_'.$row] ?? 0);
                if ($amount <= 0 && $fine <= 0) {
                    continue;
                }
                if ($category === 'transport') {
                    $transportId = (int) ($input['trans_fee_id_'.$row] ?? 0);
                    if ($transportId <= 0) {
                        continue;
                    }
                    $this->assertOwnsTransportFee($transportId, $studentSessionId);
                    $info = $this->collect->getTransportBalance($transportId);
                    $payAmount = $this->clampAmount($amount, (float) $info['balance']);
                    $payFine = $this->clampAmount($fine, (float) $info['remaining_fine']);
                    $lines[] = [
                        'fee_category' => 'transport',
                        'student_transport_fee_id' => $transportId,
                        'student_fees_master_id' => 0,
                        'fee_groups_feetype_id' => 0,
                        'fee_group_name' => $info['fee_group_name'],
                        'fee_type_code' => $info['fee_type'],
                        'amount_balance' => $payAmount,
                        'fine_balance' => $payFine,
                        'applied_fee_discount' => 0.0,
                    ];
                } else {
                    $masterId = (int) ($input['student_fees_master_id_'.$row] ?? 0);
                    $feetypeId = (int) ($input['fee_groups_feetype_id_'.$row] ?? 0);
                    if ($masterId <= 0 || $feetypeId <= 0) {
                        continue;
                    }
                    $this->assertOwnsFeeMaster($masterId, $studentSessionId);
                    $info = $this->collect->getBalance($masterId, $feetypeId);
                    $payAmount = $this->clampAmount($amount, (float) $info['balance']);
                    $payFine = $this->clampAmount($fine, (float) $info['remaining_fine']);
                    $lines[] = [
                        'fee_category' => 'fees',
                        'student_transport_fee_id' => 0,
                        'student_fees_master_id' => $masterId,
                        'fee_groups_feetype_id' => $feetypeId,
                        'fee_session_group_id' => $info['fee_session_group_id'],
                        'fee_group_name' => $info['fee_group_name'],
                        'fee_type_code' => $info['fee_code'] !== '' ? $info['fee_code'] : $info['fee_type'],
                        'amount_balance' => $payAmount,
                        'fine_balance' => $payFine,
                        'applied_fee_discount' => 0.0,
                    ];
                }
            }
        } else {
            // Single-pay modal
            $category = (string) ($input['fee_category'] ?? 'fees');
            $discount = (float) ($input['fee_discount'] ?? 0);
            $postedFee = isset($input['fee_amount_single']) ? (float) $input['fee_amount_single'] : null;
            $postedFine = isset($input['fine_amount_single']) ? (float) $input['fine_amount_single'] : null;

            if ($category === 'transport') {
                $transportId = (int) ($input['student_transport_fee_id'] ?? $input['trans_fee_id'] ?? 0);
                if ($transportId <= 0) {
                    throw new InvalidArgumentException('Transport fee is required.');
                }
                $this->assertOwnsTransportFee($transportId, $studentSessionId);
                $info = $this->collect->getTransportBalance($transportId);
                $amount = $postedFee !== null ? $postedFee : (float) $info['balance'];
                $fine = $postedFine !== null ? $postedFine : (float) $info['remaining_fine'];
                $amount = $this->clampAmount($amount - $discount, (float) $info['balance']);
                $fine = $this->clampAmount($fine, (float) $info['remaining_fine']);
                $lines[] = [
                    'fee_category' => 'transport',
                    'student_transport_fee_id' => $transportId,
                    'student_fees_master_id' => 0,
                    'fee_groups_feetype_id' => 0,
                    'fee_group_name' => $info['fee_group_name'],
                    'fee_type_code' => $info['fee_type'],
                    'amount_balance' => $amount,
                    'fine_balance' => $fine,
                    'applied_fee_discount' => max(0, $discount),
                ];
            } else {
                $masterId = (int) ($input['student_fees_master_id'] ?? 0);
                $feetypeId = (int) ($input['fee_groups_feetype_id'] ?? 0);
                if ($masterId <= 0 || $feetypeId <= 0) {
                    throw new InvalidArgumentException('Fee line is required.');
                }
                $this->assertOwnsFeeMaster($masterId, $studentSessionId);
                $info = $this->collect->getBalance($masterId, $feetypeId);
                $amount = $postedFee !== null ? $postedFee : (float) $info['balance'];
                $fine = $postedFine !== null ? $postedFine : (float) $info['remaining_fine'];
                $amount = $this->clampAmount($amount - $discount, (float) $info['balance']);
                $fine = $this->clampAmount($fine, (float) $info['remaining_fine']);
                $lines[] = [
                    'fee_category' => 'fees',
                    'student_transport_fee_id' => 0,
                    'student_fees_master_id' => $masterId,
                    'fee_groups_feetype_id' => $feetypeId,
                    'fee_session_group_id' => $info['fee_session_group_id'],
                    'fee_group_name' => $info['fee_group_name'],
                    'fee_type_code' => $info['fee_code'] !== '' ? $info['fee_code'] : $info['fee_type'],
                    'amount_balance' => $amount,
                    'fine_balance' => $fine,
                    'applied_fee_discount' => max(0, $discount),
                ];
            }
        }

        if ($lines === []) {
            throw new InvalidArgumentException('Select at least one fee line to pay.');
        }

        $feeTotal = 0.0;
        $fineTotal = 0.0;
        $discountTotal = 0.0;
        foreach ($lines as $line) {
            $feeTotal += (float) $line['amount_balance'];
            $fineTotal += (float) $line['fine_balance'];
            $discountTotal += (float) $line['applied_fee_discount'];
        }

        $subtotal = round($feeTotal + $fineTotal, 2);
        $processingCharge = $this->gatewayProcessingCharge($method, $subtotal);
        $params = [
            'payment_type' => (string) $method->payment_type,
            'student_id' => (int) $student->id,
            'student_session_id' => $studentSessionId,
            'student_name' => trim(($student->firstname ?? '').' '.($student->middlename ?? '').' '.($student->lastname ?? '')),
            'lines' => $lines,
            'fee_total' => round($feeTotal, 2),
            'fine_total' => round($fineTotal, 2),
            'discount_total' => round($discountTotal, 2),
            'gateway_processing_charge' => $processingCharge,
            'total' => round($subtotal + $processingCharge, 2),
            'live_charge_deferred' => true,
        ];

        session()->put(self::SESSION_PARAMS_KEY, $params);

        return $params;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sessionParams(): ?array
    {
        $params = session(self::SESSION_PARAMS_KEY);

        return is_array($params) ? $params : null;
    }

    public function clearSessionParams(): void
    {
        session()->forget(self::SESSION_PARAMS_KEY);
    }

    protected function gatewayProcessingCharge(?object $method, float $amount): float
    {
        if ($method === null || $amount <= 0) {
            return 0.0;
        }

        $type = (string) ($method->charge_type ?? '');
        $value = (float) ($method->charge_value ?? 0);
        if ($type === 'percentage') {
            return round(($amount * $value) / 100, 2);
        }
        if ($type === 'fix') {
            return round($value, 2);
        }

        return 0.0;
    }

    protected function clampAmount(float $amount, float $max): float
    {
        if (! $this->allowPartialPayment()) {
            return max(0, round($max, 2));
        }

        return max(0, min(round($amount, 2), round($max, 2)));
    }

    protected function assertOwnsFeeMaster(int $masterId, int $studentSessionId): void
    {
        $row = DB::table('student_fees_master')->where('id', $masterId)->first();
        if (! $row || (int) $row->student_session_id !== $studentSessionId) {
            throw new RuntimeException('Unauthorized fee access.');
        }
    }

    protected function assertOwnsTransportFee(int $transportFeeId, int $studentSessionId): void
    {
        $row = DB::table('student_transport_fees')->where('id', $transportFeeId)->first();
        if (! $row || (int) $row->student_session_id !== $studentSessionId) {
            throw new RuntimeException('Unauthorized transport fee access.');
        }
    }
}
