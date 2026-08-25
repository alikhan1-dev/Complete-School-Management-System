<?php

namespace App\Modules\LessonPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LessonPlan\Services\LessonPlanService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/lessonplan/topic — topic create/edit/delete + status complete date.
 */
class TopicController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LessonPlanService $lessons,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('topic', 'can_view'), 403);

        $groups = $this->lessons->listTopicGroups();
        foreach ($groups as $i => $group) {
            $groups[$i]['topics'] = $this->lessons->topicsByLesson((int) $group['lesson_id']);
        }

        return view('shared::layouts.admin', [
            'title' => 'Topic',
            'contentView' => 'lessonplan::admin.topic',
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'groups' => $groups,
            'editing' => null,
            'editTopics' => [],
            'canAdd' => $this->permissions->hasPrivilege('topic', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('topic', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('topic', 'can_delete'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('topic', 'can_add'), 403);

        $validated = $request->validate([
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'subject_group_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'lesson_id' => ['required', 'integer'],
            'topic' => ['required', 'array', 'min:1'],
            'topic.*' => ['nullable', 'string', 'max:255'],
        ]);

        $this->lessons->createTopics((int) $validated['lesson_id'], $validated['topic']);

        return redirect()->route('lessonplan.topics.index')->with('success', 'Topic saved successfully.');
    }

    public function edit(int $lessonId): View
    {
        abort_unless($this->permissions->hasPrivilege('topic', 'can_edit'), 403);

        $groups = $this->lessons->listTopicGroups();
        $editing = null;
        foreach ($groups as $i => $group) {
            $groups[$i]['topics'] = $this->lessons->topicsByLesson((int) $group['lesson_id']);
            if ((int) $group['lesson_id'] === $lessonId) {
                $editing = $groups[$i];
            }
        }
        abort_if($editing === null, 404);

        $context = $this->lessons->lessonContext($lessonId);
        abort_if($context === null, 404);
        abort_unless(
            $this->lessons->canEditAsClassTeacher(
                $context['class_id'],
                $context['section_id'],
                $context['subject_group_id'],
                $context['subject_group_subject_id'],
            ),
            403
        );

        return view('shared::layouts.admin', [
            'title' => 'Edit Topic',
            'contentView' => 'lessonplan::admin.topic',
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'groups' => $groups,
            'editing' => $editing,
            'editTopics' => $this->lessons->topicsByLesson($lessonId),
            'canAdd' => $this->permissions->hasPrivilege('topic', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('topic', 'can_delete'),
        ]);
    }

    public function update(Request $request, int $lessonId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('topic', 'can_edit'), 403);

        $context = $this->lessons->lessonContext($lessonId);
        abort_if($context === null, 404);
        abort_unless(
            $this->lessons->canEditAsClassTeacher(
                $context['class_id'],
                $context['section_id'],
                $context['subject_group_id'],
                $context['subject_group_subject_id'],
            ),
            403
        );

        $validated = $request->validate([
            'topic_delete' => ['nullable', 'array'],
            'topic_delete.*' => ['integer'],
            'topic' => ['nullable', 'array'],
            'topic.*' => ['nullable', 'string', 'max:255'],
        ]);

        $updates = [];
        foreach ($request->all() as $key => $value) {
            // CI uses topic_{id}
            if (str_starts_with((string) $key, 'topic_') && is_numeric(substr((string) $key, 6))) {
                $updates[(int) substr((string) $key, 6)] = (string) $value;
            }
        }

        $this->lessons->updateTopics(
            $lessonId,
            $updates,
            array_map('intval', $validated['topic_delete'] ?? []),
            $validated['topic'] ?? []
        );

        return redirect()->route('lessonplan.topics.index')->with('success', 'Topic updated successfully.');
    }

    public function destroyBulk(int $lessonId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('topic', 'can_delete'), 403);

        $this->lessons->deleteTopicsByLesson($lessonId);

        return redirect()->route('lessonplan.topics.index')->with('success', 'Topic deleted successfully.');
    }

    public function complete(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('topic', 'can_edit')
            || $this->permissions->hasPrivilege('manage_syllabus_status', 'can_edit'), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'redirect' => ['nullable', 'string'],
        ]);

        $this->lessons->changeTopicStatus($id, 1, $validated['date']);

        return redirect()
            ->to($validated['redirect'] ?? route('lessonplan.status.index'))
            ->with('success', 'Topic marked complete.');
    }

    public function incomplete(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('topic', 'can_edit')
            || $this->permissions->hasPrivilege('manage_syllabus_status', 'can_edit'), 403);

        $this->lessons->changeTopicStatus($id, 0, null);

        return redirect()
            ->to($request->input('redirect', route('lessonplan.status.index')))
            ->with('success', 'Topic marked incomplete.');
    }
}
