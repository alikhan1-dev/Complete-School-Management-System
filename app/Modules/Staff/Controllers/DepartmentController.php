<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Staff\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Department — department master list/add/edit/delete.
 */
class DepartmentController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DepartmentService $departments,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('department', 'can_view'), 403);

        if ($request->isMethod('post')) {
            return $this->persist($request);
        }

        return $this->page(null);
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('department', 'can_edit'), 403);

        return $this->page($this->departments->find($id));
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('department', 'can_delete'), 403);

        $this->departments->delete($this->departments->find($id));

        return redirect()
            ->route('staff.departments.index')
            ->with('success', __('system.success_message'));
    }

    private function persist(Request $request): RedirectResponse
    {
        $departmentId = (int) $request->input('departmenttypeid', 0);
        $isUpdate = $departmentId > 0;

        abort_unless(
            $isUpdate
                ? $this->permissions->hasPrivilege('department', 'can_edit')
                : $this->permissions->hasPrivilege('department', 'can_add'),
            403
        );

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:200'],
            'departmenttypeid' => ['nullable', 'integer'],
        ], [
            'type.required' => 'The '.__('system.name').' field is required.',
        ]);

        $name = trim((string) $validated['type']);
        if ($this->departments->nameExists($name, $departmentId)) {
            return back()
                ->withInput()
                ->withErrors(['type' => 'Record already exists']);
        }

        if ($isUpdate) {
            $this->departments->update($this->departments->find($departmentId), $name);
        } else {
            $this->departments->create($name);
        }

        return redirect()
            ->route('staff.departments.index')
            ->with('success', __('system.success_message'));
    }

    private function page(?object $editing): View
    {
        $canAdd = $this->permissions->hasPrivilege('department', 'can_add');
        $canEdit = $this->permissions->hasPrivilege('department', 'can_edit');

        return view('shared::layouts.admin', [
            'title' => $editing ? __('system.edit_department') : __('system.add_department'),
            'contentView' => 'staff::admin.departments.index',
            'results' => $this->departments->all(),
            'editing' => $editing,
            'canAdd' => $canAdd,
            'canEdit' => $canEdit,
            'canDelete' => $this->permissions->hasPrivilege('department', 'can_delete'),
            'showForm' => $editing !== null || $canAdd || $canEdit,
        ]);
    }
}
