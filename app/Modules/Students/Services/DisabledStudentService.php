<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Student::disablestudentslist + Student_model disable searches.
 */
class DisabledStudentService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
        protected ClassTeacherScopeService $classTeacherScope,
        protected CustomFieldValueService $customFields,
    ) {
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    /**
     * CI get_custom_fields('students', 1) for list columns.
     */
    public function tableCustomFields(): Collection
    {
        return $this->customFields->fieldsForTable('students');
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

    public function studentImageUrl(object $student): string
    {
        $image = trim((string) ($student->image ?? ''));
        if ($image === '') {
            return asset('uploads/student_images/no_image.png');
        }

        return asset($image);
    }

    public function customFieldDisplay(object $student, object $field): string
    {
        $values = (array) ($student->table_custom ?? []);
        $value = (string) ($values[(int) $field->id] ?? '');
        if ($value === '') {
            return '';
        }

        if ((string) ($field->type ?? '') === 'link') {
            return '<a href="'.e($value).'" target="_blank">'.e($value).'</a>';
        }

        return e($value);
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
     * Class-teacher map scopes class_section_list labels (CI subquery only).
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

        return $this->hydrateDisabledStudents($studentIds, $sessionId, $this->classTeacherScope->myClassSectionMap());
    }

    /**
     * CI disablestudentFullText.
     * Note: CI builds a LIKE clause but never applies it; Laravel preserves that
     * observed behavior (returns inactive students in the current session).
     * Class-teacher map scopes both the main query and class_section_list labels.
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

        $scopeMap = $this->classTeacherScope->myClassSectionMap();

        $query = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'no');

        $this->classTeacherScope->applyStudentSessionScope($query, $scopeMap);

        $studentIds = $query->distinct()->pluck('students.id')->all();

        return $this->hydrateDisabledStudents($studentIds, $sessionId, $scopeMap);
    }

    /**
     * @param  list<int|string>  $studentIds
     * @param  array<int, list<int>>  $scopeMap
     * @return Collection<int, object>
     */
    protected function hydrateDisabledStudents(array $studentIds, int $sessionId, array $scopeMap = []): Collection
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
                'samagra_id',
                'guardian_name',
                'guardian_phone',
                'current_address',
                'city',
            ])
            ->get();

        $classRows = DB::table('student_session')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->whereIn('student_session.student_id', $studentIds)
            ->select([
                'student_session.student_id',
                'student_session.class_id',
                'student_session.section_id',
                'classes.class',
                'sections.section',
            ])
            ->orderBy('student_session.id')
            ->get();

        $labels = [];
        $primary = [];
        foreach ($classRows as $row) {
            $studentId = (int) $row->student_id;
            $classId = (int) $row->class_id;
            $sectionId = (int) $row->section_id;

            if ($scopeMap !== []) {
                $allowed = $scopeMap[$classId] ?? [];
                if (! in_array($sectionId, $allowed, true)) {
                    continue;
                }
            }

            $label = $row->class.'('.$row->section.')';
            if (! isset($labels[$studentId])) {
                $labels[$studentId] = [];
            }
            $labels[$studentId][] = $label;

            if (! isset($primary[$studentId])) {
                $primary[$studentId] = [
                    'class' => (string) $row->class,
                    'section' => (string) $row->section,
                ];
            }
        }

        $customMaps = $this->customFields->tableValuesByBelongIds('students', $studentIds);

        return $students->map(function ($student) use ($labels, $primary, $customMaps) {
            $studentId = (int) $student->id;
            $student->class_section_list = isset($labels[$studentId])
                ? implode(', ', $labels[$studentId])
                : '';
            $student->class = $primary[$studentId]['class'] ?? '';
            $student->section = $primary[$studentId]['section'] ?? '';
            $student->table_custom = $customMaps[$studentId] ?? [];

            return $student;
        });
    }
}
