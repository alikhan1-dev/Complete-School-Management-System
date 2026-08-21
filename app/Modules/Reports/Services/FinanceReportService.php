<?php

namespace App\Modules\Reports\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Services\FeeCollectService;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Financereports: hub + fee reports + remark/payroll/admission + income/expense list/group/balance.
 * Transport fee lines deferred. Class-teacher scope deferred.
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
     * CI Balancefees::index / due_fees_report (transport + class-teacher deferred).
     * Accrues overdue grand fine separately from paid fine; row visibility is view-side
     * (balance + grand_fine > 0) but we still return search_type-filtered students.
     *
     * @return list<object>
     */
    public function dueFeesReport(?int $classId, ?int $sectionId, string $searchType = 'all'): array
    {
        $students = $this->sessionStudents($classId, $sectionId);
        $today = now()->toDateString();
        $rows = [];

        foreach ($students as $student) {
            $lines = $this->dueFeesFeeLines((int) $student->student_session_id);
            $totalfee = 0;
            $deposit = 0;
            $discount = 0;
            $fine = 0;
            $grandFine = 0.0;
            $dueDate = 0;
            $totalAmount = 0.0;

            foreach ($lines as $line) {
                $amount = (float) $line->amount;
                $totalfee += $amount;

                foreach ($this->decodeAmountDetail($line->amount_detail) as $entry) {
                    // CI Balancefees casts paid amounts to int while summing.
                    $deposit = (int) $deposit + (int) ($entry['amount'] ?? 0);
                    $fine = (int) $fine + (int) ($entry['amount_fine'] ?? 0);
                    $discount = (int) $discount + (int) ($entry['amount_discount'] ?? 0);
                }

                if (! empty($line->due_date) && $line->due_date !== '0000-00-00') {
                    $dueDate = $line->due_date;
                    $totalAmount += $amount;
                    if (strtotime((string) $line->due_date) < strtotime($today)) {
                        $fineType = (string) ($line->fine_type ?? '');
                        if ($fineType === 'cumulative') {
                            $dueDays = (int) (new \DateTime((string) $line->due_date))
                                ->diff(new \DateTime($today))
                                ->format('%a');
                            $grandFine += $this->getCumulativeFineAmount((int) $line->fee_groups_feetype_id, $dueDays);
                        } elseif ($fineType === 'fix' || $fineType === 'percentage') {
                            // CI uses stored fine_amount (no runtime % recalculation).
                            $grandFine += (float) ($line->fine_amount ?? 0);
                        }
                    }
                }
            }

            $balance = $totalfee - ($deposit + $discount);
            $obj = (object) [
                'id' => (int) $student->id,
                'name' => $this->fullName($student),
                'class' => $student->class,
                'section' => $student->section,
                'admission_no' => $student->admission_no,
                'roll_no' => $student->roll_no ?? '',
                'father_name' => $student->father_name ?? '',
                'mobileno' => $student->mobileno ?? '',
                'due_date' => $dueDate,
                'grand_fine_amount' => $grandFine,
                'total_amount' => $totalAmount,
                'totalfee' => $totalfee,
                'payment_mode' => $lines === [] ? 0 : 'N/A',
                'deposit' => $deposit,
                'fine' => $fine,
                'discount' => $discount,
                'balance' => $balance,
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
     * CI Customlib::get_cumulative_fine_amount.
     */
    public function getCumulativeFineAmount(int $feeGroupsFeetypeId, int $dueDays): float
    {
        $tiers = DB::table('cumulative_fine')
            ->leftJoin('fee_groups_feetype', 'fee_groups_feetype.id', '=', 'cumulative_fine.fee_groups_feetype_id')
            ->where('cumulative_fine.fee_groups_feetype_id', $feeGroupsFeetypeId)
            ->select([
                'cumulative_fine.overdue_day',
                'cumulative_fine.fine_amount',
                'fee_groups_feetype.fine_per_day',
            ])
            ->get()
            ->all();

        if ($tiers === []) {
            return 0.0;
        }

        $dueFineAmount = 0.0;
        foreach ($tiers as $key => $value) {
            $overdueDay = (int) $value->overdue_day;
            $tierFine = (float) $value->fine_amount;
            if ((int) ($value->fine_per_day ?? 0) === 1) {
                if ($dueDays > $overdueDay) {
                    $next = $tiers[$key + 1] ?? null;
                    if ($next !== null && isset($next->overdue_day)) {
                        if ((int) $next->overdue_day < $dueDays) {
                            $day = (int) $next->overdue_day - $overdueDay;
                            $dueFineAmount += $tierFine * $day;
                        } else {
                            $dueFineAmount += $tierFine * ($dueDays - $overdueDay);
                        }
                    } else {
                        $dueFineAmount += $tierFine * ($dueDays - $overdueDay);
                    }
                }
            } elseif ($dueDays > $overdueDay) {
                // Non per-day: later matching tiers overwrite (CI assign, not add).
                $dueFineAmount = $tierFine;
            }
        }

        return (float) $dueFineAmount;
    }

    /**
     * Academic fee lines for Balancefees (transport deferred).
     *
     * @return list<object>
     */
    protected function dueFeesFeeLines(int $studentSessionId): array
    {
        return DB::table('student_fees_master')
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
            ->orderByDesc('fee_groups_feetype.due_date')
            ->select([
                'student_fees_master.is_system',
                'student_fees_master.amount as master_amount',
                'fee_groups_feetype.id as fee_groups_feetype_id',
                'fee_groups_feetype.amount as feetype_amount',
                'fee_groups_feetype.due_date',
                'fee_groups_feetype.fine_type',
                'fee_groups_feetype.fine_amount',
                DB::raw('IFNULL(student_fees_deposite.amount_detail, 0) as amount_detail'),
            ])
            ->get()
            ->map(function ($row) {
                $amount = ((int) $row->is_system !== 0)
                    ? (float) $row->master_amount
                    : (float) $row->feetype_amount;

                return (object) [
                    'fee_groups_feetype_id' => (int) $row->fee_groups_feetype_id,
                    'amount' => $amount,
                    'due_date' => $row->due_date,
                    'fine_type' => $row->fine_type,
                    'fine_amount' => $row->fine_amount,
                    'amount_detail' => $row->amount_detail,
                ];
            })
            ->all();
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
     * CI Customlib::get_searchtype (includes empty Select).
     *
     * @return array<string, string>
     */
    public function searchDurationTypes(): array
    {
        return [
            '' => (string) __('system.select'),
            'today' => (string) __('system.today'),
            'this_week' => (string) __('system.this_week'),
            'last_week' => (string) __('system.last_week'),
            'this_month' => (string) __('system.this_month'),
            'last_month' => (string) __('system.last_month'),
            'last_3_month' => (string) __('system.last_3_month'),
            'last_6_month' => (string) __('system.last_6_month'),
            'last_12_month' => (string) __('system.last_12_month'),
            'this_year' => (string) __('system.this_year'),
            'last_year' => (string) __('system.last_year'),
            'period' => (string) __('system.period'),
        ];
    }

    /**
     * CI Customlib::get_groupby.
     *
     * @return array<string, string>
     */
    public function collectionGroupBy(): array
    {
        return [
            '' => (string) __('system.select'),
            'class' => (string) __('system.class'),
            'collection' => (string) __('system.collect'),
            'mode' => (string) __('system.mode'),
        ];
    }

    /**
     * @return array{from: string, to: string}
     */
    public function dateRange(string $searchType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();

        return match ($searchType) {
            'today' => ['from' => $now->toDateString(), 'to' => $now->toDateString()],
            'this_week' => [
                'from' => $now->copy()->startOfWeek()->toDateString(),
                'to' => $now->copy()->endOfWeek()->toDateString(),
            ],
            'last_week' => [
                'from' => $now->copy()->startOfWeek()->subWeek()->toDateString(),
                'to' => $now->copy()->startOfWeek()->subWeek()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                'from' => $now->copy()->startOfMonth()->toDateString(),
                'to' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                'from' => $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'to' => $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'last_3_month' => [
                'from' => $now->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(),
                'to' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_6_month' => [
                'from' => $now->copy()->subMonthsNoOverflow(5)->startOfMonth()->toDateString(),
                'to' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_12_month' => [
                'from' => $now->copy()->subMonthsNoOverflow(11)->startOfMonth()->toDateString(),
                'to' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'this_year' => [
                'from' => $now->copy()->startOfYear()->toDateString(),
                'to' => $now->copy()->endOfYear()->toDateString(),
            ],
            'last_year' => [
                'from' => $now->copy()->subYear()->startOfYear()->toDateString(),
                'to' => $now->copy()->subYear()->endOfYear()->toDateString(),
            ],
            'period' => [
                'from' => $this->parseDate($dateFrom) ?: $now->toDateString(),
                'to' => $this->parseDate($dateTo) ?: $now->toDateString(),
            ],
            default => [
                'from' => $now->copy()->startOfYear()->toDateString(),
                'to' => $now->copy()->endOfYear()->toDateString(),
            ],
        };
    }

    /**
     * CI Studentfeemaster_model::get_feesreceived_by (superadmin hide deferred).
     *
     * @return array<int, string>
     */
    public function feesCollectors(): array
    {
        $rows = DB::table('staff')
            ->join('staff_roles', 'staff.id', '=', 'staff_roles.staff_id')
            ->where('staff.is_active', 1)
            ->select([
                'staff.id',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
            ])
            ->orderBy('staff.name')
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[(int) $row->id] = trim($row->name.' '.($row->surname ?? '')).' ('.$row->employee_id.')';
        }

        return $data;
    }

    /**
     * @return Collection<int, object>
     */
    public function feeTypes(): Collection
    {
        return DB::table('feetype')->orderBy('type')->get(['id', 'type', 'code']);
    }

    /**
     * CI getFeeCollectionReport (transport deferred).
     *
     * @return list<array<string, mixed>>
     */
    public function feeCollectionReport(
        string $startDate,
        string $endDate,
        ?int $feetypeId = null,
        ?int $receivedBy = null,
        ?int $classId = null,
        ?int $sectionId = null
    ): array {
        $sessionId = (int) $this->currentSession->id();
        $query = DB::table('student_fees_deposite')
            ->join('fee_groups_feetype', 'fee_groups_feetype.id', '=', 'student_fees_deposite.fee_groups_feetype_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->join('student_fees_master', 'student_fees_master.id', '=', 'student_fees_deposite.student_fees_master_id')
            ->leftJoin('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('fee_groups_feetype.session_id', $sessionId)
            ->where('student_session.session_id', $sessionId)
            ->select([
                'student_fees_deposite.id',
                'student_fees_deposite.student_fees_master_id',
                'student_fees_deposite.fee_groups_feetype_id',
                'student_fees_deposite.amount_detail',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'student_session.class_id',
                'classes.class',
                'sections.section',
                'student_session.section_id',
                'student_session.student_id',
                'fee_groups.name',
                'feetype.type',
                'feetype.code',
                'feetype.is_system',
                'student_fees_master.student_session_id',
            ]);

        if ($feetypeId) {
            $query->where('fee_groups_feetype.feetype_id', $feetypeId);
        }
        if ($classId) {
            $query->where('student_session.class_id', $classId);
        }
        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }

        $deposits = $query->get();
        $staffMap = $this->staffNameMap();

        return $this->expandDepositPayments(
            $deposits,
            $startDate,
            $endDate,
            $receivedBy,
            onlineOnly: false,
            staffMap: $staffMap
        );
    }

    /**
     * CI getOnlineFeeCollectionReport (transport deferred).
     *
     * @return list<array<string, mixed>>
     */
    public function onlineFeeCollectionReport(string $startDate, string $endDate): array
    {
        $sessionId = (int) $this->currentSession->id();
        $deposits = DB::table('student_fees_deposite')
            ->join('fee_groups_feetype', 'fee_groups_feetype.id', '=', 'student_fees_deposite.fee_groups_feetype_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->join('student_fees_master', 'student_fees_master.id', '=', 'student_fees_deposite.student_fees_master_id')
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('student_session.session_id', $sessionId)
            ->orderBy('student_fees_deposite.id')
            ->select([
                'student_fees_deposite.id',
                'student_fees_deposite.student_fees_master_id',
                'student_fees_deposite.fee_groups_feetype_id',
                'student_fees_deposite.amount_detail',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'student_session.class_id',
                'classes.class',
                'sections.section',
                'student_session.section_id',
                'student_session.student_id',
                'fee_groups.name',
                'feetype.type',
                'feetype.code',
                'feetype.is_system',
                'student_fees_master.student_session_id',
            ])
            ->get();

        return $this->expandDepositPayments(
            $deposits,
            $startDate,
            $endDate,
            null,
            onlineOnly: true,
            staffMap: $this->staffNameMap()
        );
    }

    /**
     * CI controller grouping for collection_report results.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<list<array<string, mixed>>>
     */
    public function groupCollectionRows(array $rows, string $group): array
    {
        if ($group === '') {
            $out = [];
            foreach ($rows as $row) {
                $out[] = [$row];
            }

            return $out;
        }

        $groupBy = match ($group) {
            'class' => 'class_id',
            'collection' => 'received_by',
            'mode' => 'payment_mode',
            default => null,
        };
        if ($groupBy === null) {
            return array_map(fn ($row) => [$row], $rows);
        }

        $collection = [];
        foreach ($rows as $row) {
            $key = (string) ($row[$groupBy] ?? '');
            $collection[$key][] = $row;
        }

        return array_values($collection);
    }

    /**
     * CI Financereports::duefeesremark / printduefeesremark (transport deferred).
     * Uses due_date < asOfDate (CI strict less-than, not <= used by dueFeesStatement).
     *
     * @return array<int, array<string, mixed>>
     */
    public function dueFeesWithRemark(int $classId, int $sectionId, ?string $asOfDate = null): array
    {
        $date = $asOfDate ?: now()->toDateString();
        $sessionId = (int) $this->currentSession->id();

        $dues = DB::table('student_fees_master')
            ->join('fee_session_groups', 'fee_session_groups.id', '=', 'student_fees_master.fee_session_group_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_session_groups.fee_groups_id')
            ->join('fee_groups_feetype', 'fee_groups_feetype.fee_session_group_id', '=', 'student_fees_master.fee_session_group_id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->leftJoin('student_fees_deposite', function ($join) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_deposite.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->where('students.is_active', 'yes')
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('fee_groups_feetype.due_date', '<', $date)
            ->orderBy('student_fees_master.id')
            ->select([
                'student_fees_master.amount as previous_balance_amount',
                'student_fees_deposite.amount_detail',
                'fee_groups_feetype.amount',
                'fee_groups.is_system',
                'fee_groups.name as fee_group',
                'feetype.type as fee_type',
                'feetype.code as fee_code',
                'student_session.id as student_session_id',
                'students.id',
                'classes.class',
                'sections.id as section_id',
                'sections.section',
                'students.admission_no',
                'students.roll_no',
                'students.admission_date',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.image',
                'students.mobileno',
                'students.email',
                'students.state',
                'students.city',
                'students.pincode',
                'students.religion',
                'students.dob',
                'students.current_address',
                'students.permanent_address',
                DB::raw('IFNULL(students.category_id, 0) as category_id'),
                DB::raw('IFNULL(categories.category, "") as category'),
                'students.adhar_no',
                'students.samagra_id',
                'students.bank_account_no',
                'students.bank_name',
                'students.ifsc_code',
                'students.guardian_name',
                'students.guardian_relation',
                'students.guardian_phone',
                'students.guardian_address',
                'students.is_active',
                'students.father_name',
                'students.rte',
                'students.gender',
            ])
            ->get();

        $students = [];
        foreach ($dues as $row) {
            $isSystem = (int) $row->is_system === 1;
            $amtDue = $isSystem
                ? (float) $row->previous_balance_amount
                : (float) $row->amount;
            $paid = $this->depositBreakdown($row->amount_detail);

            if ($amtDue <= ($paid['amount'] + $paid['discount'])) {
                continue;
            }

            $ssid = (int) $row->student_session_id;
            if (! isset($students[$ssid])) {
                $students[$ssid] = [
                    'id' => (int) $row->id,
                    'student_session_id' => $ssid,
                    'class' => $row->class,
                    'section_id' => (int) $row->section_id,
                    'section' => $row->section,
                    'admission_no' => $row->admission_no,
                    'roll_no' => $row->roll_no,
                    'admission_date' => $row->admission_date,
                    'firstname' => $row->firstname,
                    'middlename' => $row->middlename,
                    'lastname' => $row->lastname,
                    'image' => $row->image,
                    'mobileno' => $row->mobileno,
                    'email' => $row->email,
                    'state' => $row->state,
                    'city' => $row->city,
                    'pincode' => $row->pincode,
                    'religion' => $row->religion,
                    'dob' => $row->dob,
                    'current_address' => $row->current_address,
                    'permanent_address' => $row->permanent_address,
                    'category_id' => (int) $row->category_id,
                    'category' => $row->category,
                    'adhar_no' => $row->adhar_no,
                    'samagra_id' => $row->samagra_id,
                    'bank_account_no' => $row->bank_account_no,
                    'bank_name' => $row->bank_name,
                    'ifsc_code' => $row->ifsc_code,
                    'guardian_name' => $row->guardian_name,
                    'guardian_relation' => $row->guardian_relation,
                    'guardian_phone' => $row->guardian_phone,
                    'guardian_address' => $row->guardian_address,
                    'is_active' => $row->is_active,
                    'father_name' => $row->father_name,
                    'rte' => $row->rte,
                    'gender' => $row->gender,
                    'fees' => [],
                ];
            }

            $students[$ssid]['fees'][] = [
                'is_system' => $isSystem ? 1 : 0,
                'amount' => $amtDue,
                'amount_deposite' => $paid['amount'],
                'amount_discount' => $paid['discount'],
                'amount_fine' => $paid['fine'],
                'fee_group' => $row->fee_group,
                'fee_type' => $row->fee_type,
                'fee_code' => $row->fee_code,
            ];
        }

        return $students;
    }

    /**
     * CI Payroll_model::getbetweenpayrollReport (superadmin hide deferred).
     *
     * @return list<object>
     */
    public function betweenPayrollReport(string $startDate, string $endDate): array
    {
        return DB::table('staff')
            ->join('staff_payslip', 'staff_payslip.staff_id', '=', 'staff.id')
            ->leftJoin('staff_designation', 'staff.designation', '=', 'staff_designation.id')
            ->leftJoin('department', 'staff.department', '=', 'department.id')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->whereRaw("DATE_FORMAT(staff_payslip.payment_date,'%Y-%m-%d') BETWEEN ? AND ?", [$startDate, $endDate])
            ->select([
                'staff.id',
                'staff.employee_id',
                'staff.name',
                'roles.name as user_type',
                'staff.surname',
                'staff_designation.designation',
                'department.department_name as department',
                'staff_payslip.*',
            ])
            ->get()
            ->all();
    }

    /**
     * CI Onlinestudent_model::getOnlineAdmissionFeeCollectionReport.
     *
     * @return list<object>
     */
    public function onlineAdmissionFeeCollectionReport(string $startDate, string $endDate): array
    {
        return DB::table('online_admissions')
            ->join('online_admission_payment', 'online_admissions.id', '=', 'online_admission_payment.online_admission_id')
            ->leftJoin('class_sections', 'class_sections.id', '=', 'online_admissions.class_section_id')
            ->leftJoin('classes', 'class_sections.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'sections.id', '=', 'class_sections.section_id')
            ->whereRaw("DATE_FORMAT(online_admission_payment.date, '%Y-%m-%d') >= ?", [$startDate])
            ->whereRaw("DATE_FORMAT(online_admission_payment.date, '%Y-%m-%d') <= ?", [$endDate])
            ->select([
                'online_admissions.id',
                'online_admissions.reference_no',
                'online_admissions.firstname',
                'online_admissions.middlename',
                'online_admissions.lastname',
                'online_admissions.admission_no',
                'online_admissions.email',
                'online_admissions.mobileno',
                'classes.class',
                'sections.section',
                'online_admission_payment.payment_mode',
                'online_admission_payment.transaction_id',
                'online_admission_payment.date',
                'online_admission_payment.paid_amount',
            ])
            ->get()
            ->all();
    }

    /**
     * CI Income_model::incomeexpensebalancereport (UNION ordered by date ASC).
     *
     * @return list<array{date: string, name: string, category: string, note: string, amount: float, source: string}>
     */
    public function incomeExpenseBalanceReport(string $startDate, string $endDate): array
    {
        $expenses = DB::table('expenses')
            ->join('expense_head', 'expense_head.id', '=', 'expenses.exp_head_id')
            ->whereRaw("DATE_FORMAT(expenses.date, '%Y-%m-%d') >= ?", [$startDate])
            ->whereRaw("DATE_FORMAT(expenses.date, '%Y-%m-%d') <= ?", [$endDate])
            ->select([
                'expenses.date',
                'expenses.name',
                'expenses.note',
                'expenses.amount',
                DB::raw("'expenses' AS source"),
                'expense_head.exp_category AS category',
            ]);

        $income = DB::table('income')
            ->join('income_head', 'income_head.id', '=', 'income.income_head_id')
            ->whereRaw("DATE_FORMAT(income.date, '%Y-%m-%d') >= ?", [$startDate])
            ->whereRaw("DATE_FORMAT(income.date, '%Y-%m-%d') <= ?", [$endDate])
            ->select([
                'income.date',
                'income.name',
                'income.note',
                'income.amount',
                DB::raw("'income' AS source"),
                'income_head.income_category AS category',
            ]);

        return $expenses
            ->union($income)
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'name' => (string) $row->name,
                'category' => (string) $row->category,
                'note' => (string) ($row->note ?? ''),
                'amount' => (float) $row->amount,
                'source' => (string) $row->source,
            ])
            ->all();
    }

    /**
     * CI Income_model::search date range (report list; form-POST instead of DataTables).
     *
     * @return list<object>
     */
    public function incomeReport(string $startDate, string $endDate): array
    {
        return DB::table('income')
            ->join('income_head', 'income.income_head_id', '=', 'income_head.id')
            ->where('income.date', '>=', $startDate)
            ->where('income.date', '<=', $endDate)
            ->orderBy('income.date')
            ->orderBy('income.id')
            ->select([
                'income.id',
                'income.date',
                'income.name',
                'income.invoice_no',
                'income.amount',
                'income_head.income_category',
            ])
            ->get()
            ->all();
    }

    /**
     * CI Expense_model::search date range (report list; form-POST instead of DataTables).
     *
     * @return list<object>
     */
    public function expenseReport(string $startDate, string $endDate): array
    {
        return DB::table('expenses')
            ->join('expense_head', 'expenses.exp_head_id', '=', 'expense_head.id')
            ->where('expenses.date', '>=', $startDate)
            ->where('expenses.date', '<=', $endDate)
            ->orderBy('expenses.date')
            ->orderBy('expenses.id')
            ->select([
                'expenses.id',
                'expenses.date',
                'expenses.name',
                'expenses.invoice_no',
                'expenses.amount',
                'expense_head.exp_category',
            ])
            ->get()
            ->all();
    }

    /**
     * @return Collection<int, object>
     */
    public function incomeHeads(): Collection
    {
        return DB::table('income_head')->orderBy('income_category')->get(['id', 'income_category']);
    }

    /**
     * @return Collection<int, object>
     */
    public function expenseHeads(): Collection
    {
        return DB::table('expense_head')->orderBy('exp_category')->get(['id', 'exp_category']);
    }

    /**
     * CI Income_model::searchincomegroup (+ DT blank category / subtotals built in PHP).
     *
     * @return list<array{type: string, category?: string, id?: int, name?: string, date?: string, invoice_no?: string, amount?: float}>
     */
    public function incomeGroupReport(string $startDate, string $endDate, ?int $headId = null): array
    {
        $query = DB::table('income')
            ->join('income_head', 'income.income_head_id', '=', 'income_head.id')
            ->where('income.date', '>=', $startDate)
            ->where('income.date', '<=', $endDate)
            ->orderByDesc('income.income_head_id')
            ->orderBy('income.date')
            ->orderBy('income.id')
            ->select([
                'income.id',
                'income.name',
                'income.invoice_no',
                'income.date',
                'income.amount',
                'income_head.income_category as category',
                'income_head.id as head_id',
            ]);

        if ($headId) {
            $query->where('income.income_head_id', $headId);
        }

        return $this->buildGroupedFinanceRows($query->get()->all());
    }

    /**
     * CI Expensehead_model::searchexpensegroup (+ blank category / subtotals).
     *
     * @return list<array{type: string, category?: string, id?: int, name?: string, date?: string, invoice_no?: string, amount?: float}>
     */
    public function expenseGroupReport(string $startDate, string $endDate, ?int $headId = null): array
    {
        $query = DB::table('expenses')
            ->join('expense_head', 'expenses.exp_head_id', '=', 'expense_head.id')
            ->where('expenses.date', '>=', $startDate)
            ->where('expenses.date', '<=', $endDate)
            ->orderByDesc('expenses.exp_head_id')
            ->orderBy('expenses.date')
            ->orderBy('expenses.id')
            ->select([
                'expenses.id',
                'expenses.name',
                'expenses.invoice_no',
                'expenses.date',
                'expenses.amount',
                'expense_head.exp_category as category',
                'expenses.exp_head_id as head_id',
            ]);

        if ($headId) {
            $query->where('expenses.exp_head_id', $headId);
        }

        return $this->buildGroupedFinanceRows($query->get()->all());
    }

    /**
     * Port CI dtincomegroupreport / dtexpensegroupreport row builder.
     *
     * @param  list<object>  $rows
     * @return list<array{type: string, category?: string, id?: int, name?: string, date?: string, invoice_no?: string, amount?: float}>
     */
    protected function buildGroupedFinanceRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $byHead = [];
        foreach ($rows as $row) {
            $byHead[(int) $row->head_id][] = $row;
        }

        $out = [];
        $prevHeadId = 0;
        $count = 0;
        $grand = 0.0;

        foreach ($rows as $value) {
            $headId = (int) $value->head_id;
            $amount = (float) $value->amount;
            $grand += $amount;

            if ($prevHeadId === $headId) {
                $category = '';
                $count++;
            } else {
                $category = (string) $value->category;
                $count = 0;
            }

            $out[] = [
                'type' => 'row',
                'category' => $category,
                'id' => (int) $value->id,
                'name' => (string) $value->name,
                'date' => (string) $value->date,
                'invoice_no' => (string) $value->invoice_no,
                'amount' => $amount,
            ];

            $prevHeadId = $headId;

            if ($count === (count($byHead[$headId]) - 1)) {
                $sub = 0.0;
                foreach ($byHead[$headId] as $headRow) {
                    $sub += (float) $headRow->amount;
                }
                $out[] = [
                    'type' => 'subtotal',
                    'amount' => $sub,
                ];
            }
        }

        $out[] = [
            'type' => 'total',
            'amount' => $grand,
        ];

        return $out;
    }

    /**
     * @return array{name: string, class: string}|null
     */
    public function classLabel(int $classId): ?array
    {
        $row = DB::table('classes')->where('id', $classId)->first();

        return $row ? ['name' => (string) $row->class, 'class' => (string) $row->class] : null;
    }

    /**
     * @return array{section: string}|null
     */
    public function sectionLabel(int $sectionId): ?array
    {
        $row = DB::table('sections')->where('id', $sectionId)->first();

        return $row ? ['section' => (string) $row->section] : null;
    }

    /**
     * @return array{amount: float, discount: float, fine: float}
     */
    protected function depositBreakdown(mixed $raw): array
    {
        $amount = 0.0;
        $discount = 0.0;
        $fine = 0.0;
        foreach ($this->decodeAmountDetail($raw) as $entry) {
            $amount += (float) ($entry['amount'] ?? 0);
            $discount += (float) ($entry['amount_discount'] ?? 0);
            $fine += (float) ($entry['amount_fine'] ?? 0);
        }

        return ['amount' => $amount, 'discount' => $discount, 'fine' => $fine];
    }

    /**
     * @param  Collection<int, object>  $deposits
     * @param  array<int, array{name: string, employee_id: string, id: int}>  $staffMap
     * @return list<array<string, mixed>>
     */
    protected function expandDepositPayments(
        Collection $deposits,
        string $startDate,
        string $endDate,
        ?int $receivedBy,
        bool $onlineOnly,
        array $staffMap
    ): array {
        $st = strtotime($startDate);
        $ed = strtotime($endDate);
        $offlineModes = ['Cheque', 'Cash', 'DD'];
        $return = [];

        foreach ($deposits as $value) {
            $payments = $this->paymentsInDateRange($value->amount_detail, $st, $ed, $receivedBy, $onlineOnly, $offlineModes);
            foreach ($payments as $pay) {
                $rid = isset($pay['received_by']) ? (int) $pay['received_by'] : 0;
                $return[] = [
                    'id' => (int) $value->id,
                    'student_fees_master_id' => (int) $value->student_fees_master_id,
                    'fee_groups_feetype_id' => (int) $value->fee_groups_feetype_id,
                    'admission_no' => $value->admission_no,
                    'firstname' => $value->firstname,
                    'middlename' => $value->middlename,
                    'lastname' => $value->lastname,
                    'class_id' => (int) $value->class_id,
                    'class' => $value->class,
                    'section' => $value->section,
                    'section_id' => (int) $value->section_id,
                    'student_id' => (int) $value->student_id,
                    'name' => $value->name,
                    'type' => $value->type,
                    'code' => $value->code,
                    'student_session_id' => (int) $value->student_session_id,
                    'is_system' => (int) ($value->is_system ?? 0),
                    'amount' => (float) ($pay['amount'] ?? 0),
                    'date' => (string) ($pay['date'] ?? ''),
                    'amount_discount' => (float) ($pay['amount_discount'] ?? 0),
                    'amount_fine' => (float) ($pay['amount_fine'] ?? 0),
                    'description' => (string) ($pay['description'] ?? ''),
                    'payment_mode' => (string) ($pay['payment_mode'] ?? ''),
                    'inv_no' => (string) ($pay['inv_no'] ?? ''),
                    'received_by' => $rid,
                    'received_byname' => $staffMap[$rid] ?? ['name' => '', 'employee_id' => '', 'id' => 0],
                ];
            }
        }

        return $return;
    }

    /**
     * @param  list<string>  $offlineModes
     * @return list<array<string, mixed>>
     */
    protected function paymentsInDateRange(
        mixed $raw,
        int $st,
        int $ed,
        ?int $receivedBy,
        bool $onlineOnly,
        array $offlineModes
    ): array {
        $detail = [];
        if (is_string($raw) && $raw !== '' && $raw !== '0') {
            $decoded = json_decode($raw, true);
            $detail = is_array($decoded) ? $decoded : [];
        }

        $matched = [];
        for ($i = $st; $i <= $ed; $i += 86400) {
            $find = date('Y-m-d', $i);
            foreach ($detail as $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                if (($entry['date'] ?? '') !== $find) {
                    continue;
                }
                if ($receivedBy !== null) {
                    if (! isset($entry['received_by']) || (int) $entry['received_by'] !== $receivedBy) {
                        continue;
                    }
                }
                if ($onlineOnly && in_array((string) ($entry['payment_mode'] ?? ''), $offlineModes, true)) {
                    continue;
                }
                $matched[] = $entry;
            }
        }

        return $matched;
    }

    /**
     * @return array<int, array{name: string, employee_id: string, id: int}>
     */
    protected function staffNameMap(): array
    {
        $map = [];
        foreach (DB::table('staff')->select(['id', 'name', 'surname', 'employee_id'])->get() as $row) {
            $map[(int) $row->id] = [
                'id' => (int) $row->id,
                'name' => trim($row->name.' '.($row->surname ?? '')),
                'employee_id' => (string) $row->employee_id,
            ];
        }

        return $map;
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
