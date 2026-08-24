<?php

namespace App\Modules\Shared\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CI Customlib::get_myClassSection + Teacher_model teacherrestricted helpers.
 *
 * Active when staff role_id = 2 (Teacher) and sch_settings.class_teacher = 'yes'.
 * Class/section access is the union of subject_timetable + class_teacher for the
 * current academic session.
 */
class ClassTeacherScopeService
{
    public const TEACHER_ROLE_ID = 2;

    public function __construct(
        protected SchoolContext $school,
        protected CurrentSessionResolver $currentSession,
    ) {
    }

    public function isRestricted(): bool
    {
        if ((string) $this->school->get('class_teacher', 'no') !== 'yes') {
            return false;
        }

        $staff = Auth::guard('staff')->user();
        if ($staff === null) {
            return false;
        }

        $roleId = (int) DB::table('staff_roles')
            ->where('staff_id', $staff->id)
            ->where('is_active', 1)
            ->orderBy('id')
            ->value('role_id');

        return $roleId === self::TEACHER_ROLE_ID;
    }

    public function staffId(): int
    {
        return (int) (Auth::guard('staff')->id() ?? 0);
    }

    /**
     * CI MY_Model::getTeacherClassSectionMatrix.
     *
     * null  = unrestricted (non-teacher / setting off)
     * []    = teacher restricted but no class/section assignments
     * map   = class_id => section_ids
     *
     * @return array<int, list<int>>|null
     */
    public function teacherClassSectionMatrix(): ?array
    {
        if (! $this->isRestricted()) {
            return null;
        }

        return $this->myClassSectionMap();
    }

    /**
     * Filter student rows that already include class_id / section_id
     * (CI searchByClassSectionWithSession matrix filter).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function filterRowsByMatrix(array $rows): array
    {
        $matrix = $this->teacherClassSectionMatrix();
        if ($matrix === null) {
            return $rows;
        }
        if ($matrix === []) {
            return [];
        }

        $allowed = [];
        foreach ($matrix as $classId => $sectionIds) {
            foreach ($sectionIds as $sectionId) {
                $allowed[(int) $classId][(int) $sectionId] = true;
            }
        }

        return array_values(array_filter($rows, function (array $row) use ($allowed) {
            $classId = isset($row['class_id']) ? (int) $row['class_id'] : 0;
            $sectionId = isset($row['section_id']) ? (int) $row['section_id'] : 0;

            return $classId > 0 && $sectionId > 0 && isset($allowed[$classId][$sectionId]);
        }));
    }

    /**
     * CI get_myClassSection — map of class_id => list of section_ids.
     * Empty array when unrestricted or when teacher has no assignments.
     *
     * @return array<int, list<int>>
     */
    public function myClassSectionMap(): array
    {
        if (! $this->isRestricted()) {
            return [];
        }

        $map = [];
        foreach ($this->restrictedClassIds() as $classId) {
            $sections = $this->restrictedSectionIdsForClass($classId);
            if ($sections !== []) {
                $map[$classId] = $sections;
            }
        }

        return $map;
    }

    /**
     * @return list<int>
     */
    public function restrictedClassIds(): array
    {
        $staffId = $this->staffId();
        $sessionId = (int) $this->currentSession->id();
        if ($staffId <= 0 || $sessionId <= 0) {
            return [];
        }

        $fromTimetable = DB::table('subject_timetable')
            ->where('staff_id', $staffId)
            ->where('session_id', $sessionId)
            ->pluck('class_id')
            ->all();

        $fromClassTeacher = DB::table('class_teacher')
            ->where('staff_id', $staffId)
            ->where('session_id', $sessionId)
            ->pluck('class_id')
            ->all();

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge($fromTimetable, $fromClassTeacher)
        ), fn (int $id) => $id > 0)));

        sort($ids);

        return $ids;
    }

    /**
     * @return list<int>
     */
    public function restrictedSectionIdsForClass(int $classId): array
    {
        $staffId = $this->staffId();
        $sessionId = (int) $this->currentSession->id();
        if ($staffId <= 0 || $sessionId <= 0 || $classId <= 0) {
            return [];
        }

        $fromTimetable = DB::table('subject_timetable')
            ->where('staff_id', $staffId)
            ->where('session_id', $sessionId)
            ->where('class_id', $classId)
            ->pluck('section_id')
            ->all();

        $fromClassTeacher = DB::table('class_teacher')
            ->where('staff_id', $staffId)
            ->where('session_id', $sessionId)
            ->where('class_id', $classId)
            ->pluck('section_id')
            ->all();

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge($fromTimetable, $fromClassTeacher)
        ), fn (int $id) => $id > 0)));

        sort($ids);

        return $ids;
    }

    /**
     * Classes for dropdowns — CI Class_model::get() teacher branch.
     *
     * @return Collection<int, object>
     */
    public function classesForDropdown(): Collection
    {
        if (! $this->isRestricted()) {
            return DB::table('classes')->orderBy('id')->get(['id', 'class']);
        }

        $ids = $this->restrictedClassIds();
        if ($ids === []) {
            return collect();
        }

        return DB::table('classes')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get(['id', 'class']);
    }

    /**
     * Sections for a class — CI Section_model::getClassBySection.
     *
     * @return list<object{section_id:int|string,section:string,id?:int|string}>
     */
    public function sectionsForClass(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }

        if ($this->isRestricted()) {
            $sectionIds = $this->restrictedSectionIdsForClass($classId);
            if ($sectionIds === []) {
                return [];
            }

            return DB::table('class_sections')
                ->join('sections', 'sections.id', '=', 'class_sections.section_id')
                ->where('class_sections.class_id', $classId)
                ->whereIn('class_sections.section_id', $sectionIds)
                ->orderBy('class_sections.id')
                ->select([
                    'class_sections.id',
                    'class_sections.section_id',
                    'sections.section',
                ])
                ->get()
                ->all();
        }

        return DB::table('class_sections')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('class_sections.class_id', $classId)
            ->orderBy('class_sections.id')
            ->select([
                'class_sections.id',
                'class_sections.section_id',
                'sections.section',
            ])
            ->get()
            ->all();
    }

    /**
     * Apply CI-style (class_id AND section_id) OR groups onto a query builder
     * that already references student_session.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     */
    public function applyStudentSessionScope($query, ?array $map = null): void
    {
        $map ??= $this->myClassSectionMap();
        if ($map === []) {
            return;
        }

        $query->where(function ($outer) use ($map) {
            foreach ($map as $classId => $sectionIds) {
                foreach ($sectionIds as $sectionId) {
                    $outer->orWhere(function ($inner) use ($classId, $sectionId) {
                        $inner->where('student_session.class_id', (int) $classId)
                            ->where('student_session.section_id', (int) $sectionId);
                    });
                }
            }
        });
    }
}
