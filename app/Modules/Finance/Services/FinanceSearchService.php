<?php

namespace App\Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/income/incomeSearch + admin/expense/expenseSearch query parity.
 */
class FinanceSearchService
{
    /**
     * CI Customlib::get_searchtype (includes empty Select).
     *
     * @return array<string, string>
     */
    public function searchDurationTypes(): array
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
     * CI Customlib::get_betweendate / FinanceReportService::dateRange.
     *
     * @return array{from: string, to: string}
     */
    public function dateRange(string $searchType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();
        $type = $searchType === 'all' || $searchType === '' ? 'this_year' : $searchType;

        return match ($type) {
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
                'from' => $this->normalizeDate($dateFrom) ?: $now->toDateString(),
                'to' => $this->normalizeDate($dateTo) ?: $now->toDateString(),
            ],
            default => [
                'from' => $now->copy()->startOfYear()->toDateString(),
                'to' => $now->copy()->endOfYear()->toDateString(),
            ],
        };
    }

    /**
     * @param  array{button_type:string,search_type?:string,search_text?:string,date_from?:string,date_to?:string}  $filters
     * @return array{rows: Collection<int, object>, total: float, date_from:?string, date_to:?string, mode:string}
     */
    public function searchIncome(array $filters): array
    {
        $buttonType = (string) ($filters['button_type'] ?? '');

        if ($buttonType === 'search_full') {
            $text = trim((string) ($filters['search_text'] ?? ''));
            if ($text === '') {
                throw ValidationException::withMessages([
                    'search_text' => __('system.keyword').' is required.',
                ]);
            }

            $rows = DB::table('income')
                ->join('income_head', 'income.income_head_id', '=', 'income_head.id')
                ->where('income.name', 'like', '%'.$this->escapeLike($text).'%')
                ->orderByDesc('income.date')
                ->orderByDesc('income.id')
                ->select([
                    'income.id',
                    'income.name',
                    'income.invoice_no',
                    'income.date',
                    'income.amount',
                    'income_head.income_category',
                ])
                ->get();

            return [
                'rows' => $rows,
                'total' => (float) $rows->sum('amount'),
                'date_from' => null,
                'date_to' => null,
                'mode' => 'keyword',
            ];
        }

        if ($buttonType !== 'search_filter') {
            throw ValidationException::withMessages([
                'button_type' => 'Invalid search mode.',
            ]);
        }

        $searchType = (string) ($filters['search_type'] ?? '');
        if ($searchType === '') {
            throw ValidationException::withMessages([
                'search_type' => __('system.search_type').' is required.',
            ]);
        }

        $range = $this->dateRange(
            $searchType,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );

        $rows = DB::table('income')
            ->join('income_head', 'income.income_head_id', '=', 'income_head.id')
            ->whereDate('income.date', '>=', $range['from'])
            ->whereDate('income.date', '<=', $range['to'])
            ->orderByDesc('income.date')
            ->orderByDesc('income.id')
            ->select([
                'income.id',
                'income.name',
                'income.invoice_no',
                'income.date',
                'income.amount',
                'income_head.income_category',
            ])
            ->get();

        return [
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'date_from' => $range['from'],
            'date_to' => $range['to'],
            'mode' => 'filter',
        ];
    }

    /**
     * @param  array{button_type:string,search_type?:string,search_text?:string,date_from?:string,date_to?:string}  $filters
     * @return array{rows: Collection<int, object>, total: float, date_from:?string, date_to:?string, mode:string}
     */
    public function searchExpense(array $filters): array
    {
        $buttonType = (string) ($filters['button_type'] ?? '');

        if ($buttonType === 'search_full') {
            $text = trim((string) ($filters['search_text'] ?? ''));
            if ($text === '') {
                throw ValidationException::withMessages([
                    'search_text' => __('system.keyword').' is required.',
                ]);
            }

            $rows = DB::table('expenses')
                ->join('expense_head', 'expenses.exp_head_id', '=', 'expense_head.id')
                ->where('expenses.name', 'like', '%'.$this->escapeLike($text).'%')
                ->orderByDesc('expenses.date')
                ->orderByDesc('expenses.id')
                ->select([
                    'expenses.id',
                    'expenses.name',
                    'expenses.invoice_no',
                    'expenses.date',
                    'expenses.amount',
                    'expense_head.exp_category',
                ])
                ->get();

            return [
                'rows' => $rows,
                'total' => (float) $rows->sum('amount'),
                'date_from' => null,
                'date_to' => null,
                'mode' => 'keyword',
            ];
        }

        if ($buttonType !== 'search_filter') {
            throw ValidationException::withMessages([
                'button_type' => 'Invalid search mode.',
            ]);
        }

        $searchType = (string) ($filters['search_type'] ?? '');
        if ($searchType === '') {
            throw ValidationException::withMessages([
                'search_type' => __('system.search_type').' is required.',
            ]);
        }

        $range = $this->dateRange(
            $searchType,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );

        $rows = DB::table('expenses')
            ->join('expense_head', 'expenses.exp_head_id', '=', 'expense_head.id')
            ->whereDate('expenses.date', '>=', $range['from'])
            ->whereDate('expenses.date', '<=', $range['to'])
            ->orderByDesc('expenses.date')
            ->orderByDesc('expenses.id')
            ->select([
                'expenses.id',
                'expenses.name',
                'expenses.invoice_no',
                'expenses.date',
                'expenses.amount',
                'expense_head.exp_category',
            ])
            ->get();

        return [
            'rows' => $rows,
            'total' => (float) $rows->sum('amount'),
            'date_from' => $range['from'],
            'date_to' => $range['to'],
            'mode' => 'filter',
        ];
    }

    protected function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
