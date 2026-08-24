<?php

namespace App\Modules\Timetable\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Timetable\Models\SubjectTimetable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI Subjecttimetable_model + Timetable classreport/create/savegroup/mytimetable/print core.
 * Deferred: duplicate-check AJAX, quick period generator.
 */
class ClassTimetableService
{
    public const TEACHER_ROLE_ID = 2;

    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * CI Customlib::getDaysname — week day keys ordered from sch_settings.start_week.
     *
     * @return list<string>
     */
    public function dayNames(): array
    {
        $startWeek = (string) (DB::table('sch_settings')->value('start_week') ?: 'Monday');
        $start = strtotime('last week '.$startWeek);
        if ($start === false) {
            $start = strtotime('last week Monday');
        }

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = date('l', $start + ($i * 86400));
        }

        return $days;
    }

    /**
     * Active teachers (CI staff_model::getStaffbyrole(2)).
     *
     * @return Collection<int, object>
     */
    public function teachers(): Collection
    {
        return DB::table('staff')
            ->join('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->where('staff_roles.role_id', self::TEACHER_ROLE_ID)
            ->where('staff.is_active', 1)
            ->orderBy('staff.name')
            ->select([
                'staff.id',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
            ])
            ->get();
    }

    /**
     * Subjects in a subject group for the current session.
     *
     * @return Collection<int, object>
     */
    public function groupSubjects(int $subjectGroupId): Collection
    {
        $sessionId = $this->currentSession->id();

        return DB::table('subject_group_subjects')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_group_subjects.subject_group_id', $subjectGroupId)
            ->where('subject_group_subjects.session_id', $sessionId)
            ->orderBy('subjects.name')
            ->select([
                'subject_group_subjects.id',
                'subject_group_subjects.subject_id',
                'subjects.name',
                'subjects.code',
                'subjects.type',
            ])
            ->get();
    }

    /**
     * CI getBySubjectGroupDayClassSection.
     *
     * @return Collection<int, object>
     */
    public function periodsForDay(int $subjectGroupId, string $day, int $classId, int $sectionId): Collection
    {
        return DB::table('subject_timetable')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'subject_timetable.subject_group_subject_id')
            ->join('staff', 'staff.id', '=', 'subject_timetable.staff_id')
            ->where('subject_timetable.class_id', $classId)
            ->where('subject_timetable.section_id', $sectionId)
            ->where('subject_timetable.day', $day)
            ->where('subject_timetable.subject_group_id', $subjectGroupId)
            ->where('staff.is_active', 1)
            ->orderBy('subject_timetable.start_time')
            ->select('subject_timetable.*')
            ->get();
    }

    /**
     * CI getSubjectByClassandSectionDay — used by class report.
     *
     * @return Collection<int, object>
     */
    public function periodsForClassSectionDay(int $classId, int $sectionId, string $day): Collection
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new InvalidArgumentException('Current academic session is not configured.');
        }

        return DB::table('subject_timetable')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'subject_timetable.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('staff', 'staff.id', '=', 'subject_timetable.staff_id')
            ->where('subject_timetable.class_id', $classId)
            ->where('subject_timetable.section_id', $sectionId)
            ->where('subject_timetable.day', $day)
            ->where('subject_timetable.session_id', $sessionId)
            ->where('staff.is_active', 1)
            ->orderBy('subject_timetable.start_time')
            ->select([
                'subject_timetable.*',
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
     * Week map for class report: day => periods.
     *
     * @return array<string, Collection<int, object>>
     */
    public function weekForClassSection(int $classId, int $sectionId): array
    {
        $week = [];
        foreach ($this->dayNames() as $day) {
            $week[$day] = $this->periodsForClassSectionDay($classId, $sectionId, $day);
        }

        return $week;
    }

    /**
     * Week map for create editor: day => periods for subject group.
     *
     * @return array<string, Collection<int, object>>
     */
    public function weekForEditor(int $subjectGroupId, int $classId, int $sectionId): array
    {
        $week = [];
        foreach ($this->dayNames() as $day) {
            $week[$day] = $this->periodsForDay($subjectGroupId, $day, $classId, $sectionId);
        }

        return $week;
    }

    /**
     * CI Subjecttimetable_model::getByStaffandDay.
     *
     * @return Collection<int, object>
     */
    public function periodsForStaffDay(int $staffId, string $day): Collection
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new InvalidArgumentException('Current academic session is not configured.');
        }

        return DB::table('subject_timetable')
            ->join('classes', 'classes.id', '=', 'subject_timetable.class_id')
            ->join('sections', 'sections.id', '=', 'subject_timetable.section_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'subject_timetable.subject_group_subject_id')
            ->join('subjects as sub', 'sub.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_timetable.staff_id', $staffId)
            ->where('subject_timetable.session_id', $sessionId)
            ->where('subject_timetable.day', $day)
            ->orderBy('subject_timetable.start_time')
            ->select([
                'subject_timetable.*',
                'classes.class',
                'sections.section',
                'subject_group_subjects.subject_id',
                'sub.name as subject_name',
                'sub.code as subject_code',
            ])
            ->get();
    }

    /**
     * CI mytimetable / getteachertimetable — day => periods for a teacher.
     *
     * @return array<string, Collection<int, object>>
     */
    public function weekForStaff(int $staffId): array
    {
        $week = [];
        foreach ($this->dayNames() as $day) {
            $week[$day] = $this->periodsForStaffDay($staffId, $day);
        }

        return $week;
    }

    /**
     * CI Section_model::getClassAndSectionNameByClassIDSectionID.
     */
    public function classSectionLabel(int $classId, int $sectionId): ?object
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $section = DB::table('sections')->where('id', $sectionId)->first();
        if (! $class || ! $section) {
            return null;
        }

        return (object) [
            'class' => $class->class,
            'section' => $section->section,
        ];
    }

    /**
     * CI savegroup / Subjecttimetable_model::add — replace day set via delete/insert/update.
     *
     * @param  list<array{
     *     id?:int,
     *     subject_group_subject_id:int,
     *     staff_id:int,
     *     time_from:string,
     *     time_to:string,
     *     room_no:string
     * }>  $rows
     */
    public function saveDay(
        int $classId,
        int $sectionId,
        int $subjectGroupId,
        string $day,
        array $rows
    ): int {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new InvalidArgumentException('Current academic session is not configured.');
        }
        if (! in_array($day, $this->dayNames(), true)) {
            throw new InvalidArgumentException('Invalid day.');
        }

        $validSubjectIds = $this->groupSubjects($subjectGroupId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $teacherIds = $this->teachers()->pluck('id')->map(fn ($id) => (int) $id)->all();

        return (int) DB::transaction(function () use (
            $classId,
            $sectionId,
            $subjectGroupId,
            $day,
            $rows,
            $sessionId,
            $validSubjectIds,
            $teacherIds
        ) {
            $existingIds = SubjectTimetable::query()
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('subject_group_id', $subjectGroupId)
                ->where('day', $day)
                ->where('session_id', $sessionId)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $keepIds = [];
            $saved = 0;

            foreach ($rows as $row) {
                $subjectGroupSubjectId = (int) ($row['subject_group_subject_id'] ?? 0);
                $staffId = (int) ($row['staff_id'] ?? 0);
                $timeFromRaw = (string) ($row['time_from'] ?? '');
                $timeToRaw = (string) ($row['time_to'] ?? '');
                $roomNo = trim((string) ($row['room_no'] ?? ''));
                $rowId = (int) ($row['id'] ?? 0);

                if ($subjectGroupSubjectId <= 0 || $staffId <= 0 || $timeFromRaw === '' || $timeToRaw === '' || $roomNo === '') {
                    throw new InvalidArgumentException('Each period requires subject, teacher, times, and room.');
                }
                if (! in_array($subjectGroupSubjectId, $validSubjectIds, true)) {
                    throw new InvalidArgumentException('Invalid subject for this subject group.');
                }
                if (! in_array($staffId, $teacherIds, true)) {
                    throw new InvalidArgumentException('Invalid teacher.');
                }

                $times = $this->normalizeTimes($timeFromRaw, $timeToRaw);
                $payload = [
                    'day' => $day,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'subject_group_id' => $subjectGroupId,
                    'subject_group_subject_id' => $subjectGroupSubjectId,
                    'staff_id' => $staffId,
                    'time_from' => $times['time_from'],
                    'time_to' => $times['time_to'],
                    'start_time' => $times['start_time'],
                    'end_time' => $times['end_time'],
                    'room_no' => $roomNo,
                    'session_id' => $sessionId,
                ];

                if ($rowId > 0 && in_array($rowId, $existingIds, true)) {
                    SubjectTimetable::query()->where('id', $rowId)->update($payload);
                    $keepIds[] = $rowId;
                } else {
                    $created = SubjectTimetable::query()->create($payload);
                    $keepIds[] = (int) $created->id;
                }
                $saved++;
            }

            $deleteIds = array_values(array_diff($existingIds, $keepIds));
            if ($deleteIds !== []) {
                SubjectTimetable::query()->whereIn('id', $deleteIds)->delete();
            }

            return $saved;
        });
    }

    /**
     * @return array{time_from:string,time_to:string,start_time:string,end_time:string}
     */
    public function normalizeTimes(string $from, string $to): array
    {
        $fromTs = strtotime($from);
        $toTs = strtotime($to);
        if ($fromTs === false || $toTs === false) {
            throw new InvalidArgumentException('Invalid time format.');
        }

        return [
            'time_from' => date('g:i A', $fromTs),
            'time_to' => date('g:i A', $toTs),
            'start_time' => date('H:i:s', $fromTs),
            'end_time' => date('H:i:s', $toTs),
        ];
    }

    /**
     * Convert stored period time to HTML time input value (H:i).
     */
    public function toTimeInput(?string $displayOrStart, ?string $fallbackStart = null): string
    {
        $raw = $displayOrStart ?: $fallbackStart;
        if ($raw === null || $raw === '') {
            return '';
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return '';
        }

        return date('H:i', $ts);
    }
}
