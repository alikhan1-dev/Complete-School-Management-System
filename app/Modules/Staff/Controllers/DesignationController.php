<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Staff\Services\DesignationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Designation — designation master list/add/edit/delete.
 */
class DesignationController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DesignationService $designations,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('designation', 'can_view'), 403);

        if ($request->isMethod('post')) {
            return $this->persist($request);
        }

        return $this->page(null);
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('designation', 'can_edit'), 403);

        return $this->page($this->designations->find($id));
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('designation', 'can_delete'), 403);

        $this->designations->delete($this->designations->find($id));

        return redirect()
            ->route('staff.designations.index')
            ->with('success', __('system.success_message'));
    }

    private function persist(Request $request): RedirectResponse
    {
        $designationId = (int) $request->input('designationid', 0);
        $isUpdate = $designationId > 0;

        abort_unless(
            $isUpdate
                ? $this->permissions->hasPrivilege('designation', 'can_edit')
                : $this->permissions->hasPrivilege('designation', 'can_add'),
            403
        );

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:200'],
            'designationid' => ['nullable', 'integer'],
        ], [
            'type.required' => 'The '.__('system.name').' field is required.',
        ]);

        $name = trim((string) $validated['type']);
        if ($this->designations->nameExists($name, $designationId)) {
            return back()
                ->withInput()
                ->withErrors(['type' => 'Record already exists']);
        }

        if ($isUpdate) {
            $this->designations->update($this->designations->find($designationId), $name);
        } else {
            $this->designations->create($name);
        }

        return redirect()
            ->route('staff.designations.index')
            ->with('success', __('system.success_message'));
    }

    private function page(?object $editing): View
    {
        $canAdd = $this->permissions->hasPrivilege('designation', 'can_add');
        $canEdit = $this->permissions->hasPrivilege('designation', 'can_edit');

        return view('shared::layouts.admin', [
            'title' => $editing ? __('system.edit_designation') : __('system.add_designation'),
            'contentView' => 'staff::admin.designations.index',
            'results' => $this->designations->listActive(),
            'editing' => $editing,
            'canAdd' => $canAdd,
            'canEdit' => $canEdit,
            'canDelete' => $this->permissions->hasPrivilege('designation', 'can_delete'),
            'showForm' => $editing !== null || $canAdd || $canEdit,
        ]);
    }
}
