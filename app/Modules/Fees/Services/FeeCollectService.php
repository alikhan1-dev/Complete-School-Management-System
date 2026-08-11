<?php

namespace App\Modules\Fees\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Models\StudentAppliedDiscount;
use App\Modules\Fees\Models\StudentFeesDeposite;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI Studentfee + Studentfeemaster_model deposit/ledger (fees category only for Slice 5 core).
 */
class FeeCollectService
{
    public const PAYMENT_MODES = [
        'Cash',
        'Cheque',
        'DD',
        'bank_transfer',
        'upi',
        'card',
    ];

    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * @return Collection<int, object>
     */
    public function searchStudents(?int $classId, ?int $sectionId, ?string $keyword = null): Collection
    {
        $sessionId = $this->currentSession->id();

        $query = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->select([
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.dob',
                'students.mobileno',
                'student_session.id as student_session_id',
                'classes.class',
                'sections.section',
            ])
            ->orderBy('students.admission_no');

        if ($classId) {
            $query->where('student_session.class_id', $classId);
        }
        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $term = '%'.trim($keyword).'%';
            $query->where(function ($q) use ($term) {
                $q->where('students.admission_no', 'like', $term)
                    ->orWhere('students.firstname', 'like', $term)
                    ->orWhere('students.middlename', 'like', $term)
                    ->orWhere('students.lastname', 'like', $term)
                    ->orWhere('students.father_name', 'like', $term)
                    ->orWhere('students.mobileno', 'like', $term);
            });
        }

