<?php

namespace App\Modules\Reports\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Services\FeeCollectService;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Financereports slice 1: hub helpers + balance fees report + fees statement
 * + balance fees statement (+ print) + daily collection (+ day drill-down).
 * Transport fee lines deferred (Fees module). Class-teacher scope deferred.
 */
class FinanceReportService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
        protected FeeCollectService $fees,
    ) {
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    public function currencySymbol(): string
    {
        return $this->school->currencySymbol();
    }

    public function formatAmount(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        return Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    public function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function fullName(object $student): string
    {
        $first = trim((string) ($student->firstname ?? ''));
        $middle = trim((string) ($student->middlename ?? ''));
        $last = trim((string) ($student->lastname ?? ''));
        $name = $this->settingOn('middlename')
            ? ($middle === '' ? $first : $first.' '.$middle)
            : $first;
        if ($this->settingOn('lastname') && $last !== '') {
            $name .= ' '.$last;
        }

        return $name;
    }

    /**
     * @return Collection<int, object>
     */
    public function classes(): Collection
    {
        return DB::table('classes')->orderBy('class')->get();
    }

    /**
     * CI Customlib::getPaymenttype.
     *
     * @return array<string, string>
     */
    public function paymentSearchTypes(): array
    {
        return [
            'all' => (string) __('system.all'),
            'balance' => (string) __('system.balance'),
            'paid' => (string) __('system.no').' '.(string) __('system.balance'),
        ];
    }

    /**
     * CI Financereports::studentacademicreport totals (transport deferred).
     *
     * @return list<object>
     */
    public function balanceFeesReport(?int $classId, ?int $sectionId, string $searchType): array
    {
        $students = $this->sessionStudents($classId, $sectionId);
        $rows = [];

        foreach ($students as $student) {
            $ledger = $this->fees->getStudentFees((int) $student->student_session_id);
            $totalfee = 0.0;
            $deposit = 0.0;
            $discount = 0.0;
            $fine = 0.0;

            foreach ($ledger as $line) {
                $totalfee += (float) $line->due_amount;
                $deposit += (float) $line->paid_amount;
                $discount += (float) $line->paid_discount;
                $fine += (float) $line->paid_fine;
            }

            $balance = $totalfee - ($deposit + $discount);
            $obj = (object) [
                'name' => $this->fullName($student),
                'class' => $student->class,
                'section' => $student->section,
                'admission_no' => $student->admission_no,
                'roll_no' => $student->roll_no ?? '',
                'father_name' => $student->father_name ?? '',
                'mobileno' => $student->mobileno ?? '',
                'totalfee' => round($totalfee, 2),
                'deposit' => round($deposit, 2),
                'discount' => round($discount, 2),
                'fine' => round($fine, 2),
                'balance' => round($balance, 2),
                'payment_mode' => empty($ledger) ? 0 : 'N/A',
            ];

            if ($searchType === 'all') {
                $rows[] = $obj;
            } elseif ($searchType === 'balance' && $obj->balance > 0) {
                $rows[] = $obj;
            } elseif ($searchType === 'paid' && $obj->balance <= 0) {
                $rows[] = $obj;
            }
        }

        return $rows;
    }

    /**
     * CI reportbyname / getStudentFeesByClassSectionStudent (single student typical).
     *
     * @return list<array<string, mixed>>
     */
    public function feesStatement(?int $classId, ?int $sectionId, ?int $studentId): array
    {
        $students = $this->sessionStudents($classId, $sectionId, $studentId);
        $out = [];

        foreach ($students as $student) {
            $ssid = (int) $student->student_session_id;
            $ledger = $this->fees->getStudentFees($ssid);
            $groups = [];
            foreach ($ledger as $line) {
                $gid = (int) $line->fee_session_group_id;
                if (! isset($groups[$gid])) {
                    $groups[$gid] = (object) [
                        'fee_session_group_id' => $gid,
                        'name' => $line->fee_group_name,
                        'fees' => [],
                    ];
                }
                $groups[$gid]->fees[] = $line;
            }

            $out[] = [
                'student_session_id' => $ssid,
                'student_id' => (int) $student->id,
                'firstname' => $student->firstname,
                'middlename' => $student->middlename,
                'lastname' => $student->lastname,
                'admission_no' => $student->admission_no,
                'class' => $student->class,
                'section' => $student->section,
                'father_name' => $student->father_name ?? '',
                'mobileno' => $student->mobileno ?? '',
                'roll_no' => $student->roll_no ?? '',
                'fees' => array_values($groups),
                'student_discount_fee' => $this->fees->getStudentDiscounts($ssid),
                'transport_fees' => [],
            ];
        }

        return $out;
    }

    /**
     * CI reportduefees / printreportduefees student_due_fee map.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dueFeesStatement(?int $classId, ?int $sectionId, ?string $asOfDate = null): array
    {
        $date = $asOfDate ?: now()->toDateString();
        $dues = $this->dueFeeTypesByDate($date, $classId, $sectionId);
        $studentsList = [];

        foreach ($dues as $feeDue) {
            $paid = $this->sumPaidAmountDiscount($feeDue->amount_detail);
            $feeAmount = (float) $feeDue->fee_amount;
            $masterAmount = (float) $feeDue->amount;
            $isSystem = (int) $feeDue->is_system === 1;

            if ($paid < $feeAmount || ($isSystem && $paid < $masterAmount)) {
                $ssid = (int) $feeDue->student_session_id;
                if (! isset($studentsList[$ssid])) {
                    $studentsList[$ssid] = [
                        'admission_no' => $feeDue->admission_no,
                        'class_id' => $feeDue->class_id,
                        'section_id' => $feeDue->section_id,
                        'student_id' => $feeDue->student_id,
                        'roll_no' => $feeDue->roll_no,
                        'admission_date' => $feeDue->admission_date,
                        'firstname' => $feeDue->firstname,
                        'middlename' => $feeDue->middlename,
                        'lastname' => $feeDue->lastname,
                        'father_name' => $feeDue->father_name,
                        'mobileno' => $feeDue->mobileno,
                        'email' => $feeDue->email,
                        'class' => $feeDue->class,
                        'section' => $feeDue->section,
                        'fee_groups_feetype_ids' => [],
                        'fees_list' => [],
                        'transport_fees' => [],
                    ];
                }
                $studentsList[$ssid]['fee_groups_feetype_ids'][] = (int) $feeDue->fee_groups_feetype_id;
            }
        }

        foreach ($studentsList as $ssid => $student) {
            $ids = array_values(array_unique($student['fee_groups_feetype_ids']));
            $studentsList[$ssid]['fees_list'] = $this->depositByFeeGroupFeeTypeArray($ssid, $ids);
            $studentsList[$ssid]['transport_fees'] = [];
        }

        return $studentsList;
    }

    /**
     * CI reportdailycollection fees_data keyed by unix midnight.
     *
     * @return array<int, array{amt: float, count: int, student_fees_deposite_ids: list<int>}>
     */
    public function dailyCollection(string $dateFrom, string $dateTo): array
    {
        $fromTs = strtotime($dateFrom);
        $toTs = strtotime($dateTo);
        $feesData = [];

        for ($i = $fromTs; $i <= $toTs; $i += 86400) {
            $feesData[$i] = [
                'amt' => 0.0,
                'count' => 0,
                'student_fees_deposite_ids' => [],
            ];
        }

        $rows = $this->currentSessionStudentFeeDeposits();
        foreach ($rows as $feeValue) {
            $detail = $this->decodeAmountDetail($feeValue->amount_detail);
            if ($detail === []) {
                continue;
            }
            foreach ($detail as $entry) {
                $payDate = (string) ($entry['date'] ?? '');
                if ($payDate === '') {
                    continue;
                }
                $dateTs = strtotime($payDate);
                if ($dateTs === false || $dateTs < $fromTs || $dateTs > $toTs) {
                    continue;
                }
                // Normalize to midnight key like CI strtotime of Y-m-d payment date.
                $dayKey = strtotime(date('Y-m-d', $dateTs));
                if (! isset($feesData[$dayKey])) {
                    $feesData[$dayKey] = [
                        'amt' => 0.0,
                        'count' => 0,
                        'student_fees_deposite_ids' => [],
                    ];
                }
                $feesData[$dayKey]['amt'] += (float) ($entry['amount'] ?? 0) + (float) ($entry['amount_fine'] ?? 0);
                $feesData[$dayKey]['count'] += 1;
                $feesData[$dayKey]['student_fees_deposite_ids'][] = (int) $feeValue->student_fees_deposite_id;
            }
        }

        ksort($feesData);

        return $feesData;
    }

    /**
     * CI getFeesDepositeByIdArray (transport deferred).
     *
     * @param  list<int|string>  $ids
     * @return list<object>
     */
    public function feesDepositeByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $sessionId = (int) $this->currentSession->id();

        return DB::table('student_fees_master')
            ->join('fee_session_groups', 'fee_session_groups.id', '=', 'student_fees_master.fee_session_group_id')
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_session_groups.fee_groups_id')
            ->join('fee_groups_feetype', 'fee_groups_feetype.fee_session_group_id', '=', 'fee_session_groups.id')
            ->join('student_fees_deposite', function ($join) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_deposite.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->where('student_session.session_id', $sessionId)
            ->where('fee_session_groups.session_id', $sessionId)
            ->whereIn('student_fees_deposite.id', $ids)
            ->select([
                'student_fees_master.*',
                'fee_session_groups.fee_groups_id',
                'fee_session_groups.session_id',
                'fee_groups.name',
                'fee_groups.is_system',
                'fee_groups_feetype.amount as fee_amount',
                'fee_groups_feetype.id as fee_groups_feetype_id',
                'student_fees_deposite.id as student_fees_deposite_id',
                'student_fees_deposite.amount_detail',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'classes.class',
                'sections.section',
            ])
            ->get()
            ->all();
    }

    /**
     * @return Collection<int, object>
     */
    protected function sessionStudents(?int $classId, ?int $sectionId, ?int $studentId = null): Collection
    {
        $sessionId = (int) $this->currentSession->id();
        $query = DB::table('students')
            ->join('student_session', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.admission_no')
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.mobileno',
                'student_session.id as student_session_id',
                'classes.class',
                'sections.section',
            ]);

        if ($classId) {
            $query->where('student_session.class_id', $classId);
        }
        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }
        if ($studentId) {
            $query->where('students.id', $studentId);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, object>
     */
    protected function dueFeeTypesByDate(string $date, ?int $classId, ?int $sectionId): Collection
    {
        $sessionId = (int) $this->currentSession->id();
        $query = DB::table('student_fees_master')
            ->join('fee_session_groups', 'fee_session_groups.id', '=', 'student_fees_master.fee_session_group_id')
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_session_groups.fee_groups_id')
            ->join('fee_groups_feetype', 'fee_groups_feetype.fee_session_group_id', '=', 'fee_session_groups.id')
            ->leftJoin('student_fees_deposite', function ($join) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_deposite.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->where('student_session.session_id', $sessionId)
            ->where('fee_session_groups.session_id', $sessionId)
            ->where('fee_groups_feetype.due_date', '<=', $date)
            ->select([
                'student_fees_master.id',
                'student_fees_master.student_session_id',
                'student_fees_master.amount',
                'student_fees_master.is_system',
                'fee_groups_feetype.amount as fee_amount',
                'fee_groups_feetype.id as fee_groups_feetype_id',
                'student_fees_deposite.amount_detail',
                'students.admission_no',
                'students.roll_no',
                'students.admission_date',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.mobileno',
                'students.email',
                'students.id as student_id',
                'classes.class',
                'classes.id as class_id',
                'sections.section',
                'sections.id as section_id',
            ]);

        if ($classId) {
            $query->where('student_session.class_id', $classId);
        }
        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }

        return $query->get();
    }

    /**
     * @param  list<int>  $feeTypeIds
     * @return list<object>
     */
    protected function depositByFeeGroupFeeTypeArray(int $studentSessionId, array $feeTypeIds): array
    {
        if ($feeTypeIds === []) {
            return [];
        }

        return DB::table('fee_groups_feetype')
            ->join('student_fees_master', 'student_fees_master.fee_session_group_id', '=', 'fee_groups_feetype.fee_session_group_id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->leftJoin('student_fees_deposite', function ($join) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_deposite.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->whereIn('fee_groups_feetype.id', $feeTypeIds)
            ->where('student_fees_master.student_session_id', $studentSessionId)
            ->orderBy('fee_groups_feetype.due_date')
            ->select([
                'fee_groups_feetype.*',
                'student_fees_master.student_session_id',
                'student_fees_master.amount as previous_amount',
                'student_fees_master.is_system',
                'student_fees_master.id as student_fees_master_id',
                'feetype.code',
                'feetype.type',
                DB::raw('IFNULL(student_fees_deposite.id, 0) as student_fees_deposite_id'),
                'student_fees_deposite.amount_detail',
                'fee_groups.name as fee_group_name',
            ])
            ->get()
            ->all();
    }

    /**
     * @return Collection<int, object>
     */
    protected function currentSessionStudentFeeDeposits(): Collection
    {
        $sessionId = (int) $this->currentSession->id();

        return DB::table('student_fees_master')
            ->join('fee_session_groups', 'fee_session_groups.id', '=', 'student_fees_master.fee_session_group_id')
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_session_groups.fee_groups_id')
            ->join('fee_groups_feetype', 'fee_groups_feetype.fee_session_group_id', '=', 'fee_session_groups.id')
            ->leftJoin('student_fees_deposite', function ($join) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_deposite.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->where('student_session.session_id', $sessionId)
            ->where('fee_session_groups.session_id', $sessionId)
            ->whereNotNull('student_fees_deposite.id')
            ->select([
                'student_fees_deposite.id as student_fees_deposite_id',
                'student_fees_deposite.amount_detail',
            ])
            ->get();
    }

    protected function sumPaidAmountDiscount(mixed $raw): float
    {
        $sum = 0.0;
        foreach ($this->decodeAmountDetail($raw) as $entry) {
            $sum += (float) ($entry['amount'] ?? 0) + (float) ($entry['amount_discount'] ?? 0);
        }

        return $sum;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function decodeAmountDetail(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '' && $raw !== '0') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? array_values($decoded) : [];
        }

        return is_array($raw) ? array_values($raw) : [];
    }
}
