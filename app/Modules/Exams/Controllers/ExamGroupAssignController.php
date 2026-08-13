<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Exams\Services\ExamAssignService;
use App\Modules\Exams\Services\ExamGroupService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/examgroup/assign/{id} + addstudent — assign students to an exam group.
 */
class ExamGroupAssignController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamGroupService $examGroups,
        protected ExamAssignService $assign,
        protected CurrentSessionResolver $currentSession
    ) {
    }

    public function assign(Request $request, int $groupId): View
    {
        abort_unless($this->permissions->hasPrivilege('exam_assign_view_student', 'can_view'), 403);

        $group = $this->examGroups->findGroup($groupId);
        $resultList = null;
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'session_id' => $request->input('session_id', $this->currentSession->id()),
        ];

        $shouldSearch = $request->isMethod('post')
            || ($request->filled('class_id') && $request->filled('section_id') && $request->filled('session_id'));

        if ($shouldSearch) {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'session_id' => ['required', 'integer', 'exists:sessions,id'],
            ]);
            $filters = $data;
            $resultList = $this->assign->searchGroupStudents(
                $groupId,
                (int) $data['class_id'],
                (int) $data['section_id'],
                (int) $data['session_id']
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Assign Exam Group',
            'contentView' => 'exams::admin.exam_assign.group',
            'group' => $group,
            'exams' => $this->assign->examsForGroup($group),
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'sessions' => AcademicSession::query()->orderBy('id')->get(),
            'resultList' => $resultList,
            'filters' => $filters,
            'canSave' => $this->permissions->hasPrivilege('exam_assign_view_student', 'can_edit'),
        ]);
    }

    public function save(Request $request, int $groupId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('exam_assign_view_student', 'can_edit'), 403);

        $this->examGroups->findGroup($groupId);

        $data = $request->validate([
            'students_id' => ['nullable', 'array'],
            'students_id.*' => ['integer'],
            'all_students' => ['required', 'array', 'min:1'],
            'all_students.*' => ['integer'],
            'student_session' => ['nullable', 'array'],
            'student_session.*' => ['integer'],
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'session_id' => ['required', 'integer'],
        ]);

        $sessionMap = [];
        foreach ($data['student_session'] ?? [] as $studentId => $studentSessionId) {
            $sessionMap[(int) $studentId] = (int) $studentSessionId;
        }

        $this->assign->syncGroupStudents(
            $groupId,
            $data['students_id'] ?? [],
            $data['all_students'],
            $sessionMap
        );

        return redirect()
            ->route('exams.exam_groups.assign', [
                'groupId' => $groupId,
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'session_id' => $data['session_id'],
            ])
            ->with('success', 'Students assigned to exam group successfully.');
    }
}
