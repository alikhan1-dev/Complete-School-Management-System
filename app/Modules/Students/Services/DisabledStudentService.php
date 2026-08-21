<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Student::disablestudentslist + Student_model disable searches.
 * Deferred: class-teacher scope, custom-field columns, details-view cards.
 */
class DisabledStudentService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
    ) {
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    public function studentDisplayName(object $student): string
    {
        $first = trim((string) ($student->firstname ?? ''));
        $middle = trim((string) ($student->middlename ?? ''));
        $last = trim((string) ($student->lastname ?? ''));

        $name = $this->settingOn('middlename') && $middle !== ''
            ? trim($first.' '.$middle)
            : $first;
        if ($this->settingOn('lastname') && $last !== '') {
            $name = trim($name.' '.$last);
        }

        return $name !== '' ? $name : $first;
    }

    /**
     * @return array<int, string> reason id => reason text
     */
    public function reasonMap(): array
    {
        return DB::table('disable_reason')
            ->orderBy('id')
            ->pluck('reason', 'id')
            ->map(fn ($reason) => (string) $reason)
            ->all();
    }

    /**
     * CI disablestudentByClassSection — inactive students in current session.
     *
     * @return Collection<int, object>
     */
    public function searchByClassSection(int $classId, ?int $sectionId = null): Collection
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0 || $classId <= 0) {
            return collect();
        }

        $query = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'no')
            ->where('student_session.class_id', $classId);

        if ($sectionId !== null && $sectionId > 0) {
            $query->where('student_session.section_id', $sectionId);
        }

        $studentIds = $query->distinct()->pluck('students.id')->all();

        return $this->hydrateDisabledStudents($studentIds, $sessionId);
    }

    /**
     * CI disablestudentFullText.
     * Note: CI builds a LIKE clause but never applies it; Laravel preserves that
     * observed behavior (returns all inactive students in the current session).
     *
     * @return Collection<int, object>
     */
    public function searchFullText(string $term): Collection
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            return collect();
        }

        // Preserve CI quirk: $term is unused in the legacy SQL.
        unset($term);

        $studentIds = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'no')
            ->distinct()
            ->pluck('students.id')
            ->all();

        return $this->hydrateDisabledStudents($studentIds, $sessionId);
    }

    /**
     * @param  list<int|string>  $studentIds
     * @return Collection<int, object>
     */
    protected function hydrateDisabledStudents(array $studentIds, int $sessionId): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        $students = DB::table('students')
            ->whereIn('id', $studentIds)
            ->orderByDesc('id')
            ->select([
                'id',
                'admission_no',
                'firstname',
                'middlename',
                'lastname',
                'father_name',
                'gender',
                'mobileno',
                'dis_reason',
                'dis_note',
                'image',
                'dob',
            ])
            ->get();

        $labels = DB::table('student_session')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->whereIn('student_session.student_id', $studentIds)
            ->select([
                'student_session.student_id',
                DB::raw("GROUP_CONCAT(CONCAT(classes.class, '(', sections.section, ')') SEPARATOR ', ') as class_section_list"),
            ])
            ->groupBy('student_session.student_id')
            ->pluck('class_section_list', 'student_id');

        return $students->map(function ($student) use ($labels) {
            $student->class_section_list = (string) ($labels[$student->id] ?? '');

            return $student;
        });
    }
}
