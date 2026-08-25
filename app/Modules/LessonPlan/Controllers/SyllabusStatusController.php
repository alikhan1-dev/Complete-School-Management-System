<?php

namespace App\Modules\LessonPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LessonPlan\Services\LessonPlanService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/lessonplan index — manage syllabus status (lesson/topic completion tree).
 */
class SyllabusStatusController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LessonPlanService $lessons,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('manage_syllabus_status', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'subject_group_id' => $request->input('subject_group_id'),
            'subject_id' => $request->input('subject_id'),
        ];

        $tree = null;
        $searched = false;

        if ($request->isMethod('post') || $request->filled('search')) {
            $request->validate([
                'class_id' => ['required', 'integer'],
                'section_id' => ['required', 'integer'],
                'subject_group_id' => ['required', 'integer'],
                'subject_id' => ['required', 'integer'],
            ]);
            abort_unless(
                $this->classTeacherScope->allowsClassSection(
                    (int) $request->input('class_id'),
                    (int) $request->input('section_id'),
                    'union'
                ),
                403
            );
            $tree = $this->lessons->syllabusStatus(
                (int) $request->input('class_id'),
                (int) $request->input('section_id'),
                (int) $request->input('subject_group_id'),
                (int) $request->input('subject_id')
            );
            $searched = true;
            $filters = [
                'class_id' => $request->input('class_id'),
                'section_id' => $request->input('section_id'),
                'subject_group_id' => $request->input('subject_group_id'),
                'subject_id' => $request->input('subject_id'),
            ];
        }

        return view('shared::layouts.admin', [
            'title' => 'Manage Syllabus Status',
            'contentView' => 'lessonplan::admin.status',
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'filters' => $filters,
            'tree' => $tree,
            'searched' => $searched,
            'canEdit' => $this->permissions->hasPrivilege('manage_syllabus_status', 'can_edit')
                || $this->permissions->hasPrivilege('topic', 'can_edit'),
        ]);
    }
}
