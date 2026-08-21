<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CI Report online examinations hub + exams report (dtonlineexamReport).
 * Deferred: attempt report, rank report, result report, class-teacher scope, DataTables AJAX.
 */
class OnlineExamReportService
{
    public function __construct(
        protected SchoolContext $school,
    ) {
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

        if ($dateType === 'exam_from_date') {
            $query->whereRaw("DATE_FORMAT(onlineexam.exam_from,'%Y-%m-%d') BETWEEN ? AND ?", [$range['from'], $range['to']]);
        } elseif ($dateType === 'exam_to_date') {
            $query->whereRaw("DATE_FORMAT(onlineexam.exam_to,'%Y-%m-%d') BETWEEN ? AND ?", [$range['from'], $range['to']]);
        } else {
            $query->whereRaw("DATE_FORMAT(onlineexam.created_at,'%Y-%m-%d') BETWEEN ? AND ?", [$range['from'], $range['to']]);
        }

        return $query
            ->orderBy('onlineexam.id')
            ->get()
            ->all();
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
