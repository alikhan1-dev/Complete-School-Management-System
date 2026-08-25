<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Attendance\Models\AttendenceType;
use App\Modules\Attendance\Models\StudentSubjectAttendance;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI Studentsubjectattendence_model + subjecttimetable day lookup for period attendance.
 * SMS deferred (Communication).
 */
class SubjectPeriodAttendanceService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    /**
     * @return Collection<int, AttendenceType>
     */
    public function activeTypes(): Collection
    {
        return AttendenceType::query()->active()->get();
    }

    /**
     * CI Subjecttimetable_model::getSubjectByClassandSectionDay.
     * Restricted teachers: class teacher for class → all periods; else own subjects only.
     *
     * @return Collection<int, object>
     */
    public function periodsForDate(int $classId, int $sectionId, string $date): Collection
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new InvalidArgumentException('Current academic session is not configured.');
        }

        $day = date('l', strtotime($date));
        if ($day === false || $day === '') {
            throw new InvalidArgumentException('Invalid date.');
        }

        if (! $this->classTeacherScope->allowsClassSection($classId, $sectionId, 'union')) {
            return collect();
        }

        $query = DB::table('subject_timetable')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'subject_timetable.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('staff', 'staff.id', '=', 'subject_timetable.staff_id')
            ->where('subject_timetable.class_id', $classId)
            ->where('subject_timetable.section_id', $sectionId)
            ->where('subject_timetable.day', $day)
            ->where('subject_timetable.session_id', $sessionId)
            ->where('staff.is_active', 1);

        if ($this->classTeacherScope->shouldFilterPeriodsToOwnSubjects($classId)) {
            $subjectGroupSubjectIds = $this->classTeacherScope
                ->subjectGroupSubjectIdsForClassSection($classId, $sectionId);
            if ($subjectGroupSubjectIds === []) {
                return collect();
            }
            $query->whereIn('subject_group_subjects.id', $subjectGroupSubjectIds);
        }

        return $query
            ->orderBy('subject_timetable.start_time')
            ->select([
                'subject_timetable.*',
                'subject_group_subjects.subject_id',
                'subjects.name as subject_name',
                'subjects.code',
                'subjects.type',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
            ])
            ->get();
    }

    /**
     * CI searchAttendenceClassSection — roster + existing period attendance row.
     *
     * @return Collection<int, object>
     */
    public function searchClassSection(int $classId, int $sectionId, int $subjectTimetableId, string $date): Collection
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new InvalidArgumentException('Current academic session is not configured.');
        }

        if (! $this->classTeacherScope->allowsClassSection($classId, $sectionId, 'union')) {
            return collect();
        }

        if (! $this->timetableBelongsToClassSection($subjectTimetableId, $classId, $sectionId, $sessionId)) {
            throw new InvalidArgumentException('Selected subject period is invalid for this class/section/session.');
        }

        if (! $this->timetableAllowedForTeacher($subjectTimetableId, $classId, $sectionId, $date)) {
            throw new InvalidArgumentException('Selected subject period is not available for your account.');
        }

        return DB::table('student_session')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('student_subject_attendances', function ($join) use ($subjectTimetableId, $date) {
                $join->on('student_subject_attendances.student_session_id', '=', 'student_session.id')
                    ->where('student_subject_attendances.subject_timetable_id', '=', $subjectTimetableId)
                    ->where('student_subject_attendances.date', '=', $date);
            })
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
                DB::raw("IFNULL(student_subject_attendances.id, 0) as student_subject_attendance_id"),
                'student_subject_attendances.subject_timetable_id',
                'student_subject_attendances.attendence_type_id',
                DB::raw("IFNULL(student_subject_attendances.date, 'xxx') as date"),
                'student_subject_attendances.remark',
            ])
            ->get();
    }

    /**
     * CI addorUpdate — upsert by (student_session_id, subject_timetable_id, date).
     *
     * @param  list<array{
     *     student_session_id:int,
     *     subject_timetable_id:int,
     *     attendence_type_id:int,
     *     date:string,
     *     remark?:string|null
     * }>  $rows
     */
    public function addOrUpdate(
        array $rows,
        ?int $classId = null,
        ?int $sectionId = null,
        ?string $date = null,
    ): int {
        if ($rows === []) {
            throw new InvalidArgumentException('No attendance rows to save.');
        }

        if ($classId !== null && $sectionId !== null
            && ! $this->classTeacherScope->allowsClassSection($classId, $sectionId, 'union')) {
            throw new InvalidArgumentException('You are not allowed to mark period attendance for this class/section.');
        }

        $activeTypeIds = AttendenceType::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();

        return (int) DB::transaction(function () use ($rows, $activeTypeIds, $classId, $sectionId, $date) {
            $saved = 0;

            foreach ($rows as $row) {
                $studentSessionId = (int) ($row['student_session_id'] ?? 0);
                $timetableId = (int) ($row['subject_timetable_id'] ?? 0);
                $typeId = (int) ($row['attendence_type_id'] ?? 0);
                $rowDate = (string) ($row['date'] ?? '');

                if ($studentSessionId <= 0 || $timetableId <= 0 || $rowDate === '' || $typeId <= 0) {
                    throw new InvalidArgumentException('Invalid attendance row.');
                }
                if (! in_array($typeId, $activeTypeIds, true)) {
                    throw new InvalidArgumentException('Invalid attendance type.');
                }

                if ($classId !== null && $sectionId !== null) {
                    $belongs = DB::table('student_session')
                        ->where('id', $studentSessionId)
                        ->where('class_id', $classId)
                        ->where('section_id', $sectionId)
                        ->exists();
                    if (! $belongs) {
                        throw new InvalidArgumentException('Student session does not belong to the selected class/section.');
                    }

                    $checkDate = $date ?? $rowDate;
                    if (! $this->timetableAllowedForTeacher($timetableId, $classId, $sectionId, $checkDate)) {
                        throw new InvalidArgumentException('You are not allowed to mark this subject period.');
                    }
                }

                $payload = [
                    'student_session_id' => $studentSessionId,
                    'subject_timetable_id' => $timetableId,
                    'attendence_type_id' => $typeId,
                    'date' => $rowDate,
                    'remark' => (string) ($row['remark'] ?? ''),
                ];

                $existing = StudentSubjectAttendance::query()
                    ->where('student_session_id', $studentSessionId)
                    ->where('subject_timetable_id', $timetableId)
                    ->where('date', $rowDate)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->fill($payload);
                    $existing->save();
                } else {
                    StudentSubjectAttendance::query()->create($payload);
                }
                $saved++;
            }

            return $saved;
        });
    }

    /**
     * CI Studentsubjectattendence_model::searchByStudentsAttendanceByDate.
     * Matrix of students × subject periods for a class/section on a date.
     * Class/section scope applied; subject columns remain unfiltered (CI parity).
     *
     * @return object{subjects: Collection<int, object>, student_record: Collection<int, object>}|null
     */
    public function searchByStudentsAttendanceByDate(int $classId, int $sectionId, string $date): ?object
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new InvalidArgumentException('Current academic session is not configured.');
        }

        if (! $this->classTeacherScope->allowsClassSection($classId, $sectionId, 'union')) {
            return null;
        }

        $day = date('l', strtotime($date));
        if ($day === false || $day === '') {
            throw new InvalidArgumentException('Invalid date.');
        }

        $subjects = DB::table('subject_timetable')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'subject_timetable.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_timetable.class_id', $classId)
            ->where('subject_timetable.section_id', $sectionId)
            ->where('subject_timetable.session_id', $sessionId)
            ->where('subject_timetable.day', $day)
            ->select([
                'subject_timetable.*',
                'subjects.id as subject_id',
                'subjects.name',
                'subjects.code',
                'subjects.type',
            ])
            ->get();

        if ($subjects->isEmpty()) {
            return null;
        }

        $query = DB::table('students')
            ->join('student_session', function ($join) use ($classId, $sectionId, $sessionId) {
                $join->on('students.id', '=', 'student_session.student_id')
                    ->where('student_session.class_id', '=', $classId)
                    ->where('student_session.section_id', '=', $sectionId)
                    ->where('student_session.session_id', '=', $sessionId);
            })
            ->where('students.is_active', 'yes');

        $selects = [
            'students.id',
            'students.firstname',
            'students.middlename',
            'students.lastname',
            'students.admission_no',
        ];

        $count = 1;
        foreach ($subjects as $subject) {
            $alias = 'ssa_'.$count;
            $query->leftJoin("student_subject_attendances as {$alias}", function ($join) use ($alias, $subject, $date) {
                $join->on("{$alias}.student_session_id", '=', 'student_session.id')
                    ->where("{$alias}.subject_timetable_id", '=', $subject->id)
                    ->where("{$alias}.date", '=', $date);
            });
            $selects[] = DB::raw("{$alias}.attendence_type_id as attendence_type_id_{$count}");
            $count++;
        }

        $studentRecord = $query->select($selects)->get();

        return (object) [
            'subjects' => $subjects,
            'student_record' => $studentRecord,
        ];
    }

    protected function timetableBelongsToClassSection(
        int $timetableId,
        int $classId,
        int $sectionId,
        int $sessionId
    ): bool {
        return DB::table('subject_timetable')
            ->where('id', $timetableId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('session_id', $sessionId)
            ->exists();
    }

    /**
     * Period must appear in the same filtered set as periodsForDate for this teacher.
     */
    protected function timetableAllowedForTeacher(
        int $timetableId,
        int $classId,
        int $sectionId,
        string $date
    ): bool {
        if (! $this->classTeacherScope->isRestricted()) {
            return true;
        }

        return $this->periodsForDate($classId, $sectionId, $date)
            ->contains(fn ($period) => (int) $period->id === $timetableId);
    }
}
