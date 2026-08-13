<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\OnlineExam\Services\OnlineExamAssignService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/onlineexam/assign/{id} + addstudent — assign students to an online exam.
 */
class OnlineExamAssignController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OnlineExamAssignService $assign
    ) {
    }

    public function assign(Request $request, int $examId): View
    {
        abort_unless($this->permissions->hasPrivilege('online_assign_view_student', 'can_view'), 403);

        $exam = $this->assign->exam($examId);
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
        ];

        $resultList = null;
        $shouldSearch = $request->isMethod('post')
            || $request->filled('class_id');

        if ($shouldSearch) {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            ]);
            $filters = $data;
            $sectionId = ! empty($data['section_id']) ? (int) $data['section_id'] : null;
            $resultList = $this->assign->searchStudents($examId, (int) $data['class_id'], $sectionId);
        }

        return view('shared::layouts.admin', [
            'title' => 'Assign / View Student',
            'contentView' => 'onlineexam::admin.assign.index',
            'exam' => $exam,
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'resultList' => $resultList,
            'filters' => $filters,
            'canSave' => $this->permissions->hasPrivilege('online_assign_view_student', 'can_edit'),
        ]);
    }

    public function save(Request $request, int $examId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_assign_view_student', 'can_edit'), 403);

        $this->assign->exam($examId);

        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'students_id' => ['nullable', 'array'],
            'students_id.*' => ['integer'],
        ]);

        $sectionId = ! empty($data['section_id']) ? (int) $data['section_id'] : null;

        $this->assign->syncStudents(
            $examId,
            (int) $data['class_id'],
            $sectionId,
            $data['students_id'] ?? []
        );

        $redirect = [
            'examId' => $examId,
            'class_id' => $data['class_id'],
        ];
        if ($sectionId !== null) {
            $redirect['section_id'] = $sectionId;
        }

        return redirect()
            ->route('onlineexam.assign.index', $redirect)
            ->with('success', 'Students assigned successfully.');
    }
}
