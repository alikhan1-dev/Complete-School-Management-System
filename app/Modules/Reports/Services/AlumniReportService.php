<?php

namespace App\Modules\Reports\Services;

use App\Modules\Shared\Services\ClassTeacherScopeService;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Report::alumnireport + Student_model::search_alumniStudentReport.
 * Requires alumni_students row (inner join). Deferred: custom fields, add-details modal.
 */
class AlumniReportService
{
    public function __construct(
        protected SchoolContext $school,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        return Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
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
     * @return Collection<int, object>
     */
    public function sessions(): Collection
    {
        return DB::table('sessions')->orderByDesc('id')->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function classes(): Collection
    {
        return $this->classTeacherScope->classesForDropdown();
    }

    /**
     * @return array<int, object> student_id => alumni_students row
     */
    public function alumniDetailsByStudentId(): array
    {
        $map = [];
        foreach (DB::table('alumni_students')->get() as $row) {
            $map[(int) $row->student_id] = $row;
        }

        return $map;
    }

    /**
     * CI search_alumniStudentReport — alumni_students + is_alumni=1 + is_active=yes.
     *
     * @return Collection<int, object>
     */
    public function searchByFilter(int $sessionId, int $classId, ?int $sectionId = null): Collection
    {
        if ($sessionId <= 0 || $classId <= 0) {
            return collect();
        }

        if ($this->classTeacherScope->isRestricted()) {
            $allowedClasses = $this->classTeacherScope->restrictedClassIds();
            if ($allowedClasses === [] || ! in_array($classId, $allowedClasses, true)) {
                return collect();
            }
            if ($sectionId !== null && $sectionId > 0) {
                $allowedSections = $this->classTeacherScope->restrictedSectionIdsForClass($classId);
                if (! in_array($sectionId, $allowedSections, true)) {
                    return collect();
                }
            }
        }

        $sessionQuery = DB::table('alumni_students')
            ->join('students', 'students.id', '=', 'alumni_students.student_id')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->where('student_session.is_alumni', 1)
            ->where('students.is_active', 'yes')
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId);

        if ($sectionId !== null && $sectionId > 0) {
            $sessionQuery->where('student_session.section_id', $sectionId);
        }

        $studentIds = $sessionQuery->distinct()->pluck('students.id')->all();
        if ($studentIds === []) {
            return collect();
        }

        $students = DB::table('students')
            ->whereIn('id', $studentIds)
            ->orderBy('admission_no')
            ->select([
                'id',
                'admission_no',
                'firstname',
                'middlename',
                'lastname',
                'gender',
                'dob',
                'current_address',
                'city',
            ])
            ->get();

        $labelQuery = DB::table('student_session')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.is_alumni', 1)
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->whereIn('student_session.student_id', $studentIds);

        if ($sectionId !== null && $sectionId > 0) {
            $labelQuery->where('student_session.section_id', $sectionId);
        }

        $labels = $labelQuery
            ->select([
                'student_session.student_id',
                DB::raw('GROUP_CONCAT(CONCAT(classes.class, "(", sections.section, ")")) as class'),
            ])
            ->groupBy('student_session.student_id')
            ->pluck('class', 'student_id');

        return $students->map(function ($student) use ($labels, $sessionId) {
            $student->class = (string) ($labels[$student->id] ?? '');
            $student->session_id = $sessionId;

            return $student;
        });
    }

    public function displayAddress(object $student, ?object $alumni): string
    {
        $address = $alumni !== null && filled($alumni->address ?? null)
            ? (string) $alumni->address
            : (string) ($student->current_address ?? '');
        $city = trim((string) ($student->city ?? ''));

        return trim($address.($city !== '' ? ' '.$city : ''));
    }
}
