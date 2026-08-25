<?php

namespace App\Modules\LessonPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\LessonPlan\Services\LessonPlanService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/lessonplan/copylesson + saveCopyLesson (form POST instead of AJAX).
 */
class CopyLessonController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LessonPlanService $lessons,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('copy_old_lesson', 'can_view'), 403);

        $filters = [
            'old_session_id' => $request->input('old_session_id'),
            'old_class_id' => $request->input('old_class_id'),
            'old_section_id' => $request->input('old_section_id'),
            'old_subject_group_id' => $request->input('old_subject_group_id'),
            'old_subject_id' => $request->input('old_subject_id'),
        ];

        $tree = null;
        $searched = false;
        $noRecord = '0';

        if ($request->isMethod('post') || $request->filled('search')) {
            $validated = $request->validate([
                'old_session_id' => ['required', 'integer'],
                'old_class_id' => ['required', 'integer'],
                'old_section_id' => ['required', 'integer'],
                'old_subject_group_id' => ['required', 'integer'],
                'old_subject_id' => ['required', 'integer'],
            ]);

            $tree = $this->lessons->loadOldSyllabus(
                (int) $validated['old_session_id'],
                (int) $validated['old_class_id'],
                (int) $validated['old_section_id'],
                (int) $validated['old_subject_group_id'],
                (int) $validated['old_subject_id']
            );
            $noRecord = $tree['no_record'];
            $searched = true;
            $filters = $validated;
        }

        return view('shared::layouts.admin', [
            'title' => 'Copy Old Lesson',
            'contentView' => 'lessonplan::admin.copylesson',
            'sessions' => AcademicSession::query()->orderByDesc('id')->get(),
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'currentSessionId' => $this->lessons->currentSessionId(),
            'filters' => $filters,
            'tree' => $tree,
            'searched' => $searched,
            'noRecord' => $noRecord,
            'canSave' => $this->permissions->hasPrivilege('copy_old_lesson', 'can_add')
                || $this->permissions->hasPrivilege('copy_old_lesson', 'can_view'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('copy_old_lesson', 'can_add')
            || $this->permissions->hasPrivilege('copy_old_lesson', 'can_view'),
            403
        );

        $validated = $request->validate([
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'subject_group_id' => ['required', 'integer'],
            'subject_group_subject_id' => ['required', 'integer'],
            'topic_id' => ['required', 'array', 'min:1'],
        ]);

        /** @var array<int, list<int|string>> $topicMap */
        $topicMap = [];
        foreach ($validated['topic_id'] as $lessonId => $topicIds) {
            if (! is_array($topicIds)) {
                continue;
            }
            $topicMap[(int) $lessonId] = array_map('intval', $topicIds);
        }

        $this->lessons->copySelectedTopics(
            $topicMap,
            (int) $validated['class_id'],
            (int) $validated['section_id'],
            (int) $validated['subject_group_id'],
            (int) $validated['subject_group_subject_id']
        );

        return redirect()
            ->route('lessonplan.lessons.index')
            ->with('success', 'Lesson copied successfully.');
    }
}
