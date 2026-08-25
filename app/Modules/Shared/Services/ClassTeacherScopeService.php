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

        $fromClassTeacher = $this->classTeacherOnlySectionIdsForClass($classId);

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge($fromTimetable, $fromClassTeacher)
        ), fn (int $id) => $id > 0)));

        sort($ids);

        return $ids;
    }

    /**
     * CI Teacher_model::get_daywiseattendanceclass — class_teacher table only.
     *
     * @return list<int>
     */
    public function classTeacherOnlyClassIds(): array
    {
        $staffId = $this->staffId();
        $sessionId = (int) $this->currentSession->id();
        if ($staffId <= 0 || $sessionId <= 0) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            DB::table('class_teacher')
                ->where('staff_id', $staffId)
                ->where('session_id', $sessionId)
                ->pluck('class_id')
                ->all()
        ), fn (int $id) => $id > 0)));

        sort($ids);

        return $ids;
    }

    /**
     * CI Teacher_model::get_teacherrestricted_modesections day_wise branch.
     *
     * @return list<int>
     */
    public function classTeacherOnlySectionIdsForClass(int $classId): array
    {
        $staffId = $this->staffId();
        $sessionId = (int) $this->currentSession->id();
        if ($staffId <= 0 || $sessionId <= 0 || $classId <= 0) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            DB::table('class_teacher')
                ->where('staff_id', $staffId)
                ->where('session_id', $sessionId)
                ->where('class_id', $classId)
                ->pluck('section_id')
                ->all()
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

        return $this->classesByIds($this->restrictedClassIds());
    }

    /**
     * Classes for Attendance By Date — CI get_daywiseattendanceclass.
     *
     * @return Collection<int, object>
     */
    public function classesForDayWiseAttendanceDropdown(): Collection
    {
        if (! $this->isRestricted()) {
            return DB::table('classes')->orderBy('id')->get(['id', 'class']);
        }

        return $this->classesByIds($this->classTeacherOnlyClassIds());
    }

    /**
     * Sections for a class — CI Section_model::getClassBySection.
     * When $dayWise is true and restricted, use class_teacher sections only.
     *
     * @return list<object{section_id:int|string,section:string,id?:int|string}>
     */
    public function sectionsForClass(int $classId, bool $dayWise = false): array
    {
        if ($classId <= 0) {
            return [];
        }

        if ($this->isRestricted()) {
            $sectionIds = $dayWise
                ? $this->classTeacherOnlySectionIdsForClass($classId)
                : $this->restrictedSectionIdsForClass($classId);
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
     * Whether the restricted teacher may use this class/section pair.
     *
     * Modes (CI parity):
     * - union     — timetable ∪ class_teacher (subject/period attendance)
     * - day_mark  — union classes + class_teacher-only sections (day mark day_wise)
     * - day_wise  — class_teacher-only classes + sections (attendance by date)
     */
    public function allowsClassSection(int $classId, int $sectionId, string $mode = 'union'): bool
    {
        if (! $this->isRestricted()) {
            return $classId > 0 && $sectionId > 0;
        }

        if ($classId <= 0 || $sectionId <= 0) {
            return false;
        }

        if ($mode === 'day_wise') {
            if (! in_array($classId, $this->classTeacherOnlyClassIds(), true)) {
                return false;
            }

            return in_array($sectionId, $this->classTeacherOnlySectionIdsForClass($classId), true);
        }

        if (! in_array($classId, $this->restrictedClassIds(), true)) {
            return false;
        }

        if ($mode === 'day_mark') {
            return in_array($sectionId, $this->classTeacherOnlySectionIdsForClass($classId), true);
        }

        // union (default)
        return in_array($sectionId, $this->restrictedSectionIdsForClass($classId), true);
    }

    /**
     * CI Teacher_model::my_classes — class_ids from class_teacher only.
     *
     * @return list<int>
     */
    public function myClassTeacherClassIds(): array
    {
        return $this->classTeacherOnlyClassIds();
    }

    /**
     * CI Teacher_model::get_subjectby_classid — subject_group_subject ids taught by staff.
     *
     * @return list<int>
     */
    public function subjectGroupSubjectIdsForClassSection(int $classId, int $sectionId): array
    {
        $staffId = $this->staffId();
        $sessionId = (int) $this->currentSession->id();
        if ($staffId <= 0 || $sessionId <= 0 || $classId <= 0 || $sectionId <= 0) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            DB::table('subject_timetable')
                ->where('staff_id', $staffId)
                ->where('session_id', $sessionId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->pluck('subject_group_subject_id')
                ->all()
        ), fn (int $id) => $id > 0)));

        sort($ids);

        return $ids;
    }

    /**
     * CI Subjecttimetable_model::getSubjectByClassandSectionDay teacher branch.
     * Class teacher for the class → all periods; otherwise → own subject_group_subjects only.
     */
    public function shouldFilterPeriodsToOwnSubjects(int $classId): bool
    {
        if (! $this->isRestricted()) {
            return false;
        }

        return ! in_array($classId, $this->myClassTeacherClassIds(), true);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, object>
     */
    protected function classesByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return DB::table('classes')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get(['id', 'class']);
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
