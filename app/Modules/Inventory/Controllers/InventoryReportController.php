<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\InventoryReportService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI report/inventory hub + stock / additem / issueinventory reports.
 */
class InventoryReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected InventoryReportService $reports,
        protected SchoolContext $school,
    ) {
    }

    public function hub(): View
    {
        abort_unless($this->canOpenHub(), 403);

        return view('shared::layouts.admin', array_merge([
            'title' => 'Inventory Report',
            'contentView' => 'inventory::admin.reports.hub',
        ], $this->navFlags()));
    }

    public function stock(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('stock_report', 'can_view'), 403);

        return $this->reportPage($request, 'Stock Report', 'inventory::admin.reports.stock', 'stockReport');
    }

    public function addItem(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('add_item_report', 'can_view'), 403);

        return $this->reportPage($request, 'Add Item Report', 'inventory::admin.reports.add_item', 'addItemReport');
    }

    public function issueItem(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('issue_item_report', 'can_view'), 403);

        return $this->reportPage($request, 'Issue Item Report', 'inventory::admin.reports.issue_item', 'issueItemReport');
    }

    protected function reportPage(Request $request, string $title, string $view, string $method): View
    {
        $filters = $this->filterInput($request);
        $rows = collect();
        $range = null;

        if ($this->shouldSearch($request)) {
            $this->validateSearch($request);
            $payload = $this->reports->{$method}($filters);
            $rows = $payload['rows'];
            $range = $payload['range'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => $title,
            'contentView' => $view,
            'filters' => $filters,
            'rows' => $rows,
            'range' => $range,
            'searched' => $this->shouldSearch($request),
            'searchTypes' => $this->reports->searchTypes(),
            'currencySymbol' => $this->school->currencySymbol(),
        ], $this->navFlags()));
    }

    protected function canOpenHub(): bool
    {
        return $this->permissions->hasPrivilege('stock_report', 'can_view')
            || $this->permissions->hasPrivilege('add_item_report', 'can_view')
            || $this->permissions->hasPrivilege('issue_item_report', 'can_view');
    }

    /**
     * @return array<string, bool>
     */
    protected function navFlags(): array
    {
        return [
            'canStockReport' => $this->permissions->hasPrivilege('stock_report', 'can_view'),
            'canAddItemReport' => $this->permissions->hasPrivilege('add_item_report', 'can_view'),
            'canIssueItemReport' => $this->permissions->hasPrivilege('issue_item_report', 'can_view'),
        ];
    }

    /**
     * @return array{search_type:mixed,date_from:mixed,date_to:mixed,search:mixed}
     */
    protected function filterInput(Request $request): array
    {
        return [
            'search_type' => $request->input('search_type', 'this_year'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
        ];
    }

    protected function shouldSearch(Request $request): bool
    {
        return $request->filled('search') || $request->isMethod('post');
    }

    protected function validateSearch(Request $request): void
    {
        $rules = [
            'search_type' => ['required', 'in:'.implode(',', array_keys($this->reports->searchTypes()))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
        if ($request->input('search_type') === 'period') {
            $rules['date_from'] = ['required', 'date'];
            $rules['date_to'] = ['required', 'date', 'after_or_equal:date_from'];
        }
        $request->validate($rules);
    }
}
