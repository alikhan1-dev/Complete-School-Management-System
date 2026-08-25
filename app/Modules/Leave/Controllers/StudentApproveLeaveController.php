<?php

namespace App\Modules\Leave\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leave\Services\StudentApplyLeaveService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/approve_leave — student leave list / add / edit / status / delete.
 * Form pages instead of CI AJAX modals.
 */
class StudentApproveLeaveController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentApplyLeaveService $leaves,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave', 'can_view'), 403);

        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);
        $results = [];
        $searched = false;

        if ($request->isMethod('post') || ($request->filled('search') && $classId > 0 && $sectionId > 0)) {
            $request->validate([
                'class_id' => ['required', 'integer'],
                'section_id' => ['required', 'integer'],
            ]);
            $classId = (int) $request->input('class_id');
            $sectionId = (int) $request->input('section_id');
            abort_unless($this->leaves->canApproveLeave($classId, $sectionId), 403);
            $results = $this->leaves->get(null, $classId, $sectionId);
            $searched = true;
        }

        return view('shared::layouts.admin', [
            'title' => 'Approve Leave',
            'contentView' => 'leave::admin.student_approve.index',
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'class_id' => $classId,
            'section_id' => $sectionId,
            'results' => $results,
            'searched' => $searched,
            'schSetting' => SchSetting::query()->first(),
            'canAdd' => $this->permissions->hasPrivilege('approve_leave', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('approve_leave', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('approve_leave', 'can_delete'),
            'statusLabel' => fn (int $s) => $this->leaves->statusLabel($s),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave', 'can_add'), 403);

        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);
        if ($classId > 0 && $sectionId > 0) {
            abort_unless($this->leaves->canApproveLeave($classId, $sectionId), 403);
        }

        return view('shared::layouts.admin', [
            'title' => 'Add Student Leave',
            'contentView' => 'leave::admin.student_approve.form',
            'editing' => null,
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'class_id' => $classId,
            'section_id' => $sectionId,
            'students' => ($classId > 0 && $sectionId > 0)
                ? $this->leaves->studentsByClassSection($classId, $sectionId)
                : [],
            'schSetting' => SchSetting::query()->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave', 'can_add'), 403);

        $validated = $this->validated($request);
        abort_unless(
            $this->leaves->canApproveLeave((int) $validated['class'], (int) $validated['section']),
            403
        );

        try {
            $this->leaves->save($validated, $request->file('userfile'));
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['class' => $e->getMessage()]);
        }

        return redirect()
            ->to($this->listUrl((int) $validated['class'], (int) $validated['section']))
            ->with('success', 'Student leave saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave', 'can_edit'), 403);

        $row = $this->leaves->get($id);
        abort_if($row === null, 404);

        $classId = (int) $row['class_id'];
        $sectionId = (int) $row['section_id'];
        abort_unless($this->leaves->canApproveLeave($classId, $sectionId), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Student Leave',
            'contentView' => 'leave::admin.student_approve.form',
            'editing' => $row,
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'class_id' => $classId,
            'section_id' => $sectionId,
            'students' => $this->leaves->studentsByClassSection($classId, $sectionId),
            'schSetting' => SchSetting::query()->first(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave', 'can_edit'), 403);

        $existing = $this->leaves->get($id);
        abort_if($existing === null, 404);

        $validated = $this->validated($request);
        abort_unless(
            $this->leaves->canApproveLeave((int) $validated['class'], (int) $validated['section']),
            403
        );

        try {
            $this->leaves->save($validated, $request->file('userfile'), $id);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['class' => $e->getMessage()]);
        }

        return redirect()
            ->to($this->listUrl((int) $validated['class'], (int) $validated['section']))
            ->with('success', 'Student leave updated successfully.');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave', 'can_edit'), 403);

        $validated = $request->validate([
            'status' => ['required', 'integer', 'in:0,1,2'],
            'class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
        ]);

        $row = $this->leaves->get($id);
        abort_if($row === null, 404);
        abort_unless(
            $this->leaves->canApproveLeave((int) $row['class_id'], (int) $row['section_id']),
            403
        );

        try {
            $this->leaves->updateStatus($id, (int) $validated['status']);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->to($this->listUrl((int) ($validated['class_id'] ?? $row['class_id']), (int) ($validated['section_id'] ?? $row['section_id'])))
            ->with('success', 'Leave status updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave', 'can_delete'), 403);

        $row = $this->leaves->get($id);
        abort_if($row === null, 404);
        abort_unless(
            $this->leaves->canApproveLeave((int) $row['class_id'], (int) $row['section_id']),
            403
        );

        try {
            $this->leaves->delete($id);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->to($this->listUrl((int) $request->input('class_id', $row['class_id']), (int) $request->input('section_id', $row['section_id'])))
            ->with('success', 'Leave deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse|Response
    {
        abort_unless($this->permissions->hasPrivilege('approve_leave', 'can_view'), 403);

        $row = $this->leaves->get($id);
        abort_if($row === null || empty($row['docs']), 404);
        abort_unless(
            $this->leaves->canApproveLeave((int) $row['class_id'], (int) $row['section_id']),
            403
        );

        $path = $this->leaves->documentPath((string) $row['docs']);
        abort_unless(is_file($path), 404);

        return response()->download($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'class' => ['required', 'integer'],
            'section' => ['required', 'integer'],
            'student' => ['required', 'integer'],
            'apply_date' => ['required', 'date'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'leave_status' => ['required', 'integer', 'in:0,1,2'],
            'message' => ['nullable', 'string'],
            'userfile' => ['nullable', 'file', 'max:5120'],
        ]);
    }

    protected function listUrl(int $classId, int $sectionId): string
    {
        if ($classId > 0 && $sectionId > 0) {
            return url('admin/approve_leave').'?class_id='.$classId.'&section_id='.$sectionId.'&search=search_filter';
        }

        return route('leave.student_approve.index');
    }
}
