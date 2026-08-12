<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Timetable\Services\ClassTimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * CI admin/Timetable — class timetable create/save + class report.
 * Deferred: mytimetable, print endpoints, duplicate check, quick generator.
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
}
