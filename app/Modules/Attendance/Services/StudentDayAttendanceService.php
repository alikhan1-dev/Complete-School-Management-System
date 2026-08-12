<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Attendance\Models\AttendenceType;
use App\Modules\Attendance\Models\StudentAttendence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI Stuattendence_model — day attendance search + addorUpdate.
 * SMS notifications deferred (parity later with Communication module).
 */
class StudentDayAttendanceService
{
    public const TYPE_PRESENT = 1;

    public const TYPE_LATE = 3;

    public const TYPE_ABSENT = 4;

    public const TYPE_HOLIDAY = 5;

    public const TYPE_HALF_DAY = 6;

    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * Active attendance types for mark UI (excludes inactive e.g. Late With Excuse).
     *
     * @return Collection<int, AttendenceType>
     */
    public function activeTypes(): Collection
    {
        return AttendenceType::query()->active()->get();
    }

    /**
     * CI searchAttendenceClassSection — roster for class/section/date in current session.
     *
     * @return Collection<int, object>
     */
    public function searchClassSection(int $classId, int $sectionId, string $date): Collection
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new InvalidArgumentException('Current academic session is not configured.');
        }

        return DB::table('student_session')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('student_attendences', function ($join) use ($date) {
                $join->on('student_attendences.student_session_id', '=', 'student_session.id')
                    ->where('student_attendences.date', '=', $date);
            })
            ->leftJoin('attendence_type', 'attendence_type.id', '=', 'student_attendences.attendence_type_id')
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.admission_no')
            ->select([
                'students.id as student_id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'student_session.id as student_session_id',
                DB::raw('IFNULL(student_attendences.id, 0) as attendence_id'),
                'student_attendences.attendence_type_id',
                'student_attendences.remark',
                'student_attendences.in_time',
                'student_attendences.out_time',
                'student_attendences.biometric_attendence',
                'student_attendences.qrcode_attendance',
                'attendence_type.type as att_type',
            ])
            ->get();
    }

    /**
     * CI searchAttendenceClassSectionPrepare — Attendance By Date.
     * Only students who already have an attendance row for that date (RIGHT JOIN semantics).
     * Class-teacher class filter deferred (CI teacher role_id=2 path).
     *
     * @return Collection<int, object>
     */
    public function searchPreparedByDate(int $classId, int $sectionId, string $date): Collection
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new InvalidArgumentException('Current academic session is not configured.');
        }

        return DB::table('student_attendences')
            ->join('student_session', 'student_session.id', '=', 'student_attendences.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('attendence_type', 'attendence_type.id', '=', 'student_attendences.attendence_type_id')
            ->where('student_attendences.date', $date)
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->orderBy('students.admission_no')
            ->select([
                'students.id as student_id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'student_session.id as student_session_id',
                'student_attendences.id as attendence_id',
                'student_attendences.date',
                'student_attendences.attendence_type_id',
                'student_attendences.remark',
                'attendence_type.type as att_type',
                'attendence_type.long_lang_name',
            ])
            ->get();
    }

    /**
     * CI addorUpdate — upsert by (student_session_id, date). No DB unique key.
     *
     * @param  list<array{
     *     student_session_id:int,
     *     attendence_type_id:int,
     *     date:string,
     *     remark?:string|null,
     *     in_time?:string|null,
     *     out_time?:string|null
     * }>  $rows
     */
    public function addOrUpdate(array $rows): int
    {
        if ($rows === []) {
            throw new InvalidArgumentException('No attendance rows to save.');
        }

        $activeTypeIds = AttendenceType::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();

        return (int) DB::transaction(function () use ($rows, $activeTypeIds) {
            $saved = 0;

            foreach ($rows as $row) {
                $studentSessionId = (int) ($row['student_session_id'] ?? 0);
                $typeId = (int) ($row['attendence_type_id'] ?? 0);
                $date = (string) ($row['date'] ?? '');

                if ($studentSessionId <= 0 || $date === '' || $typeId <= 0) {
                    throw new InvalidArgumentException('Invalid attendance row.');
                }
                if (! in_array($typeId, $activeTypeIds, true)) {
                    throw new InvalidArgumentException('Invalid attendance type.');
                }

                $inTime = $row['in_time'] ?? null;
                $outTime = $row['out_time'] ?? null;
                if (in_array($typeId, [self::TYPE_ABSENT, self::TYPE_HOLIDAY], true)) {
                    $inTime = null;
                    $outTime = null;
                }

                $payload = [
                    'student_session_id' => $studentSessionId,
                    'date' => $date,
                    'attendence_type_id' => $typeId,
                    'remark' => (string) ($row['remark'] ?? ''),
                    'in_time' => $this->normalizeTime($inTime),
                    'out_time' => $this->normalizeTime($outTime),
                    'is_active' => 'no',
                ];

                $existing = StudentAttendence::query()
                    ->where('student_session_id', $studentSessionId)
                    ->where('date', $date)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->fill($payload);
                    $existing->save();
                } else {
                    StudentAttendence::query()->create(array_merge($payload, [
                        'biometric_attendence' => 0,
                        'qrcode_attendance' => 0,
                        'biometric_device_data' => null,
                        'user_agent' => null,
                    ]));
                }
                $saved++;
            }

            return $saved;
        });
    }

    protected function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return date('H:i:s', $ts);
    }
}
