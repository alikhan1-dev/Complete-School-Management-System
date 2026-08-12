<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\ExamGroupService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Examgroup — exam group CRUD.
 */
class ExamGroupController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamGroupService $examGroups
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('exam_group', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Exam Group',
            'contentView' => 'exams::admin.exam_groups.index',
            'examGroups' => $this->examGroups->listGroups(),
            'examTypes' => $this->examGroups->examTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_group', 'can_add'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'exam_type' => ['required', 'string', 'in:'.implode(',', array_keys($this->examGroups->examTypes()))],
            'description' => ['nullable', 'string'],
        ]);

        $this->examGroups->createGroup($data);

        return redirect()->route('exams.exam_groups.index')->with('success', 'Exam group created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('exam_group', 'can_edit'), 403);

        $group = $this->examGroups->findGroup($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Exam Group',
            'contentView' => 'exams::admin.exam_groups.edit',
            'examGroups' => $this->examGroups->listGroups(),
            'examTypes' => $this->examGroups->examTypes(),
            'group' => $group,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_group', 'can_edit'), 403);

        $group = $this->examGroups->findGroup($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'exam_type' => ['required', 'string', 'in:'.implode(',', array_keys($this->examGroups->examTypes()))],
            'description' => ['nullable', 'string'],
        ]);

        $this->examGroups->updateGroup($group, $data);

        return redirect()->route('exams.exam_groups.index')->with('success', 'Exam group updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_group', 'can_delete'), 403);

        $group = $this->examGroups->findGroup($id);
        $this->examGroups->deleteGroup($group);

        return redirect()->route('exams.exam_groups.index')->with('success', 'Exam group deleted successfully.');
    }
}
