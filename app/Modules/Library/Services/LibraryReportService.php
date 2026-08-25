<?php

namespace App\Modules\Library\Services;

use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Report library hub + book issue / due / inventory / issue-return reports.
 * Deferred: superadmin_visible staff filtering, DataTables AJAX endpoints.
 */
class LibraryReportService
{
    public function __construct(
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    /**
     * CI Report::studentbookissuereport access_denied when restricted teacher has no matrix.
     */
    public function assertHasClassSectionMatrix(): void
    {
        if ($this->classTeacherScope->isRestricted() && $this->classTeacherScope->myClassSectionMap() === []) {
            abort(403);
        }
    }

    /**
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
     * @return array<string, string>
     */
    public function memberTypes(): array
    {
        return [
            '' => 'All',
            'student' => 'Student',
            'teacher' => 'Staff',
        ];
    }

    /**
     * CI Customlib::get_betweendate — Y-m-d from/to.
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
     * CI bookissue_model::studentBookIssue_report — currently issued by issue_date.
     *
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed,members_type?:mixed}  $filters
     * @return array{rows:Collection<int,object>,range:array{from:string,to:string}}
     */
    public function bookIssueReport(array $filters): array
    {
        $range = $this->rangeFromFilters($filters);
        $memberType = trim((string) ($filters['members_type'] ?? ''));

        $query = $this->issuedBooksBaseQuery()
            ->whereRaw("DATE(book_issues.issue_date) BETWEEN ? AND ?", [$range['from'], $range['to']]);

        if ($memberType !== '') {
            $query->where('libarary_members.member_type', $memberType);
        }

        return [
            'rows' => $this->mapIssueRows($query->orderByDesc('book_issues.issue_date')->get()),
            'range' => $range,
        ];
    }

    /**
     * CI bookissue_model::bookduereport — currently issued by duereturn_date.
     *
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed,members_type?:mixed}  $filters
     * @return array{rows:Collection<int,object>,range:array{from:string,to:string}}
     */
    public function bookDueReport(array $filters): array
    {
        $range = $this->rangeFromFilters($filters);
        $memberType = trim((string) ($filters['members_type'] ?? ''));

        $query = $this->issuedBooksBaseQuery()
            ->whereRaw("DATE(book_issues.duereturn_date) BETWEEN ? AND ?", [$range['from'], $range['to']]);

        if ($memberType !== '') {
            $query->where('libarary_members.member_type', $memberType);
        }

        return [
            'rows' => $this->mapIssueRows($query->orderBy('book_issues.duereturn_date')->get()),
            'range' => $range,
        ];
    }

    /**
     * CI book_model::bookinventory — books by postdate.
     *
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{rows:Collection<int,object>,range:array{from:string,to:string}}
     */
    public function bookInventoryReport(array $filters): array
    {
        $range = $this->rangeFromFilters($filters);

        $rows = DB::table('books')
            ->leftJoin(DB::raw('(SELECT book_id, COUNT(*) as total_issue FROM book_issues WHERE is_returned = 0 GROUP BY book_id) as book_count'), 'book_count.book_id', '=', 'books.id')
            ->whereRaw("DATE(books.postdate) BETWEEN ? AND ?", [$range['from'], $range['to']])
            ->orderBy('books.book_title')
            ->select([
                'books.id',
                'books.book_title',
                'books.book_no',
                'books.isbn_no',
                'books.publish',
                'books.author',
                'books.subject',
                'books.rack_no',
                'books.qty',
                'books.perunitcost',
                'books.postdate',
                'books.description',
                DB::raw('IFNULL(book_count.total_issue, 0) as total_issue'),
            ])
            ->get()
            ->map(static function (object $row): object {
                $qty = (int) ($row->qty ?? 0);
                $issued = (int) ($row->total_issue ?? 0);
                $row->available_qty = max(0, $qty - $issued);
                $row->issued_qty = $issued;

                return $row;
            });

        return [
            'rows' => $rows,
            'range' => $range,
        ];
    }

    /**
     * CI bookissue_model::getissuereturnMemberBooks — returned issues by issue_date.
     *
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{rows:Collection<int,object>,range:array{from:string,to:string}}
     */
    public function issueReturnReport(array $filters): array
    {
        $range = $this->rangeFromFilters($filters);

        $rows = DB::table('book_issues')
            ->leftJoin('books', 'books.id', '=', 'book_issues.book_id')
            ->leftJoin('libarary_members', 'libarary_members.id', '=', 'book_issues.member_id')
            ->leftJoin('students', function ($join) {
                $join->on('students.id', '=', 'libarary_members.member_id')
                    ->where('libarary_members.member_type', '=', 'student');
            })
            ->leftJoin('staff', function ($join) {
                $join->on('staff.id', '=', 'libarary_members.member_id')
                    ->where('libarary_members.member_type', '=', 'teacher');
            })
            ->where('book_issues.is_returned', 1)
            ->whereRaw("DATE(book_issues.issue_date) BETWEEN ? AND ?", [$range['from'], $range['to']])
            ->orderByDesc('book_issues.return_date')
            ->select([
                'book_issues.id',
                'book_issues.issue_date',
                'book_issues.return_date',
                'books.book_title',
                'books.book_no',
                'libarary_members.id as members_id',
                'libarary_members.library_card_no',
                'libarary_members.member_type',
                'students.firstname as student_firstname',
                'students.middlename as student_middlename',
                'students.lastname as student_lastname',
                'students.admission_no',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id',
            ])
            ->get();

        return [
            'rows' => $this->mapIssueRows($rows, includeDue: false),
            'range' => $range,
        ];
    }

    /**
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{from:string,to:string}
     */
    protected function rangeFromFilters(array $filters): array
    {
        return $this->dateRange(
            (string) ($filters['search_type'] ?? 'this_year'),
            isset($filters['date_from']) ? (string) $filters['date_from'] : null,
            isset($filters['date_to']) ? (string) $filters['date_to'] : null
        );
    }

    protected function issuedBooksBaseQuery()
    {
        return DB::table('book_issues')
            ->leftJoin('books', 'books.id', '=', 'book_issues.book_id')
            ->leftJoin('libarary_members', 'libarary_members.id', '=', 'book_issues.member_id')
            ->leftJoin('students', function ($join) {
                $join->on('students.id', '=', 'libarary_members.member_id')
                    ->where('libarary_members.member_type', '=', 'student');
            })
            ->leftJoin('staff', function ($join) {
                $join->on('staff.id', '=', 'libarary_members.member_id')
                    ->where('libarary_members.member_type', '=', 'teacher');
            })
            ->where('book_issues.is_returned', 0)
            ->select([
                'book_issues.id',
                'book_issues.issue_date',
                'book_issues.duereturn_date',
                'book_issues.return_date',
                'books.book_title',
                'books.book_no',
                'libarary_members.id as members_id',
                'libarary_members.library_card_no',
                'libarary_members.member_type',
                'students.firstname as student_firstname',
                'students.middlename as student_middlename',
                'students.lastname as student_lastname',
                'students.admission_no',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id',
            ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    protected function mapIssueRows(Collection $rows, bool $includeDue = true): Collection
    {
        return $rows->map(function (object $row) use ($includeDue): object {
            if (($row->member_type ?? '') === 'student') {
                $name = trim(implode(' ', array_filter([
                    (string) ($row->student_firstname ?? ''),
                    (string) ($row->student_middlename ?? ''),
                    (string) ($row->student_lastname ?? ''),
                ])));
                $admission = (string) ($row->admission_no ?? '');
                $row->issue_by = $name.($admission !== '' ? ' ('.$admission.')' : '');
                $row->admission_display = $admission;
                $row->member_type_label = 'Student';
            } else {
                $name = trim(implode(' ', array_filter([
                    (string) ($row->staff_name ?? ''),
                    (string) ($row->staff_surname ?? ''),
                ])));
                $employeeId = (string) ($row->employee_id ?? '');
                $row->issue_by = $name.($employeeId !== '' ? ' ('.$employeeId.')' : '');
                $row->admission_display = $employeeId;
                $row->member_type_label = 'Staff';
            }

            if (! $includeDue) {
                unset($row->duereturn_date);
            }

            return $row;
        });
    }
}
