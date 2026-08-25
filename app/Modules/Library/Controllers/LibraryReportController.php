<?php

namespace App\Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Library\Services\LibraryReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Report::library hub + book issue / due / inventory / issue-return reports.
 */
class LibraryReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LibraryReportService $reports,
    ) {
    }

    public function hub(): View
    {
        abort_unless($this->canOpenHub(), 403);

        return view('shared::layouts.admin', array_merge([
            'title' => 'Library Report',
            'contentView' => 'library::admin.reports.hub',
        ], $this->navFlags()));
    }

    public function bookIssue(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('book_issue_report', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();

        $filters = $this->filterInput($request);
        $rows = collect();
        $range = null;

        if ($this->shouldSearch($request)) {
            $this->validateSearch($request, withMemberType: true);
            $payload = $this->reports->bookIssueReport($filters);
            $rows = $payload['rows'];
            $range = $payload['range'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => 'Book Issue Report',
            'contentView' => 'library::admin.reports.book_issue',
            'filters' => $filters,
            'rows' => $rows,
            'range' => $range,
            'searchTypes' => $this->reports->searchTypes(),
            'memberTypes' => $this->reports->memberTypes(),
        ], $this->navFlags()));
    }

    public function bookDue(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('book_due_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = collect();
        $range = null;

        if ($this->shouldSearch($request)) {
            $this->validateSearch($request, withMemberType: true);
            $payload = $this->reports->bookDueReport($filters);
            $rows = $payload['rows'];
            $range = $payload['range'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => 'Book Due Report',
            'contentView' => 'library::admin.reports.book_due',
            'filters' => $filters,
            'rows' => $rows,
            'range' => $range,
            'searchTypes' => $this->reports->searchTypes(),
            'memberTypes' => $this->reports->memberTypes(),
        ], $this->navFlags()));
    }

    public function bookInventory(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('book_inventory_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = collect();
        $range = null;

        if ($this->shouldSearch($request)) {
            $this->validateSearch($request, withMemberType: false);
            $payload = $this->reports->bookInventoryReport($filters);
            $rows = $payload['rows'];
            $range = $payload['range'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => 'Book Inventory Report',
            'contentView' => 'library::admin.reports.book_inventory',
            'filters' => $filters,
            'rows' => $rows,
            'range' => $range,
            'searchTypes' => $this->reports->searchTypes(),
        ], $this->navFlags()));
    }

    public function issueReturn(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('book_issue_return_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = collect();
        $range = null;

        if ($this->shouldSearch($request)) {
            $this->validateSearch($request, withMemberType: false);
            $payload = $this->reports->issueReturnReport($filters);
            $rows = $payload['rows'];
            $range = $payload['range'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => 'Book Issue Return Report',
            'contentView' => 'library::admin.reports.issue_return',
            'filters' => $filters,
            'rows' => $rows,
            'range' => $range,
            'searchTypes' => $this->reports->searchTypes(),
        ], $this->navFlags()));
    }

    protected function canOpenHub(): bool
    {
        return $this->permissions->hasPrivilege('book_issue_report', 'can_view')
            || $this->permissions->hasPrivilege('book_due_report', 'can_view')
            || $this->permissions->hasPrivilege('book_inventory_report', 'can_view')
            || $this->permissions->hasPrivilege('book_issue_return_report', 'can_view');
    }

    /**
     * @return array<string, bool>
     */
    protected function navFlags(): array
    {
        return [
            'canBookIssueReport' => $this->permissions->hasPrivilege('book_issue_report', 'can_view'),
            'canBookDueReport' => $this->permissions->hasPrivilege('book_due_report', 'can_view'),
            'canBookInventoryReport' => $this->permissions->hasPrivilege('book_inventory_report', 'can_view'),
            'canIssueReturnReport' => $this->permissions->hasPrivilege('book_issue_return_report', 'can_view'),
        ];
    }

    /**
     * @return array{search_type:mixed,date_from:mixed,date_to:mixed,members_type:mixed,search:mixed}
     */
    protected function filterInput(Request $request): array
    {
        return [
            'search_type' => $request->input('search_type', 'this_year'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'members_type' => $request->input('members_type', ''),
            'search' => $request->input('search'),
        ];
    }

    protected function shouldSearch(Request $request): bool
    {
        return $request->filled('search') || $request->isMethod('post');
    }

    protected function validateSearch(Request $request, bool $withMemberType): void
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

        if ($withMemberType) {
            $rules['members_type'] = ['nullable', 'in:student,teacher'];
        }

        $request->validate($rules);
    }
}
