<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\IssueItemService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/issueitem — list/create/return/delete (form POST).
 */
class IssueItemController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected IssueItemService $issues,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('issue_item', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Issue Item',
            'contentView' => 'inventory::admin.issue.index',
            'issues' => $this->issues->listIssues(),
            'canAdd' => $this->permissions->hasPrivilege('issue_item', 'can_add'),
            'canDelete' => $this->permissions->hasPrivilege('issue_item', 'can_delete'),
        ]);
    }

    public function create(): View
    {
        abort_unless($this->permissions->hasPrivilege('issue_item', 'can_add'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Add Issue Item',
            'contentView' => 'inventory::admin.issue.create',
            'categories' => $this->issues->categoriesForSelect(),
            'roles' => $this->issues->rolesForSelect(),
            'issueByStaff' => $this->issues->issueByStaff(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('issue_item', 'can_add'), 403);

        $data = $request->validate([
            'account_type' => ['required', 'integer'],
            'issue_to' => ['required', 'integer'],
            'issue_by' => ['required', 'integer'],
            'issue_date' => ['required', 'date'],
            'return_date' => ['nullable', 'date'],
            'item_category_id' => ['required', 'integer'],
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        $this->issues->create($data);

        return redirect()->route('inventory.issue.index')->with('success', 'Item issued successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('issue_item', 'can_delete'), 403);

        $this->issues->delete($this->issues->find($id));

        return redirect()->route('inventory.issue.index')->with('success', 'Issue deleted successfully.');
    }

    public function returnItem(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('issue_item', 'can_edit') || $this->permissions->hasPrivilege('issue_item', 'can_view'), 403);

        $validated = $request->validate([
            'item_issue_id' => ['required', 'integer'],
        ]);
        $this->issues->markReturned((int) $validated['item_issue_id']);

        return redirect()->route('inventory.issue.index')->with('success', 'Item returned successfully.');
    }

    public function getUser(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('issue_item', 'can_view') || $this->permissions->hasPrivilege('issue_item', 'can_add'), 403);

        $validated = $request->validate([
            'usertype' => ['required', 'integer'],
        ]);

        return response()->json([
            'usertype' => (int) $validated['usertype'],
            'result' => $this->issues->staffByRole((int) $validated['usertype']),
        ]);
    }
}