        return $query->get();
    }

    public function findStudentBySession(int $studentSessionId): ?object
    {
        return DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('sessions', 'sessions.id', '=', 'student_session.session_id')
            ->where('student_session.id', $studentSessionId)
            ->select([
                'students.*',
                'student_session.id as student_session_id',
                'student_session.session_id',
                'student_session.class_id',
                'student_session.section_id',
                'classes.class',
                'sections.section',
                'sessions.session',
            ])
            ->first();
    }

    /**
     * Fee ledger lines for a student_session (assigned fee groups × fee types + deposits).
     *
     * @return list<object>
     */
    public function getStudentFees(int $studentSessionId): array
    {
        $rows = DB::table('student_fees_master')
            ->join('fee_session_groups', 'fee_session_groups.id', '=', 'student_fees_master.fee_session_group_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_session_groups.fee_groups_id')
            ->join('fee_groups_feetype', 'fee_groups_feetype.fee_session_group_id', '=', 'fee_session_groups.id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->leftJoin('student_fees_deposite', function ($join) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_deposite.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->where('student_fees_master.student_session_id', $studentSessionId)
            ->orderBy('student_fees_master.id')
            ->orderBy('fee_groups_feetype.id')
            ->select([
                'student_fees_master.id as student_fees_master_id',
                'student_fees_master.is_system',
                'student_fees_master.amount as student_fees_master_amount',
                'student_fees_master.fee_session_group_id',
                'fee_groups.name as fee_group_name',
                'fee_groups.is_system as fee_group_is_system',
                'fee_groups_feetype.id as fee_groups_feetype_id',
                'fee_groups_feetype.amount',
                'fee_groups_feetype.due_date',
                'fee_groups_feetype.fine_type',
                'fee_groups_feetype.fine_amount',
                'fee_groups_feetype.fine_percentage',
                'feetype.type as fee_type',
                'feetype.code as fee_code',
                DB::raw('IFNULL(student_fees_deposite.id, 0) as student_fees_deposite_id'),
                DB::raw('IFNULL(student_fees_deposite.amount_detail, 0) as amount_detail'),
            ])
            ->get();

        $ledger = [];
        foreach ($rows as $row) {
            $due = ((int) $row->is_system === 1)
                ? (float) $row->student_fees_master_amount
                : (float) $row->amount;

            $totals = $this->sumAmountDetail($row->amount_detail);
            $balance = round($due - ($totals['amount'] + $totals['amount_discount']), 2);
            $remainingFine = $this->remainingFine($row, $totals['amount_fine'], $balance);

            $ledger[] = (object) [
                'student_fees_master_id' => (int) $row->student_fees_master_id,
                'fee_session_group_id' => (int) $row->fee_session_group_id,
                'fee_groups_feetype_id' => (int) $row->fee_groups_feetype_id,
                'fee_group_name' => $row->fee_group_name,
                'fee_type' => $row->fee_type,
                'fee_code' => $row->fee_code,
                'due_date' => $row->due_date,
                'due_amount' => $due,
                'paid_amount' => $totals['amount'],
                'paid_discount' => $totals['amount_discount'],
                'paid_fine' => $totals['amount_fine'],
                'balance' => $balance,
                'remaining_fine' => $remainingFine,
                'student_fees_deposite_id' => (int) $row->student_fees_deposite_id,
                'payments' => $this->paymentsList($row->amount_detail, (int) $row->student_fees_deposite_id),
                'fine_type' => $row->fine_type,
            ];
        }

        return $ledger;
    }

    /**
     * Assigned discounts for the student session (for ledger display).
     *
     * @return Collection<int, object>
     */
    public function getStudentDiscounts(int $studentSessionId): Collection
    {
        return DB::table('student_fees_discounts')
            ->join('fees_discounts', 'fees_discounts.id', '=', 'student_fees_discounts.fees_discount_id')
            ->where('student_fees_discounts.student_session_id', $studentSessionId)
            ->select([
                'student_fees_discounts.id',
                'student_fees_discounts.status',
                'student_fees_discounts.payment_id',
                'fees_discounts.name',
                'fees_discounts.code',
                'fees_discounts.type',
                'fees_discounts.amount',
                'fees_discounts.percentage',
                'fees_discounts.expire_date',
                'fees_discounts.discount_limit',
            ])
            ->orderBy('student_fees_discounts.id')
            ->get();
    }

    /**
     * Discounts still available to apply on a collection (CI getDiscountNotApplied).
     *
     * @return list<object>
     */
    public function getAvailableDiscounts(int $studentSessionId): array
    {
        $today = date('Y-m-d');
        $rows = DB::table('student_fees_discounts')
            ->join('fees_discounts', 'fees_discounts.id', '=', 'student_fees_discounts.fees_discount_id')
            ->where('student_fees_discounts.student_session_id', $studentSessionId)
            ->where(function ($q) use ($today) {
                $q->whereNull('fees_discounts.expire_date')
                    ->orWhere('fees_discounts.expire_date', '0000-00-00')
                    ->orWhere('fees_discounts.expire_date', '>=', $today);
            })
            ->select([
                'student_fees_discounts.id',
                'fees_discounts.name',
                'fees_discounts.code',
                'fees_discounts.type',
                'fees_discounts.amount',
                'fees_discounts.percentage',
                'fees_discounts.discount_limit',
            ])
            ->get();

        $available = [];
        foreach ($rows as $row) {
            $applied = StudentAppliedDiscount::query()
                ->where('student_fees_discount_id', $row->id)
                ->count();
            $limit = (int) $row->discount_limit;
            if ($limit <= 0 || $applied < $limit) {
                $row->remaining_discount_limit = $limit > 0 ? ($limit - $applied) : null;
                $available[] = $row;
            }
        }

        return $available;
    }

    /**
     * @return array{due:float,balance:float,remaining_fine:float,student_fees:float,fee_group_name:string,fee_type:string,fee_code:string,fee_session_group_id:int}
     */
    public function getBalance(int $studentFeesMasterId, int $feeGroupsFeetypeId): array
    {
        $row = DB::table('student_fees_master')
            ->join('fee_session_groups', 'fee_session_groups.id', '=', 'student_fees_master.fee_session_group_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_session_groups.fee_groups_id')
            ->join('fee_groups_feetype', function ($join) {
                $join->on('fee_groups_feetype.fee_session_group_id', '=', 'fee_session_groups.id');
            })
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->leftJoin('student_fees_deposite', function ($join) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_deposite.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->where('student_fees_master.id', $studentFeesMasterId)
            ->where('fee_groups_feetype.id', $feeGroupsFeetypeId)
            ->select([
                'student_fees_master.is_system',
                'student_fees_master.amount as student_fees_master_amount',
                'student_fees_master.fee_session_group_id',
                'fee_groups.name as fee_group_name',
                'fee_groups_feetype.amount',
                'fee_groups_feetype.due_date',
                'fee_groups_feetype.fine_type',
                'fee_groups_feetype.fine_amount',
                'fee_groups_feetype.fine_percentage',
                'feetype.type as fee_type',
                'feetype.code as fee_code',
                DB::raw('IFNULL(student_fees_deposite.amount_detail, 0) as amount_detail'),
            ])
            ->first();

        if (! $row) {
            throw new InvalidArgumentException('Fee line not found.');
        }

        $due = ((int) $row->is_system === 1)
            ? (float) $row->student_fees_master_amount
            : (float) $row->amount;

        $totals = $this->sumAmountDetail($row->amount_detail);
        $balance = round($due - ($totals['amount'] + $totals['amount_discount']), 2);

        return [
            'due' => $due,
            'balance' => $balance,
            'remaining_fine' => $this->remainingFine($row, $totals['amount_fine'], $balance),
            'student_fees' => $due,
            'fee_group_name' => (string) $row->fee_group_name,
            'fee_type' => (string) $row->fee_type,
            'fee_code' => (string) $row->fee_code,
            'fee_session_group_id' => (int) $row->fee_session_group_id,
        ];
    }

    /**
     * @param  array{
     *     student_fees_master_id:int,
     *     fee_groups_feetype_id:int,
     *     student_session_id:int,
     *     date:string,
     *     amount:float|string,
     *     amount_discount:float|string,
     *     amount_fine:float|string,
     *     payment_mode:string,
     *     description?:string|null,
     *     discounts?:list<int|string>
     * }  $input
     * @return array{invoice_id:int,sub_invoice_id:int}
     */
    public function deposit(array $input, Staff $staff): array
    {
        $masterId = (int) $input['student_fees_master_id'];
        $feetypeId = (int) $input['fee_groups_feetype_id'];
        $amount = round((float) $input['amount'], 2);
        $discount = round((float) $input['amount_discount'], 2);
        $fine = round((float) $input['amount_fine'], 2);
        $paymentMode = (string) $input['payment_mode'];
        $date = (string) $input['date'];
        $description = (string) ($input['description'] ?? '');
        $discountIds = array_values(array_unique(array_map('intval', $input['discounts'] ?? [])));

        if ($amount < 0 || $discount < 0 || $fine < 0) {
            throw new InvalidArgumentException('Amounts cannot be negative.');
        }

        if (! in_array($paymentMode, self::PAYMENT_MODES, true)) {
            throw new InvalidArgumentException('Invalid payment mode.');
        }

        $balanceInfo = $this->getBalance($masterId, $feetypeId);
        $effective = round($amount + $discount, 2);
        if ($effective - $balanceInfo['balance'] > 0.001) {
            throw new InvalidArgumentException('Deposit exceeds remaining balance.');
        }

        $collectedBy = trim($staff->name.' '.($staff->surname ?? '')).'('.$staff->employee_id.')';

        $entry = [
            'amount' => $amount,
            'amount_discount' => $discount,
            'amount_fine' => $fine,
            'date' => $date,
            'description' => $description,
            'collected_by' => $collectedBy,
            'payment_mode' => $paymentMode,
            'received_by' => (int) $staff->id,
        ];

        return DB::transaction(function () use ($masterId, $feetypeId, $entry, $discountIds, $date) {
            $row = StudentFeesDeposite::query()
                ->where('student_fees_master_id', $masterId)
                ->where('fee_groups_feetype_id', $feetypeId)
                ->lockForUpdate()
                ->first();

            if ($row) {
                $detail = $row->decodedAmountDetail();
                $invNo = $detail === [] ? 1 : ((int) max(array_map('intval', array_keys($detail))) + 1);
                $entry['inv_no'] = $invNo;
                $detail[(string) $invNo] = $entry;
                $row->amount_detail = json_encode($detail);
                $row->save();

                $this->storeAppliedDiscounts((int) $row->id, $discountIds, $date, $invNo);

                return ['invoice_id' => (int) $row->id, 'sub_invoice_id' => $invNo];
            }

            $entry['inv_no'] = 1;
            $deposit = StudentFeesDeposite::query()->create([
                'student_fees_master_id' => $masterId,
                'fee_groups_feetype_id' => $feetypeId,
                'student_transport_fee_id' => null,
                'amount_detail' => json_encode(['1' => $entry]),
                'is_active' => 'no',
            ]);

            $this->storeAppliedDiscounts((int) $deposit->id, $discountIds, $date, 1);

            return ['invoice_id' => (int) $deposit->id, 'sub_invoice_id' => 1];
        });
    }

    public function deletePayment(int $invoiceId, int $subInvoice): void
    {
        DB::transaction(function () use ($invoiceId, $subInvoice) {
            $row = StudentFeesDeposite::query()->where('id', $invoiceId)->lockForUpdate()->first();
            if (! $row) {
                return;
            }

            $detail = $row->decodedAmountDetail();
            unset($detail[(string) $subInvoice], $detail[$subInvoice]);

            StudentAppliedDiscount::query()
                ->where('student_fees_deposite_id', $invoiceId)
                ->where('sub_invoice_id', $subInvoice)
                ->delete();

            if ($detail === []) {
                $row->delete();
            } else {
                $row->amount_detail = json_encode($detail);
                $row->save();
            }
        });
    }

    /**
     * Search payment by CI display id "invoice/sub" or invoice id alone.
     */
    public function findPayment(string $paymentId): ?object
    {
        $paymentId = trim($paymentId);
        if ($paymentId === '') {
            return null;
        }

        $invoiceId = 0;
        $subInvoice = null;
        if (str_contains($paymentId, '/')) {
            [$left, $right] = array_pad(explode('/', $paymentId, 2), 2, null);
            $invoiceId = (int) $left;
            $subInvoice = $right !== null && $right !== '' ? (int) $right : null;
        } else {
            $invoiceId = (int) $paymentId;
        }

        if ($invoiceId <= 0) {
            return null;
        }

        $deposit = StudentFeesDeposite::query()->find($invoiceId);
        if (! $deposit) {
            return null;
        }

        $detail = $deposit->decodedAmountDetail();
        if ($subInvoice !== null) {
            $key = (string) $subInvoice;
            if (! isset($detail[$key])) {
                return null;
            }
            $entry = $detail[$key];
        } else {
            $keys = array_keys($detail);
            if ($keys === []) {
                return null;
            }
            $key = (string) $keys[0];
            $entry = $detail[$key];
            $subInvoice = (int) $key;
        }

        $meta = DB::table('student_fees_deposite')
            ->join('student_fees_master', 'student_fees_master.id', '=', 'student_fees_deposite.student_fees_master_id')
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('fee_groups_feetype', 'fee_groups_feetype.id', '=', 'student_fees_deposite.fee_groups_feetype_id')
            ->leftJoin('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->leftJoin('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->where('student_fees_deposite.id', $invoiceId)
            ->select([
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.class',
                'sections.section',
                'student_session.id as student_session_id',
                'fee_groups.name as fee_group_name',
                'feetype.type as fee_type',
                'feetype.code as fee_code',
            ])
            ->first();

        return (object) [
            'invoice_id' => $invoiceId,
            'sub_invoice_id' => $subInvoice,
            'payment_id' => $invoiceId.'/'.$subInvoice,
            'entry' => $entry,
            'student' => $meta,
        ];
    }

    /**
     * @param  list<int>  $discountIds  student_fees_discounts.id
     */
    protected function storeAppliedDiscounts(int $depositeId, array $discountIds, string $date, int $subInvoice): void
    {
        foreach ($discountIds as $discountId) {
            if ($discountId <= 0) {
                continue;
            }
            StudentAppliedDiscount::query()->create([
                'student_fees_deposite_id' => $depositeId,
                'student_fees_discount_id' => $discountId,
                'invoice_id' => $depositeId,
                'sub_invoice_id' => $subInvoice,
                'date' => $date,
            ]);
        }
    }

    /**
     * @return array{amount:float,amount_discount:float,amount_fine:float}
     */
    protected function sumAmountDetail(mixed $raw): array
    {
        $detail = [];
        if (is_string($raw) && $raw !== '' && $raw !== '0') {
            $decoded = json_decode($raw, true);
            $detail = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $detail = $raw;
        }

        $amount = 0.0;
        $discount = 0.0;
        $fine = 0.0;
        foreach ($detail as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $amount += (float) ($entry['amount'] ?? 0);
            $discount += (float) ($entry['amount_discount'] ?? 0);
            $fine += (float) ($entry['amount_fine'] ?? 0);
        }

        return [
            'amount' => round($amount, 2),
            'amount_discount' => round($discount, 2),
            'amount_fine' => round($fine, 2),
        ];
    }

    /**
     * @return list<object>
     */
    protected function paymentsList(mixed $raw, int $depositeId): array
    {
        if ($depositeId <= 0) {
            return [];
        }

        $detail = [];
        if (is_string($raw) && $raw !== '' && $raw !== '0') {
            $decoded = json_decode($raw, true);
            $detail = is_array($decoded) ? $decoded : [];
        }

        $payments = [];
        foreach ($detail as $sub => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $payments[] = (object) [
                'invoice_id' => $depositeId,
                'sub_invoice_id' => (int) $sub,
                'payment_id' => $depositeId.'/'.$sub,
                'amount' => (float) ($entry['amount'] ?? 0),
                'amount_discount' => (float) ($entry['amount_discount'] ?? 0),
                'amount_fine' => (float) ($entry['amount_fine'] ?? 0),
                'date' => $entry['date'] ?? '',
                'payment_mode' => $entry['payment_mode'] ?? '',
                'description' => $entry['description'] ?? '',
                'collected_by' => $entry['collected_by'] ?? '',
            ];
        }

        return $payments;
    }

    protected function remainingFine(object $row, float $paidFine, float $balance): float
    {
        if ($balance <= 0) {
            return 0.0;
        }

        $dueDate = $row->due_date ?? null;
        if (empty($dueDate) || $dueDate === '0000-00-00') {
            return 0.0;
        }

        if (strtotime((string) $dueDate) >= strtotime(date('Y-m-d'))) {
            return 0.0;
        }

        $fineType = (string) ($row->fine_type ?? 'none');
        $dueFine = 0.0;
        if ($fineType === 'fix') {
            $dueFine = (float) ($row->fine_amount ?? 0);
        } elseif ($fineType === 'percentage') {
            $base = ((int) ($row->is_system ?? 0) === 1)
                ? (float) ($row->student_fees_master_amount ?? 0)
                : (float) ($row->amount ?? 0);
            $pct = (float) ($row->fine_percentage ?? 0);
            if ($pct <= 0 && isset($row->fine_amount)) {
                $dueFine = (float) $row->fine_amount;
            } else {
                $dueFine = round($base * $pct / 100, 2);
            }
        }
        // cumulative deferred (Slice 2)

        return max(0, round($dueFine - $paidFine, 2));
    }
}
