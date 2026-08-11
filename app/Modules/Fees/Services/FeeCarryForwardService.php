<?php

namespace App\Modules\Fees\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\StudentFeesMaster;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * CI admin/Feesforward — previous session balance → current session system master.
 */
class FeeCarryForwardService
{
    public const BALANCE_GROUP = 'Balance Master';

    public const BALANCE_TYPE = 'Previous Session Balance';

    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected FeeCollectService $collect
    ) {
    }

    public function previousSessionId(?int $currentSessionId = null): ?int
    {
        $current = $currentSessionId ?: $this->currentSession->id();
        if ($current <= 0) {
            return null;
        }

        $id = DB::table('sessions')->where('id', '<', $current)->max('id');

        return $id ? (int) $id : null;
    }

    public function previousSessionLabel(?int $previousSessionId): string
    {
        if (! $previousSessionId) {
            return '';
        }

        return (string) (DB::table('sessions')->where('id', $previousSessionId)->value('session') ?? '');
    }

    /**
     * Students in current session class/section who may have a previous session enrollment.
     *
     * @return list<object>
     */
    public function search(int $classId, int $sectionId): array
    {
        $currentSessionId = $this->currentSession->id();
        if ($currentSessionId <= 0) {
            throw new RuntimeException('Current academic session is not configured.');
        }

        $previousSessionId = $this->previousSessionId($currentSessionId);
        $balanceSessionGroupId = $this->findBalanceSessionGroupId($currentSessionId);

        $query = DB::table('student_session as current_ss')
            ->join('students', 'students.id', '=', 'current_ss.student_id')
            ->leftJoin('student_session as previous_ss', function ($join) use ($previousSessionId) {
                $join->on('previous_ss.student_id', '=', 'current_ss.student_id');
                if ($previousSessionId) {
                    $join->where('previous_ss.session_id', '=', $previousSessionId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->where('current_ss.session_id', $currentSessionId)
            ->where('current_ss.class_id', $classId)
            ->where('current_ss.section_id', $sectionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.firstname')
            ->select([
                'students.id as student_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'current_ss.id as student_session_id',
                'previous_ss.id as previous_student_session_id',
            ]);

        $rows = $query->get();
        $list = [];

        foreach ($rows as $row) {
            $balance = 0.0;
            if (! empty($row->previous_student_session_id)) {
                foreach ($this->collect->getStudentFees((int) $row->previous_student_session_id) as $line) {
                    $balance += max(0, (float) $line->balance);
                }
                $balance = round($balance, 2);
            }

            $assigned = false;
            $assignedAmount = null;
            if ($balanceSessionGroupId) {
                $existing = StudentFeesMaster::query()
                    ->where('student_session_id', (int) $row->student_session_id)
                    ->where('fee_session_group_id', $balanceSessionGroupId)
                    ->where('is_system', 1)
                    ->first();
                if ($existing) {
                    $assigned = true;
                    $assignedAmount = (float) $existing->amount;
                    if ($balance <= 0 && empty($row->previous_student_session_id)) {
                        $balance = $assignedAmount;
                    }
                }
            }

            $list[] = (object) [
                'student_id' => (int) $row->student_id,
                'admission_no' => $row->admission_no,
                'name' => trim($row->firstname.' '.($row->middlename ?? '').' '.$row->lastname),
                'father_name' => $row->father_name,
                'student_session_id' => (int) $row->student_session_id,
                'previous_student_session_id' => $row->previous_student_session_id ? (int) $row->previous_student_session_id : null,
                'balance' => $balance,
                'assigned' => $assigned,
                'assigned_amount' => $assignedAmount,
            ];
        }

        return $list;
    }

    /**
     * CI addPreviousBal — upsert system Balance Master / Previous Session Balance into current session.
     *
     * @param  list<array{student_session_id:int,amount:float|string}>  $rows
     */
    public function submit(array $rows, string $dueDate): int
    {
        $currentSessionId = $this->currentSession->id();
        if ($currentSessionId <= 0) {
            throw new RuntimeException('Current academic session is not configured.');
        }

        if ($dueDate === '') {
            throw new InvalidArgumentException('Due date is required.');
        }

        return (int) DB::transaction(function () use ($rows, $dueDate, $currentSessionId) {
            $feeGroup = FeeGroup::query()->firstOrCreate(
                ['name' => self::BALANCE_GROUP],
                [
                    'description' => '',
                    'is_system' => 1,
                    'nature' => '',
                    'is_active' => 'no',
                ]
            );
            if ((int) $feeGroup->is_system !== 1) {
                $feeGroup->is_system = 1;
                $feeGroup->save();
            }

            $feeType = FeeType::query()->firstOrCreate(
                ['type' => self::BALANCE_TYPE],
                [
                    'code' => self::BALANCE_TYPE,
                    'description' => '',
                    'is_system' => 1,
                    'nature' => '',
                    'is_active' => 'no',
                ]
            );
            if ((int) $feeType->is_system !== 1) {
                $feeType->is_system = 1;
                $feeType->code = self::BALANCE_TYPE;
                $feeType->save();
            }

            $sessionGroup = FeeSessionGroup::query()->firstOrCreate(
                [
                    'fee_groups_id' => $feeGroup->id,
                    'session_id' => $currentSessionId,
                ],
                ['is_active' => 'no']
            );

            $feetypeRow = FeeGroupFeetype::query()
                ->where('fee_session_group_id', $sessionGroup->id)
                ->where('feetype_id', $feeType->id)
                ->first();

            if ($feetypeRow) {
                $feetypeRow->due_date = $dueDate;
                $feetypeRow->session_id = $currentSessionId;
                $feetypeRow->fee_groups_id = $feeGroup->id;
                $feetypeRow->save();
            } else {
                FeeGroupFeetype::query()->create([
                    'fee_session_group_id' => $sessionGroup->id,
                    'fee_groups_id' => $feeGroup->id,
                    'feetype_id' => $feeType->id,
                    'session_id' => $currentSessionId,
                    'amount' => 0,
                    'due_date' => $dueDate,
                    'fine_type' => 'none',
                    'fine_percentage' => 0,
                    'fine_amount' => 0,
                    'fine_per_day' => 0,
                    'is_active' => 'no',
                ]);
            }

            $saved = 0;
            foreach ($rows as $row) {
                $studentSessionId = (int) ($row['student_session_id'] ?? 0);
                if ($studentSessionId <= 0) {
                    continue;
                }
                $amount = round((float) ($row['amount'] ?? 0), 2);

                $master = StudentFeesMaster::query()
                    ->where('student_session_id', $studentSessionId)
                    ->where('fee_session_group_id', $sessionGroup->id)
                    ->first();

                if ($master) {
                    $master->amount = $amount;
                    $master->is_system = 1;
                    $master->save();
                } else {
                    StudentFeesMaster::query()->create([
                        'is_system' => 1,
                        'student_session_id' => $studentSessionId,
                        'fee_session_group_id' => $sessionGroup->id,
                        'amount' => $amount,
                        'is_active' => 'no',
                    ]);
                }
                $saved++;
            }

            return $saved;
        });
    }

    protected function findBalanceSessionGroupId(int $sessionId): ?int
    {
        $groupId = FeeGroup::query()->where('name', self::BALANCE_GROUP)->value('id');
        if (! $groupId) {
            return null;
        }

        $id = FeeSessionGroup::query()
            ->where('fee_groups_id', $groupId)
            ->where('session_id', $sessionId)
            ->value('id');

        return $id ? (int) $id : null;
    }
}
