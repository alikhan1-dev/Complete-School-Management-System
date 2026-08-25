<?php

namespace App\Modules\LessonPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LessonPlan\Services\LessonPlanService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/lessonplan/lesson — lesson create/edit/delete (form POST instead of AJAX).
 */
class LessonController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LessonPlanService $lessons,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('lesson', 'can_view'), 403);

        $groups = $this->lessons->listLessonGroups();
        foreach ($groups as $i => $group) {
            $groups[$i]['lesson_names'] = $this->lessons->lessonsForSubject(
                (int) $group['subject_group_subject_id'],
                (int) $group['subject_group_class_sections_id']
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Lesson',
            'contentView' => 'lessonplan::admin.lesson',
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'groups' => $groups,
            'editing' => null,
            'editLessons' => [],
            'canAdd' => $this->permissions->hasPrivilege('lesson', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('lesson', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('lesson', 'can_delete'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('lesson', 'can_add'), 403);

        $validated = $request->validate([
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'subject_group_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'lessons' => ['required', 'array', 'min:1'],
            'lessons.*' => ['nullable', 'string', 'max:255'],
        ]);

        $this->lessons->createLessons(
            (int) $validated['class_id'],
            (int) $validated['section_id'],
            (int) $validated['subject_group_id'],
            (int) $validated['subject_id'],
            $validated['lessons']
        );

        return redirect()->route('lessonplan.lessons.index')->with('success', 'Lesson saved successfully.');
    }

    public function edit(int $subjectGroupClassSectionsId, int $subjectGroupSubjectId): View
    {
        abort_unless($this->permissions->hasPrivilege('lesson', 'can_edit'), 403);

        $editing = $this->lessons->findLessonGroup($subjectGroupClassSectionsId, $subjectGroupSubjectId);
        abort_if($editing === null, 404);
        abort_unless(
            $this->lessons->canEditAsClassTeacher(
                (int) $editing['classid'],
                (int) $editing['sectionid'],
                (int) $editing['subjectgroupsid'],
                (int) $editing['subject_group_subject_id'],
            ),
            403
        );

        $groups = $this->lessons->listLessonGroups();
        foreach ($groups as $i => $group) {
            $groups[$i]['lesson_names'] = $this->lessons->lessonsForSubject(
                (int) $group['subject_group_subject_id'],
                (int) $group['subject_group_class_sections_id']
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Edit Lesson',
            'contentView' => 'lessonplan::admin.lesson',
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'groups' => $groups,
            'editing' => $editing,
            'editLessons' => $this->lessons->lessonsForSubject($subjectGroupSubjectId, $subjectGroupClassSectionsId),
            'canAdd' => $this->permissions->hasPrivilege('lesson', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('lesson', 'can_delete'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('lesson', 'can_edit'), 403);

        $validated = $request->validate([
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'subject_group_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'lesson_delete' => ['nullable', 'array'],
            'lesson_delete.*' => ['integer'],
            'lessons' => ['nullable', 'array'],
            'lessons.*' => ['nullable', 'string', 'max:255'],
        ]);

        abort_unless(
            $this->lessons->canEditAsClassTeacher(
                (int) $validated['class_id'],
                (int) $validated['section_id'],
                (int) $validated['subject_group_id'],
                (int) $validated['subject_id'],
            ),
            403
        );

        $updates = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with((string) $key, 'lessons_') && is_numeric(substr((string) $key, 8))) {
                $updates[(int) substr((string) $key, 8)] = (string) $value;
            }
        }

        $this->lessons->updateLessons(
            (int) $validated['class_id'],
            (int) $validated['section_id'],
            (int) $validated['subject_group_id'],
            (int) $validated['subject_id'],
            $updates,
            array_map('intval', $validated['lesson_delete'] ?? []),
            $validated['lessons'] ?? []
        );

        return redirect()->route('lessonplan.lessons.index')->with('success', 'Lesson updated successfully.');
    }

    public function destroyBulk(int $subjectGroupClassSectionsId, int $subjectGroupSubjectId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('lesson', 'can_delete'), 403);

        $this->lessons->deleteLessonBulk($subjectGroupClassSectionsId, $subjectGroupSubjectId);

        return redirect()->route('lessonplan.lessons.index')->with('success', 'Lesson deleted successfully.');
    }

    /**
     * CI admin/lessonplan/getlessonBysubjectid/{sub_id} — cascade for topic form.
     */
    public function lessonsBySubject(Request $request, int $subjectGroupSubjectId): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('topic', 'can_view')
            || $this->permissions->hasPrivilege('lesson', 'can_view')
            || $this->permissions->hasPrivilege('manage_syllabus_status', 'can_view'),
            403
        );

        $validated = $request->validate([
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'subject_group_id' => ['required', 'integer'],
        ]);

        $sgcs = $this->lessons->subjectGroupClassSectionsId(
            (int) $validated['class_id'],
            (int) $validated['section_id'],
            (int) $validated['subject_group_id']
        );

        if ($sgcs === null) {
            return response()->json([]);
        }

        return response()->json(
            $this->lessons->lessonsForSubject($subjectGroupSubjectId, (int) $sgcs['id'])
        );
    }

    /**
     * CI admin/lessonplan/getlessonBysubjectidedit/{sub_id} — posts subject_group_class_sections_id.
     */
    public function lessonsBySubjectEdit(Request $request, int $subjectGroupSubjectId): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('topic', 'can_view')
            || $this->permissions->hasPrivilege('lesson', 'can_view')
            || $this->permissions->hasPrivilege('manage_lesson_plan', 'can_view')
            || $this->permissions->hasPrivilege('manage_syllabus_status', 'can_view'),
            403
        );

        $validated = $request->validate([
            'subject_group_class_sections_id' => ['required', 'integer'],
        ]);

        return response()->json(
            $this->lessons->lessonsForSubject(
                $subjectGroupSubjectId,
                (int) $validated['subject_group_class_sections_id']
            )
        );
    }
}
