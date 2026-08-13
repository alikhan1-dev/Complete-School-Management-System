<?php

namespace App\Modules\Leave\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leave\Services\LeaveTypeService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/leavetypes — leave type master CRUD.
 */
class LeaveTypeController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LeaveTypeService $types,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('leave_types', 'can_view'), 403);

        return $this->formPage(null, 'Add Leave Type');
    }

    public function store(Request $request): RedirectResponse|View
    {
        $leavetypeid = (int) $request->input('leavetypeid', 0);

        if ($leavetypeid > 0) {
            abort_unless($this->permissions->hasPrivilege('leave_types', 'can_edit'), 403);
        } else {
            abort_unless($this->permissions->hasPrivilege('leave_types', 'can_add'), 403);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:200'],
            'leavetypeid' => ['nullable', 'integer'],
        ]);

        if ($leavetypeid > 0) {
            $this->types->update($this->types->find($leavetypeid), ['type' => $validated['type']]);
        } else {
            $this->types->create(['type' => $validated['type']]);
        }

        return redirect()
            ->route('leave.types.index')
            ->with('success', 'Record saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('leave_types', 'can_edit'), 403);

        return $this->formPage($this->types->find($id), 'Edit Leave Type');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('leave_types', 'can_delete'), 403);

        $this->types->delete($this->types->find($id));

        return redirect()
            ->route('leave.types.index')
            ->with('success', 'Leave type deleted successfully.');
    }

    protected function formPage(mixed $editing, string $title): View
    {
        return view('shared::layouts.admin', [
            'title' => $title,
            'contentView' => 'leave::admin.leavetypes',
            'pageTitle' => $title,
            'leavetype' => $this->types->listAll(),
            'result' => $editing,
            'canAdd' => $this->permissions->hasPrivilege('leave_types', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('leave_types', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('leave_types', 'can_delete'),
        ]);
    }
}
