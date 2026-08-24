<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Timetable\Services\ClassTimetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * CI admin/Timetable — class timetable create/save + class report + teacher mytimetable + print.
 */
class TimetableController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ClassTimetableService $timetable
    ) {
    }

    /**
     * CI admin/timetable/classreport — weekly view by class/section.
     */
    public function classreport(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('class_timetable', 'can_view'), 403);

        $week = null;
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
        ];

        if ($request->isMethod('post') || ($request->filled('class_id') && $request->filled('section_id') && $request->isMethod('get') && $request->boolean('search'))) {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
            ]);
            $filters = $data;

            try {
                $week = $this->timetable->weekForClassSection((int) $data['class_id'], (int) $data['section_id']);
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['class_id' => $e->getMessage()]);
            }
        }

        return view('shared::layouts.admin', [
            'title' => 'Class Timetable',
            'contentView' => 'timetable::admin.classreport',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'filters' => $filters,
            'week' => $week,
            'canEdit' => $this->permissions->hasPrivilege('class_timetable', 'can_edit')
                || $this->permissions->hasPrivilege('class_timetable', 'can_add'),
        ]);
    }

    /**
     * CI admin/timetable/create — build periods for class/section/subject group.
     */
    public function create(Request $request): View|RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('class_timetable', 'can_view')
            || $this->permissions->hasPrivilege('class_timetable', 'can_add')
            || $this->permissions->hasPrivilege('class_timetable', 'can_edit'),
            403
        );

        $week = null;
        $subjects = collect();
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'subject_group_id' => $request->input('subject_group_id'),
        ];

        if ($request->isMethod('post') && ($request->input('search') !== 'saveday')) {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'subject_group_id' => ['required', 'integer', 'exists:subject_groups,id'],
            ]);
            $filters = $data;
            $subjects = $this->timetable->groupSubjects((int) $data['subject_group_id']);
            $week = $this->timetable->weekForEditor(
                (int) $data['subject_group_id'],
                (int) $data['class_id'],
                (int) $data['section_id']
            );
        } elseif ($request->filled('class_id') && $request->filled('section_id') && $request->filled('subject_group_id')) {
            // After save redirect with query params
            $filters = [
                'class_id' => (int) $request->input('class_id'),
                'section_id' => (int) $request->input('section_id'),
                'subject_group_id' => (int) $request->input('subject_group_id'),
            ];
            $subjects = $this->timetable->groupSubjects((int) $filters['subject_group_id']);
            $week = $this->timetable->weekForEditor(
                (int) $filters['subject_group_id'],
                (int) $filters['class_id'],
                (int) $filters['section_id']
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Create Class Timetable',
            'contentView' => 'timetable::admin.create',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'teachers' => $this->timetable->teachers(),
            'days' => $this->timetable->dayNames(),
            'subjects' => $subjects,
            'filters' => $filters,
            'week' => $week,
            'canSave' => $this->permissions->hasPrivilege('class_timetable', 'can_add')
                || $this->permissions->hasPrivilege('class_timetable', 'can_edit'),
            'service' => $this->timetable,
        ]);
    }

    /**
     * CI admin/timetable/savegroup — save one day's periods (form POST variant).
     */
    public function saveDay(Request $request): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('class_timetable', 'can_add')
            || $this->permissions->hasPrivilege('class_timetable', 'can_edit'),
            403
        );

        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'subject_group_id' => ['required', 'integer', 'exists:subject_groups,id'],
            'day' => ['required', 'string'],
            'periods' => ['nullable', 'array'],
            'periods.*.id' => ['nullable', 'integer'],
            'periods.*.subject_group_subject_id' => ['nullable', 'integer'],
            'periods.*.staff_id' => ['nullable', 'integer'],
            'periods.*.time_from' => ['nullable', 'string'],
            'periods.*.time_to' => ['nullable', 'string'],
            'periods.*.room_no' => ['nullable', 'string', 'max:100'],
        ]);

        $rows = [];
        foreach ($data['periods'] ?? [] as $period) {
            // Skip completely blank rows (UI add-row placeholders).
            $blank = empty($period['subject_group_subject_id'])
                && empty($period['staff_id'])
                && empty($period['time_from'])
                && empty($period['time_to'])
                && empty($period['room_no']);
            if ($blank) {
                continue;
            }
            $rows[] = [
                'id' => (int) ($period['id'] ?? 0),
                'subject_group_subject_id' => (int) ($period['subject_group_subject_id'] ?? 0),
                'staff_id' => (int) ($period['staff_id'] ?? 0),
                'time_from' => (string) ($period['time_from'] ?? ''),
                'time_to' => (string) ($period['time_to'] ?? ''),
                'room_no' => (string) ($period['room_no'] ?? ''),
            ];
        }

        try {
            $count = $this->timetable->saveDay(
                (int) $data['class_id'],
                (int) $data['section_id'],
                (int) $data['subject_group_id'],
                $data['day'],
                $rows
            );
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('timetable.create', [
                    'class_id' => $data['class_id'],
                    'section_id' => $data['section_id'],
                    'subject_group_id' => $data['subject_group_id'],
                ])
                ->withInput()
                ->withErrors(['day' => $e->getMessage()]);
        }

        return redirect()
            ->route('timetable.create', [
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'subject_group_id' => $data['subject_group_id'],
            ])
            ->with('success', "Saved {$count} period(s) for {$data['day']}.");
    }

    /**
     * CI admin/timetable/mytimetable — teacher weekly view; admin sees teacher picker.
     * Privilege: teachers_time_table can_view.
     */
    public function mytimetable(): View
    {
        abort_unless($this->permissions->hasPrivilege('teachers_time_table', 'can_view'), 403);

        $staff = Auth::guard('staff')->user();
        abort_unless($staff !== null, 403);

        $roleId = (int) ($staff->primaryRole()?->id ?? 0);
        $isTeacher = $roleId === ClassTimetableService::TEACHER_ROLE_ID;

        if ($isTeacher) {
            $week = $this->timetable->weekForStaff((int) $staff->id);

            return view('shared::layouts.admin', [
                'title' => __('system.teacher_time_table'),
                'contentView' => 'timetable::admin.mytimetable',
                'isAdminPicker' => false,
                'week' => $week,
                'staffId' => (int) $staff->id,
                'teachers' => collect(),
            ]);
        }

        return view('shared::layouts.admin', [
            'title' => __('system.teacher_time_table'),
            'contentView' => 'timetable::admin.mytimetable',
            'isAdminPicker' => true,
            'week' => null,
            'staffId' => 0,
            'teachers' => $this->timetable->teachers(),
        ]);
    }

    /**
     * CI admin/timetable/getteachertimetable — AJAX HTML partial for admin teacher picker.
     */
    public function getTeacherTimetable(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('teachers_time_table', 'can_view'), 403);

        $validator = Validator::make($request->all(), [
            'teacher' => ['required', 'integer', 'exists:staff,id'],
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors[$field] = $messages[0] ?? '';
            }

            return response()->json([
                'status' => '0',
                'error' => $errors,
            ]);
        }

        $staffId = (int) $request->input('teacher');
        $week = $this->timetable->weekForStaff($staffId);

        return response()->json([
            'status' => '1',
            'error' => '',
            'message' => view('timetable::admin.partials.teachertimetable_grid', [
                'week' => $week,
                'staffId' => $staffId,
            ])->render(),
        ]);
    }

    /**
     * CI admin/timetable/printclasstimetable — JSON {status, page} print HTML.
     */
    public function printClassTimetable(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('class_timetable', 'can_view'), 403);

        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ]);

        $classSection = $this->timetable->classSectionLabel(
            (int) $data['class_id'],
            (int) $data['section_id']
        );
        if ($classSection === null) {
            return response()->json([
                'status' => '0',
                'error' => 'Class or section not found.',
                'page' => '',
            ]);
        }

        try {
            $week = $this->timetable->weekForClassSection((int) $data['class_id'], (int) $data['section_id']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => '0',
                'error' => $e->getMessage(),
                'page' => '',
            ]);
        }

        return response()->json([
            'status' => '1',
            'error' => '',
            'page' => view('timetable::admin.print.class', [
                'classSection' => $classSection,
                'timetable' => $week,
            ])->render(),
        ]);
    }

    /**
     * CI admin/timetable/printteachertimetable — JSON {status, page} print HTML.
     */
    public function printTeacherTimetable(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('teachers_time_table', 'can_view'), 403);

        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
        ]);

        $staff = Staff::query()->find((int) $data['staff_id']);
        if (! $staff) {
            return response()->json([
                'status' => '0',
                'error' => 'Staff not found.',
                'page' => '',
            ]);
        }

        $week = $this->timetable->weekForStaff((int) $staff->id);

        return response()->json([
            'status' => '1',
            'error' => '',
            'page' => view('timetable::admin.print.teacher', [
                'staff' => $staff,
                'timetable' => $week,
            ])->render(),
        ]);
    }

    /**
     * CI admin/timetable/check_class_dublicate_recored — teacher slot conflict AJAX.
     */
    public function checkClassDuplicateRecord(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('class_timetable', 'can_view')
            || $this->permissions->hasPrivilege('class_timetable', 'can_add')
            || $this->permissions->hasPrivilege('class_timetable', 'can_edit'),
            403
        );

        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'day' => ['required', 'string'],
            'time_from' => ['required', 'string'],
            'time_to' => ['required', 'string'],
        ]);

        try {
            $result = $this->timetable->duplicateRecord(
                (int) $data['staff_id'],
                $data['day'],
                $data['time_from'],
                $data['time_to']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 0,
                'result' => [],
                'error' => $e->getMessage(),
            ]);
        }

        if ($result->isNotEmpty()) {
            $staffName = (string) ($result->first()->staff_name ?? '');

            return response()->json([
                'status' => 1,
                'result' => $result->values(),
                'error' => trim($staffName.' '.__('system.is_already_allotted_to_other_class_section_or_period_for_the_same_time')),
            ]);
        }

        return response()->json([
            'status' => 0,
            'result' => [],
        ]);
    }
}
