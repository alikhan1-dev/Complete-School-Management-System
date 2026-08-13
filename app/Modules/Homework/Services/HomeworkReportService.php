<?php

namespace App\Modules\Homework\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI homework reports — homework / evaluation / marks.
 * Deferred: daily assignment report (DataTables + date-range AJAX).
 */
class HomeworkReportService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
    ) {
    }

    public function sessionId(): int
    {
        $id = (int) $this->currentSession->id();
        if ($id <= 0) {
            throw ValidationException::withMessages([
                'session_id' => 'Current academic session is not configured.',
            ]);
        }

        return $id;
    }

    /**
     * CI search_dthomeworkreport (without class-teacher matrix filter).
     *
     * @param  array{class_id?:mixed,section_id?:mixed,subject_group_id?:mixed,subject_id?:mixed}  $filters
     * @return Collection<int, object>
     */
    public function homeworkReport(array $filters): Collection
    {
        $sessionId = $this->sessionId();
        $q = DB::table('homework')
            ->join('classes', 'classes.id', '=', 'homework.class_id')
            ->join('sections', 'sections.id', '=', 'homework.section_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'homework.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->leftJoin('staff', 'staff.id', '=', 'homework.created_by')
            ->where('subject_groups.session_id', $sessionId)
            ->where('homework.session_id', $sessionId);

        $this->applyHomeworkFilters($q, $filters);

        return $q->orderByDesc('homework.homework_date')
            ->select([
                'homework.*',
                'classes.class',
                'sections.section',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'subject_groups.name as subject_group_name',
                'subject_groups.id as subject_groups_id',
                'subject_group_subjects.id as subject_group_subject_id',
                DB::raw('(select count(*) from submit_assignment where submit_assignment.homework_id = homework.id) as assignments'),
                DB::raw('(select count(distinct student_session.student_id) from student_session inner join students on students.id = student_session.student_id where student_session.class_id = homework.class_id and student_session.section_id = homework.section_id and student_session.session_id = '.$sessionId.' and students.is_active = \'yes\') as student_count'),
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id as staff_employee_id',
            ])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function homeworkReportStudents(int $homeworkId, string $type, int $classId, int $sectionId): Collection
    {
        $sessionId = $this->sessionId();
        abort_unless(in_array($type, ['student_count', 'homework_submitted', 'pending_student'], true), 404);

        if ($type === 'homework_submitted') {
            return DB::table('submit_assignment')
                ->join('students', 'students.id', '=', 'submit_assignment.student_id')
                ->join('student_session', function ($join) use ($sessionId) {
                    $join->on('student_session.student_id', '=', 'submit_assignment.student_id')
                        ->where('student_session.session_id', '=', $sessionId);
                })
                ->join('classes', 'classes.id', '=', 'student_session.class_id')
                ->join('sections', 'sections.id', '=', 'student_session.section_id')
                ->where('submit_assignment.homework_id', $homeworkId)
                ->where('students.is_active', 'yes')
                ->orderBy('students.firstname')
                ->select([
                    'students.admission_no',
                    'students.firstname',
                    'students.middlename',
                    'students.lastname',
                    'classes.class',
                    'sections.section',
                    'submit_assignment.message',
                    'submit_assignment.docs',
                ])
                ->get();
        }

        $q = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes');

        if ($type === 'pending_student') {
            $q->whereNotIn('students.id', function ($sub) use ($homeworkId) {
                $sub->select('submit_assignment.student_id')
                    ->from('submit_assignment')
                    ->where('submit_assignment.homework_id', $homeworkId);
            });
        }

        return $q->orderBy('students.firstname')
            ->select([
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.class',
                'sections.section',
            ])
            ->get();
    }

    /**
     * CI evaluation_report list + count_percentage.
     *
     * @param  array{class_id?:mixed,section_id?:mixed,subject_group_id?:mixed,subject_id?:mixed}  $filters
     * @return array{rows:Collection<int,object>,stats:array<int,array{total:int,completed:int,percentage:float,incomplete:int}>}
     */
    public function evaluationReport(array $filters): array
    {
        $sessionId = $this->sessionId();
        $q = DB::table('homework')
            ->join('classes', 'classes.id', '=', 'homework.class_id')
            ->join('sections', 'sections.id', '=', 'homework.section_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'homework.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->where('subject_groups.session_id', $sessionId)
            ->where('homework.session_id', $sessionId);

        $this->applyHomeworkFilters($q, $filters);

        $rows = $q->orderByDesc('homework.homework_date')
            ->select([
                'homework.*',
                'classes.class',
                'sections.section',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'subject_groups.name as subject_group_name',
            ])
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $total = $this->countActiveStudents((int) $row->class_id, (int) $row->section_id, $sessionId);
            $completed = $this->countEvaluatedStudents((int) $row->id, $sessionId);
            $percentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0.0;
            $stats[(int) $row->id] = [
                'total' => $total,
                'completed' => $completed,
                'incomplete' => max(0, $total - $completed),
                'percentage' => $percentage,
            ];
        }

        return ['rows' => $rows, 'stats' => $stats];
    }

    /**
     * CI search_homework_marks_report.
     *
     * @param  array{class_id?:mixed,section_id?:mixed,subject_group_id?:mixed,subject_id?:mixed}  $filters
     * @return Collection<int, object>
     */
    public function marksReport(array $filters): Collection
    {
        $sessionId = $this->sessionId();
        $q = DB::table('homework')
            ->join('homework_evaluation', 'homework_evaluation.homework_id', '=', 'homework.id')
            ->join('student_session', 'student_session.id', '=', 'homework_evaluation.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'homework.class_id')
            ->join('sections', 'sections.id', '=', 'homework.section_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'homework.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->where('subject_groups.session_id', $sessionId)
            ->where('homework.session_id', $sessionId);

        $this->applyHomeworkFilters($q, $filters);

        return $q->orderByDesc('homework.homework_date')
            ->select([
                'homework.id as homework_id',
                'homework.homework_date',
                'homework.submit_date',
                'homework.marks as max_marks',
                'classes.class',
                'sections.section',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'subject_groups.name as subject_group_name',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'homework_evaluation.marks as marks_obtain',
                'homework_evaluation.note',
                'homework_evaluation.date as eval_date',
            ])
            ->get();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $q
     * @param  array{class_id?:mixed,section_id?:mixed,subject_group_id?:mixed,subject_id?:mixed}  $filters
     */
    protected function applyHomeworkFilters($q, array $filters): void
    {
        $classId = (int) ($filters['class_id'] ?? 0);
        $sectionId = (int) ($filters['section_id'] ?? 0);
        $subjectGroupId = (int) ($filters['subject_group_id'] ?? 0);
        $subjectId = (int) ($filters['subject_id'] ?? 0); // subject_group_subjects.id

        if ($classId > 0 && $sectionId > 0 && $subjectGroupId > 0 && $subjectId > 0) {
            $q->where('homework.class_id', $classId)
                ->where('homework.section_id', $sectionId)
                ->where('subject_groups.id', $subjectGroupId)
                ->where('subject_group_subjects.id', $subjectId);
        } elseif ($classId > 0 && $sectionId > 0 && $subjectGroupId > 0) {
            $q->where('homework.class_id', $classId)
                ->where('homework.section_id', $sectionId)
                ->where('subject_groups.id', $subjectGroupId);
        } elseif ($classId > 0 && $sectionId > 0) {
            $q->where('homework.class_id', $classId)
                ->where('homework.section_id', $sectionId);
        } elseif ($classId > 0) {
            $q->where('homework.class_id', $classId);
        }
    }

    protected function countActiveStudents(int $classId, int $sectionId, int $sessionId): int
    {
        return (int) DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->distinct()
            ->count('student_session.student_id');
    }

    protected function countEvaluatedStudents(int $homeworkId, int $sessionId): int
    {
        return (int) DB::table('homework_evaluation')
            ->join('homework', 'homework.id', '=', 'homework_evaluation.homework_id')
            ->join('student_session', 'student_session.id', '=', 'homework_evaluation.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('homework.id', $homeworkId)
            ->where('homework.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->count();
    }

    /**
     * CI Customlib::get_searchtype keys (labels for UI).
     *
     * @return array<string, string>
     */
    public function searchTypes(): array
    {
        return [
            'today' => 'Today',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'last_3_month' => 'Last 3 Month',
            'last_6_month' => 'Last 6 Month',
            'last_12_month' => 'Last 12 Month',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
            'period' => 'Period',
        ];
    }

    /**
     * CI Customlib::get_betweendate — returns Y-m-d from/to.
     *
     * @return array{from:string,to:string}
     */
    public function dateRange(string $searchType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();

        return match ($searchType) {
            'today' => [
                'from' => $now->toDateString(),
                'to' => $now->toDateString(),
            ],
            'this_week' => $this->thisWeekRange(),
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
                'from' => (string) ($dateFrom ?: $now->toDateString()),
                'to' => (string) ($dateTo ?: $now->toDateString()),
            ],
            default => [
                'from' => $now->copy()->startOfYear()->toDateString(),
                'to' => $now->copy()->endOfYear()->toDateString(),
            ],
        };
    }

    /**
     * CI dailyassignmentreport — students with assignment counts in range.
     *
     * @param  array{class_id:mixed,section_id:mixed,subject_group_id:mixed,subject_id:mixed,search_type:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{rows:Collection<int,object>,range:array{from:string,to:string}}
     */
    public function dailyAssignmentReport(array $filters): array
    {
        $sessionId = $this->sessionId();
        $classId = (int) ($filters['class_id'] ?? 0);
        $sectionId = (int) ($filters['section_id'] ?? 0);
        $subjectGroupId = (int) ($filters['subject_group_id'] ?? 0);
        $subjectGroupSubjectId = (int) ($filters['subject_id'] ?? 0);
        $searchType = (string) ($filters['search_type'] ?? 'this_year');
        $range = $this->dateRange(
            $searchType,
            isset($filters['date_from']) ? (string) $filters['date_from'] : null,
            isset($filters['date_to']) ? (string) $filters['date_to'] : null
        );

        $rows = DB::table('daily_assignment')
            ->join('student_session', 'student_session.id', '=', 'daily_assignment.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'daily_assignment.subject_group_subject_id')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $sessionId)
            ->where('subject_group_subjects.subject_group_id', $subjectGroupId)
            ->where('subject_group_subjects.id', $subjectGroupSubjectId)
            ->whereBetween(DB::raw("date_format(daily_assignment.date,'%Y-%m-%d')"), [$range['from'], $range['to']])
            ->groupBy(
                'students.id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'classes.class',
                'sections.section'
            )
            ->orderBy('students.firstname')
            ->select([
                'students.id as student_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'classes.class',
                'sections.section',
                DB::raw('count(daily_assignment.id) as total_assignment'),
            ])
            ->get();

        return ['rows' => $rows, 'range' => $range];
    }

    /**
     * CI assignmentdetails — one student's daily rows in range for subject.
     *
     * @return Collection<int, object>
     */
    public function dailyAssignmentDetails(
        int $studentId,
        int $subjectGroupSubjectId,
        string $searchType,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): Collection {
        $range = $this->dateRange($searchType, $dateFrom, $dateTo);

        return DB::table('daily_assignment')
            ->join('student_session', 'student_session.id', '=', 'daily_assignment.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'daily_assignment.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->leftJoin('staff', 'staff.id', '=', 'daily_assignment.evaluated_by')
            ->where('students.id', $studentId)
            ->where('daily_assignment.subject_group_subject_id', $subjectGroupSubjectId)
            ->whereBetween(DB::raw("date_format(daily_assignment.date,'%Y-%m-%d')"), [$range['from'], $range['to']])
            ->orderByDesc('daily_assignment.date')
            ->select([
                'daily_assignment.*',
                'classes.class',
                'sections.section',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id as staff_employee_id',
            ])
            ->get();
    }

    /**
     * @return array{from:string,to:string}
     */
    protected function thisWeekRange(): array
    {
        $monday = now()->startOfWeek(); // Carbon default Monday when locale ISO
        $sunday = now()->endOfWeek();
        if ($monday->gt(now()->startOfDay())) {
            $monday = now()->subWeek()->startOfWeek();
            $sunday = now()->startOfWeek()->subDay()->endOfDay();
        }

        return [
            'from' => $monday->toDateString(),
            'to' => $sunday->toDateString(),
        ];
    }
}
