<?php

namespace App\Modules\LessonPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LessonPlan\Models\LessonPlanForum;
use App\Modules\LessonPlan\Services\LessonPlanForumService;
use App\Modules\LessonPlan\Services\LessonPlanService;
use App\Modules\LessonPlan\Services\SyllabusManageService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/syllabus — weekly lesson plan manage (form POST instead of AJAX modals).
 * Privilege: manage_lesson_plan. Forum: lesson_plan_comments.
 * Deferred: student portal posting, class-teacher scope, SaaS quota.
 */
class SyllabusManageController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SyllabusManageService $syllabus,
        protected LessonPlanService $lessons,
        protected LessonPlanForumService $forum,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_view'), 403);

        $staff = Auth::guard('staff')->user();
        abort_unless($staff !== null, 403);

        $role = $staff->primaryRole();
        $roleId = (int) ($role?->id ?? 0);
        $isTeacher = $roleId === 2;

        $staffId = (int) ($request->input('staff_id') ?: ($isTeacher ? $staff->id : 0));
        $weekStart = (string) ($request->input('week_start') ?: '');
        $meta = $this->syllabus->weekMeta($weekStart !== '' ? $weekStart : null);

        $grid = null;
        if ($staffId > 0) {
            $grid = $this->syllabus->weekTimetable($staffId, $meta['week_start']);
        }

        return view('shared::layouts.admin', [
            'title' => 'Manage Lesson Plan',
            'contentView' => 'lessonplan::admin.syllabus.index',
            'teachers' => $this->syllabus->teachers(),
            'staffId' => $staffId,
            'isTeacher' => $isTeacher,
            'roleId' => $roleId,
            'meta' => $meta,
            'grid' => $grid,
            'canAdd' => $this->permissions->hasPrivilege('manage_lesson_plan', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('manage_lesson_plan', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('manage_lesson_plan', 'can_delete'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_add'), 403);

        $validated = $request->validate([
            'subject_group_subject_id' => ['required', 'integer'],
            'subject_group_class_sections_id' => ['required', 'integer'],
            'time_from' => ['required', 'string'],
            'time_to' => ['required', 'string'],
            'date' => ['required', 'date'],
            'created_for' => ['required', 'integer'],
            'week_start' => ['nullable', 'date'],
        ]);

        return view('shared::layouts.admin', [
            'title' => 'Add Lesson Plan',
            'contentView' => 'lessonplan::admin.syllabus.form',
            'editing' => null,
            'defaults' => $validated,
            'lessons' => $this->lessons->lessonsForSubject(
                (int) $validated['subject_group_subject_id'],
                (int) $validated['subject_group_class_sections_id']
            ),
            'topics' => [],
            'uploadMeta' => $this->syllabus->uploadMeta(),
            'weekStart' => $validated['week_start'] ?? null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_add'), 403);

        return $this->persist($request, null);
    }

    public function show(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_view'), 403);

        $row = $this->syllabus->findDetailed($id);
        abort_if($row === null, 404);

        $staff = Auth::guard('staff')->user();
        $canViewComments = $this->permissions->hasPrivilege('lesson_plan_comments', 'can_view');

        return view('shared::layouts.admin', [
            'title' => 'Lesson Plan',
            'contentView' => 'lessonplan::admin.syllabus.show',
            'row' => $row,
            'canEdit' => $this->permissions->hasPrivilege('manage_lesson_plan', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('manage_lesson_plan', 'can_delete'),
            'canViewComments' => $canViewComments,
            'canAddComments' => $this->permissions->hasPrivilege('lesson_plan_comments', 'can_add'),
            'canDeleteComments' => $this->permissions->hasPrivilege('lesson_plan_comments', 'can_delete'),
            'loginStaffId' => (int) ($staff?->id ?? 0),
            'messages' => $canViewComments ? $this->forum->messagesForSyllabus($id, $staff) : [],
        ]);
    }

    /**
     * CI admin/syllabus/addmessage — form POST.
     */
    public function addMessage(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('lesson_plan_comments', 'can_add'), 403);

        $staff = Auth::guard('staff')->user();
        abort_unless($staff !== null, 403);

        $validated = $request->validate([
            'subject_syllabus_id' => ['required', 'integer'],
            'message' => ['required', 'string'],
        ]);

        $syllabus = $this->syllabus->findDetailed((int) $validated['subject_syllabus_id']);
        abort_if($syllabus === null, 404);

        $this->forum->addStaffMessage(
            (int) $validated['subject_syllabus_id'],
            (int) $staff->id,
            $validated['message']
        );

        return redirect()
            ->route('lessonplan.syllabus.show', (int) $validated['subject_syllabus_id'])
            ->with('success', 'Comment saved successfully.');
    }

    /**
     * CI admin/syllabus/deletemessage — own staff comments only.
     */
    public function deleteMessage(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('lesson_plan_comments', 'can_delete'), 403);

        $staff = Auth::guard('staff')->user();
        abort_unless($staff !== null, 403);

        $row = LessonPlanForum::query()->findOrFail($id);
        $syllabusId = (int) $row->subject_syllabus_id;
        $this->forum->deleteOwnStaffMessage($id, (int) $staff->id);

        return redirect()
            ->route('lessonplan.syllabus.show', $syllabusId)
            ->with('success', 'Comment deleted successfully.');
    }

    public function edit(int $id, Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_edit'), 403);

        $row = $this->syllabus->findDetailed($id);
        abort_if($row === null, 404);

        return view('shared::layouts.admin', [
            'title' => 'Edit Lesson Plan',
            'contentView' => 'lessonplan::admin.syllabus.form',
            'editing' => $row,
            'defaults' => [
                'subject_group_subject_id' => $row['subject_group_subject_id'],
                'subject_group_class_sections_id' => $row['subject_group_class_sections_id'],
                'time_from' => $row['time_from'],
                'time_to' => $row['time_to'],
                'date' => $row['date'],
                'created_for' => $row['created_for'],
            ],
            'lessons' => $this->lessons->lessonsForSubject(
                (int) $row['subject_group_subject_id'],
                (int) $row['subject_group_class_sections_id']
            ),
            'topics' => $this->lessons->topicsByLesson((int) $row['lesson_id']),
            'uploadMeta' => $this->syllabus->uploadMeta(),
            'weekStart' => $request->input('week_start'),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_edit'), 403);

        return $this->persist($request, $id);
    }

    public function destroy(int $id, Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_delete'), 403);

        $row = $this->syllabus->findDetailed($id);
        $this->syllabus->delete($id);

        $staffId = (int) ($row['created_for'] ?? 0);
        $weekStart = (string) $request->input('week_start', '');

        return redirect()
            ->route('lessonplan.syllabus.manage', array_filter([
                'staff_id' => $staffId ?: null,
                'week_start' => $weekStart ?: null,
            ]))
            ->with('success', 'Lesson plan deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_view'), 403);
        $row = $this->syllabus->findDetailed($id);
        abort_if($row === null || empty($row['attachment']), 404);

        return $this->syllabus->downloadAttachment((string) $row['attachment']);
    }

    public function downloadLectureVideo(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_view'), 403);
        $row = $this->syllabus->findDetailed($id);
        abort_if($row === null || empty($row['lacture_video']), 404);

        return $this->syllabus->downloadLectureVideo((string) $row['lacture_video']);
    }

    public function topicsByLesson(int $lessonId): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('manage_lesson_plan', 'can_view'), 403);

        return response()->json($this->lessons->topicsByLesson($lessonId));
    }

    private function persist(Request $request, ?int $id): RedirectResponse
    {
        $staff = Auth::guard('staff')->user();
        abort_unless($staff !== null, 403);

        $meta = $this->syllabus->uploadMeta();
        $rules = [
            'lesson_id' => ['required', 'integer'],
            'topic_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'time_from' => ['required', 'string', 'max:255'],
            'time_to' => ['required', 'string', 'max:255'],
            'created_for' => ['required', 'integer'],
            'presentation' => ['nullable', 'string'],
            'sub_topic' => ['nullable', 'string'],
            'teaching_method' => ['nullable', 'string'],
            'general_objectives' => ['nullable', 'string'],
            'previous_knowledge' => ['nullable', 'string'],
            'comprehensive_questions' => ['nullable', 'string'],
            'lacture_youtube_url' => ['nullable', 'string', 'max:255'],
            'week_start' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:'.$meta['max_kb']],
            'lacture_video' => ['nullable', 'file', 'max:'.$meta['max_kb']],
        ];
        $ext = implode(',', $meta['extensions'] ?? []);
        if ($ext !== '') {
            $rules['file'][] = 'mimes:'.$ext;
            $rules['lacture_video'][] = 'mimes:'.$ext;
        }

        $validated = $request->validate($rules);

        if ($id !== null) {
            $validated['subject_syllabusid'] = $id;
        }

        $saved = $this->syllabus->save(
            $validated,
            (int) $staff->id,
            $request->file('file'),
            $request->file('lacture_video')
        );

        return redirect()
            ->route('lessonplan.syllabus.manage', array_filter([
                'staff_id' => (int) $validated['created_for'],
                'week_start' => $validated['week_start'] ?? null,
            ]))
            ->with('success', 'Lesson plan saved successfully.')
            ->with('saved_syllabus_id', $saved->id);
    }
}
