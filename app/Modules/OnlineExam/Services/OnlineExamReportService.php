<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CI Report online examinations hub + exams + attempt + exam-wise result reports.
 * Deferred: rank report, class-teacher scope, DataTables AJAX / modal print.
 */
class OnlineExamReportService
{
    public function __construct(
        protected SchoolContext $school,
        protected CurrentSessionResolver $currentSession,
    ) {
    }

    /**
     * CI Onlineexam_model::get() list for current session (report dropdown).
     *
     * @return list<object>
     */
    public function examsForCurrentSession(): array
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            return [];
        }

        return DB::table('onlineexam')
            ->where('session_id', $sessionId)
            ->orderByDesc('id')
            ->select(['id', 'exam', 'attempt'])
            ->get()
            ->all();
    }

    /**
     * Sections linked to a class (for result-report filter restore).
     *
     * @return list<object{section_id:int,section:string}>
     */
    public function sectionsForClass(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }

        return DB::table('class_sections')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('class_sections.class_id', $classId)
            ->orderBy('sections.section')
            ->select([
                'sections.id as section_id',
                'sections.section',
            ])
            ->get()
            ->all();
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    /**
     * CI Customlib::get_searchtype (includes empty Select).
     *
     * @return array<string, string>
     */
    public function searchTypes(): array
    {
        return [
            '' => (string) __('system.select'),
            'today' => (string) __('system.today'),
            'this_week' => (string) __('system.this_week'),
            'last_week' => (string) __('system.last_week'),
            'this_month' => (string) __('system.this_month'),
            'last_month' => (string) __('system.last_month'),
            'last_3_month' => (string) __('system.last_3_month'),
            'last_6_month' => (string) __('system.last_6_month'),
            'last_12_month' => (string) __('system.last_12_month'),
            'this_year' => (string) __('system.this_year'),
            'last_year' => (string) __('system.last_year'),
            'period' => (string) __('system.period'),
        ];
    }

    /**
     * CI Customlib::date_type.
     *
     * @return array<string, string>
     */
    public function dateTypes(): array
    {
        return [
            '' => (string) __('system.all'),
            'exam_from_date' => (string) __('system.exam_from_date'),
            'exam_to_date' => (string) __('system.exam_to_date'),
        ];
    }

    /**
     * @return array{from: string, to: string}
     */
    public function dateRange(string $searchType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();
        $resolved = $searchType !== '' ? $searchType : 'this_year';

        return match ($resolved) {
            'today' => ['from' => $now->toDateString(), 'to' => $now->toDateString()],
            'this_week' => [
                'from' => $now->copy()->startOfWeek()->toDateString(),
                'to' => $now->copy()->endOfWeek()->toDateString(),
            ],
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
                'from' => $this->normalizeInputDate($dateFrom) ?? $now->toDateString(),
                'to' => $this->normalizeInputDate($dateTo) ?? $now->toDateString(),
            ],
            default => [
                'from' => $now->copy()->startOfYear()->toDateString(),
                'to' => $now->copy()->endOfYear()->toDateString(),
            ],
        };
    }

    public function formatDateTime(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        $format = $this->school->dateFormat() ?: 'd/m/Y';

        return Carbon::parse((string) $value)->format($format.' H:i:s');
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
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
     * CI Onlineexam_model::dtonlineexamReport (+ Report::dtexamreportlist display fields).
     * Class-teacher scope deferred. No onlineexam.session_id filter (CI parity).
     *
     * @return list<object>
     */
    public function examsReport(string $searchType, string $dateType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $range = $this->dateRange($searchType, $dateFrom, $dateTo);

        $query = DB::table('onlineexam')
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('onlineexam_students')
                    ->join('student_session', 'student_session.id', '=', 'onlineexam_students.student_session_id')
                    ->whereColumn('onlineexam_students.onlineexam_id', 'onlineexam.id');
            })
            ->select([
                'onlineexam.id',
                'onlineexam.exam',
                'onlineexam.attempt',
                'onlineexam.exam_from',
                'onlineexam.exam_to',
                'onlineexam.duration',
                'onlineexam.is_active',
                'onlineexam.publish_result',
                'onlineexam.created_at',
                DB::raw('(SELECT COUNT(*) FROM onlineexam_students WHERE onlineexam_students.onlineexam_id = onlineexam.id) as assign'),
                DB::raw('(SELECT COUNT(*) FROM onlineexam_questions WHERE onlineexam_questions.onlineexam_id = onlineexam.id) as questions'),
            ]);

        $this->applyExamDateFilter($query, $dateType, $range);

        return $query
            ->orderBy('onlineexam.id')
            ->get()
            ->all();
    }

    /**
     * CI Onlineexam_model::onlineexamatteptreport + Report::dtexamattemptreport display.
     * Class-teacher empty-result trap deferred. Current session + active students required.
     *
     * @return list<array{
     *     student_id: int,
     *     admission_no: string,
     *     firstname: string,
     *     middlename: string,
     *     lastname: string,
     *     class: string,
     *     section: string,
     *     exams: list<array{exam:string,exam_from:mixed,exam_to:mixed,duration:string,is_active:int,publish_result:int}>
     * }>
     */
    public function attemptReport(string $searchType, string $dateType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            return [];
        }

        $range = $this->dateRange($searchType, $dateFrom, $dateTo);

        $query = DB::table('student_session')
            ->join('onlineexam_students', 'onlineexam_students.student_session_id', '=', 'student_session.id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('onlineexam', 'onlineexam.id', '=', 'onlineexam_students.onlineexam_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.firstname')
            ->orderBy('students.id')
            ->orderBy('onlineexam.id')
            ->select([
                'students.id as student_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.class',
                'sections.section',
                'onlineexam.id as exam_id',
                'onlineexam.exam',
                'onlineexam.exam_from',
                'onlineexam.exam_to',
                'onlineexam.duration',
                'onlineexam.is_active',
                'onlineexam.publish_result',
            ]);

        $this->applyExamDateFilter($query, $dateType, $range);

        $grouped = [];
        foreach ($query->get() as $row) {
            $sid = (int) $row->student_id;
            if (! isset($grouped[$sid])) {
                $grouped[$sid] = [
                    'student_id' => $sid,
                    'admission_no' => (string) $row->admission_no,
                    'firstname' => (string) $row->firstname,
                    'middlename' => (string) ($row->middlename ?? ''),
                    'lastname' => (string) ($row->lastname ?? ''),
                    'class' => (string) $row->class,
                    'section' => (string) $row->section,
                    'exams' => [],
                ];
            }
            $grouped[$sid]['exams'][] = [
                'exam' => (string) $row->exam,
                'exam_from' => $row->exam_from,
                'exam_to' => $row->exam_to,
                'duration' => (string) $row->duration,
                'is_active' => (int) $row->is_active,
                'publish_result' => (int) $row->publish_result,
            ];
        }

        return array_values($grouped);
    }

    /**
     * CI Onlineexamresult_model::getStudentByExam (+ Onlineexam::dtreportlist display fields).
     * Remaining attempts = exam.attempt - onlineexam_attempts count (CI parity; may be negative).
     *
     * @return list<object>
     */
    public function resultReport(int $examId, int $classId, int $sectionId): array
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0 || $examId <= 0 || $classId <= 0 || $sectionId <= 0) {
            return [];
        }

        return DB::table('student_session')
            ->join('onlineexam_students', 'onlineexam_students.student_session_id', '=', 'student_session.id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('onlineexam', 'onlineexam.id', '=', 'onlineexam_students.onlineexam_id')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $sessionId)
            ->where('onlineexam_students.onlineexam_id', $examId)
            ->orderByDesc('onlineexam_students.is_attempted')
            ->orderBy('students.firstname')
            ->select([
                'students.id as student_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.class',
                'sections.section',
                'onlineexam.id as exam_id',
                'onlineexam.attempt',
                'onlineexam_students.id as onlineexam_student_id',
                'onlineexam_students.student_session_id',
                'onlineexam_students.is_attempted',
                DB::raw('(SELECT COUNT(*) FROM onlineexam_attempts WHERE onlineexam_attempts.onlineexam_student_id = onlineexam_students.id) as total_counter'),
            ])
            ->get()
            ->all();
    }

    /**
     * @param  array{from: string, to: string}  $range
     */
    protected function applyExamDateFilter($query, string $dateType, array $range): void
    {
        if ($dateType === 'exam_from_date') {
            $query->whereRaw("DATE_FORMAT(onlineexam.exam_from,'%Y-%m-%d') BETWEEN ? AND ?", [$range['from'], $range['to']]);
        } elseif ($dateType === 'exam_to_date') {
            $query->whereRaw("DATE_FORMAT(onlineexam.exam_to,'%Y-%m-%d') BETWEEN ? AND ?", [$range['from'], $range['to']]);
        } else {
            $query->whereRaw("DATE_FORMAT(onlineexam.created_at,'%Y-%m-%d') BETWEEN ? AND ?", [$range['from'], $range['to']]);
        }
    }

    protected function normalizeInputDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
