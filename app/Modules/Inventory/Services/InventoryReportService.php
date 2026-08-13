<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI report/inventory hub + stock / add-item / issue reports.
 * Form POST search replaces CI DataTables AJAX.
 */
class InventoryReportService
{
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
     * @return array{from:string,to:string}
     */
    public function dateRange(string $searchType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();

        return match ($searchType) {
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
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{rows:Collection<int,object>,range:array{from:string,to:string}}
     */
    public function stockReport(array $filters): array
    {
        $range = $this->rangeFromFilters($filters);

        $rows = DB::table('item_stock')
            ->join('item', 'item.id', '=', 'item_stock.item_id')
            ->join('item_category', 'item_category.id', '=', 'item.item_category_id')
            ->join('item_supplier', 'item_supplier.id', '=', 'item_stock.supplier_id')
            ->leftJoin('item_store', 'item_store.id', '=', 'item_stock.store_id')
            ->whereBetween(DB::raw("DATE(item_stock.date)"), [$range['from'], $range['to']])
            ->groupBy('item.id', 'item.name', 'item_category.item_category')
            ->orderBy('item.name')
            ->select([
                'item.id',
                'item.name',
                'item_category.item_category',
                DB::raw('MAX(item_supplier.item_supplier) as item_supplier'),
                DB::raw('MAX(item_store.item_store) as item_store'),
                DB::raw('SUM(item_stock.quantity) as total_quantity'),
                DB::raw('(SELECT IFNULL(SUM(quantity),0) FROM item_issue WHERE item_issue.item_id = item.id AND item_issue.is_returned = 1) as total_issued'),
            ])
            ->get()
            ->map(function (object $row) {
                $available = (float) $row->total_quantity - (float) $row->total_issued;
                $row->available_quantity = $available < 0 ? 0 : $available;

                return $row;
            });

        return ['rows' => $rows, 'range' => $range];
    }

    /**
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{rows:Collection<int,object>,range:array{from:string,to:string}}
     */
    public function addItemReport(array $filters): array
    {
        $range = $this->rangeFromFilters($filters);

        $rows = DB::table('item_stock')
            ->join('item', 'item.id', '=', 'item_stock.item_id')
            ->join('item_category', 'item_category.id', '=', 'item.item_category_id')
            ->join('item_supplier', 'item_supplier.id', '=', 'item_stock.supplier_id')
            ->leftJoin('item_store', 'item_store.id', '=', 'item_stock.store_id')
            ->whereBetween('item_stock.date', [$range['from'], $range['to']])
            ->orderByDesc('item_stock.date')
            ->select([
                'item.name',
                'item_category.item_category',
                'item_supplier.item_supplier',
                'item_store.item_store',
                'item_stock.quantity',
                'item_stock.purchase_price',
                'item_stock.date',
            ])
            ->get();

        return ['rows' => $rows, 'range' => $range];
    }

    /**
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{rows:Collection<int,object>,range:array{from:string,to:string}}
     */
    public function issueItemReport(array $filters): array
    {
        $range = $this->rangeFromFilters($filters);

        $rows = DB::table('item_issue')
            ->join('item', 'item.id', '=', 'item_issue.item_id')
            ->join('item_category', 'item_category.id', '=', 'item.item_category_id')
            ->join('staff', 'staff.id', '=', 'item_issue.issue_to')
            ->join('staff as issueby', 'issueby.id', '=', 'item_issue.issue_by')
            ->whereBetween('item_issue.issue_date', [$range['from'], $range['to']])
            ->orderByDesc('item_issue.issue_date')
            ->select([
                'item.name as item_name',
                'item_issue.note',
                'item_category.item_category',
                'item_issue.issue_date',
                'item_issue.return_date',
                'item_issue.quantity',
                'staff.name as staff_name',
                'staff.surname',
                'staff.employee_id',
                'issueby.name as issueby_staff_name',
                'issueby.surname as issueby_surname',
                'issueby.employee_id as issueby_employee_id',
            ])
            ->get();

        return ['rows' => $rows, 'range' => $range];
    }

    /**
     * @param  array{search_type?:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{from:string,to:string}
     */
    protected function rangeFromFilters(array $filters): array
    {
        $type = (string) ($filters['search_type'] ?? 'this_year');
        if ($type === '' || ! array_key_exists($type, $this->searchTypes())) {
            $type = 'this_year';
        }

        return $this->dateRange(
            $type,
            isset($filters['date_from']) ? (string) $filters['date_from'] : null,
            isset($filters['date_to']) ? (string) $filters['date_to'] : null,
        );
    }
}
