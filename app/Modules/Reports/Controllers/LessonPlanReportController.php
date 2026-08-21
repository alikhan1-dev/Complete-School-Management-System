<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\LessonPlanReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Report::lesson_plan hub + syllabus status + teachersyllabusstatus.
 */
class LessonPlanReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LessonPlanReportService $reports,
    ) {
    }

    public function lesson_plan(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('syllabus_status_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'subject_group_id' => $request->input('subject_group_id', ''),
        ];
        $subjectsData = [];
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
                'subject_group_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
                'subject_group_id.required' => 'The Subject Group field is required.',
            ]);
            $searched = true;
            $subjectsData = $this->reports->syllabusStatusReport(
                (int) $filters['class_id'],
                (int) $filters['section_id'],
                (int) $filters['subject_group_id']
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.syllabus_status_report'),
            'contentView' => 'reports::admin.lesson_plan.syllabus_status',
            'filters' => $filters,
            'subjects_data' => $subjectsData,
            'searched' => $searched,
            'statusLabels' => $this->reports->topicStatusLabels(),
            'classlist' => $this->reports->classes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function teachersyllabusstatus(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('subject_lesson_plan_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'subject_group_id' => $request->input('subject_group_id', ''),
            'subject_id' => $request->input('subject_id', ''),
        ];
        $subjectsData = [];
        $subjectName = '';
        $subjectComplete = 0;
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
                'subject_group_id' => ['required'],
                'subject_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
                'subject_group_id.required' => 'The Subject Group field is required.',
                'subject_id.required' => 'The Subject field is required.',
            ]);
            $searched = true;
            $payload = $this->reports->teacherSyllabusStatusReport(
                (int) $filters['class_id'],
                (int) $filters['section_id'],
                (int) $filters['subject_group_id'],
                (int) $filters['subject_id']
            );
            $subjectsData = $payload['subjects_data'];
            $subjectName = $payload['subject_name'];
            $subjectComplete = $payload['subject_complete'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.subject_lesson_plan_report'),
            'contentView' => 'reports::admin.lesson_plan.teacher_syllabus_status',
            'filters' => $filters,
            'subjects_data' => $subjectsData,
            'subject_name' => $subjectName,
            'subject_complete' => $subjectComplete,
            'searched' => $searched,
            'classlist' => $this->reports->classes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    /**
     * @return array<string, bool>
     */
    protected function navFlags(): array
    {
        return [
            'canSyllabusStatusReport' => $this->permissions->hasPrivilege('syllabus_status_report', 'can_view'),
            'canSubjectLessonPlanReport' => $this->permissions->hasPrivilege('subject_lesson_plan_report', 'can_view'),
        ];
    }
}
