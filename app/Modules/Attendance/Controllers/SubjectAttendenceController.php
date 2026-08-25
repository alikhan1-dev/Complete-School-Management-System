<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\SubjectPeriodAttendanceService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * CI admin/Subjectattendence — period / subject attendance mark + save.
 * SMS deferred (Communication).
 */
class SubjectAttendenceController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SubjectPeriodAttendanceService $attendance,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_attendance', 'can_view'), 403);

        $resultList = null;
        $types = collect();
        $periodOptions = collect();
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'date' => $request->input('date', date('Y-m-d')),
            'subject_timetable_id' => $request->input('subject_timetable_id'),
        ];
        $isFirstTime = true;

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'date' => ['required', 'date'],
                'subject_timetable_id' => ['required', 'integer', 'exists:subject_timetable,id'],
                'search' => ['nullable', 'string'],
            ]);

            $filters['class_id'] = $data['class_id'];
            $filters['section_id'] = $data['section_id'];
            $filters['date'] = $data['date'];
            $filters['subject_timetable_id'] = $data['subject_timetable_id'];
            $types = $this->attendance->activeTypes();

            try {
                $periodOptions = $this->attendance->periodsForDate(
                    (int) $data['class_id'],
                    (int) $data['section_id'],
                    $data['date']
                );
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['date' => $e->getMessage()]);
            }

            if (($data['search'] ?? '') === 'saveattendence') {
                abort_unless($this->permissions->hasPrivilege('student_attendance', 'can_add'), 403);

                $sessions = $request->input('student_session', []);
                if (! is_array($sessions) || $sessions === []) {
                    return back()->withInput()->withErrors(['student_session' => 'No students selected for attendance.']);
                }

                $rows = [];
                foreach ($sessions as $sessionId) {
                    $sessionId = (int) $sessionId;
                    $typeId = (int) $request->input('attendencetype'.$sessionId);
                    if ($typeId <= 0) {
                        return back()->withInput()->withErrors([
                            'attendencetype'.$sessionId => 'Attendance type is required for each student.',
                        ]);
                    }

                    $rows[] = [
                        'student_session_id' => $sessionId,
                        'subject_timetable_id' => (int) $data['subject_timetable_id'],
                        'attendence_type_id' => $typeId,
                        'date' => $data['date'],
                        'remark' => $request->input('remark'.$sessionId, ''),
                    ];
                }

                try {
                    $count = $this->attendance->addOrUpdate(
                        $rows,
                        (int) $data['class_id'],
                        (int) $data['section_id'],
                        $data['date'],
                    );
                } catch (InvalidArgumentException $e) {
                    return back()->withInput()->withErrors(['search' => $e->getMessage()]);
                }

                return redirect()
                    ->route('attendance.subjectattendence.index')
                    ->with('success', "Period attendance saved for {$count} student(s).");
            }

            try {
                $resultList = $this->attendance->searchClassSection(
                    (int) $data['class_id'],
                    (int) $data['section_id'],
                    (int) $data['subject_timetable_id'],
                    $data['date']
                );
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['subject_timetable_id' => $e->getMessage()]);
            }

            foreach ($resultList as $row) {
                if (! empty($row->attendence_type_id)) {
                    $isFirstTime = false;
                    break;
                }
            }
        }

        return view('shared::layouts.admin', [
            'title' => 'Period Attendance',
            'contentView' => 'attendance::admin.subjectattendence.index',
            // CI Class_model::get — union timetable ∪ class_teacher when restricted
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'types' => $types,
            'periodOptions' => $periodOptions,
            'resultList' => $resultList,
            'filters' => $filters,
            'isFirstTime' => $isFirstTime,
            'canAdd' => $this->permissions->hasPrivilege('student_attendance', 'can_add'),
        ]);
    }

    /**
     * CI admin/subjectgroup/getSubjectByClassandSectionDate.
     * Returns timetable periods for class/section on the weekday of date.
     */
    public function getSubjectByClassandSectionDate(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_attendance', 'can_view'), 403);

        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'date' => ['required', 'date'],
        ]);

        try {
            $periods = $this->attendance->periodsForDate(
                (int) $data['class_id'],
                (int) $data['section_id'],
                $data['date']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($periods->values());
    }

    /**
     * CI admin/subjectattendence/reportbydate — period attendance matrix by date.
     * Privilege: period_attendance_by_date can_view (fallback student_attendance can_view).
     */
    public function reportbydate(Request $request): View|RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('period_attendance_by_date', 'can_view')
            || $this->permissions->hasPrivilege('student_attendance', 'can_view'),
            403
        );

        $report = null;
        $searched = false;
        $types = collect();
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'date' => $request->input('date', date('Y-m-d')),
        ];

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'date' => ['required', 'date'],
            ]);

            $filters['class_id'] = $data['class_id'];
            $filters['section_id'] = $data['section_id'];
            $filters['date'] = $data['date'];
            $types = $this->attendance->activeTypes();
            $searched = true;

            try {
                $report = $this->attendance->searchByStudentsAttendanceByDate(
                    (int) $data['class_id'],
                    (int) $data['section_id'],
                    $data['date']
                );
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['class_id' => $e->getMessage()]);
            }
        }

        return view('shared::layouts.admin', [
            'title' => __('system.period_attendance_by_date'),
            'contentView' => 'attendance::admin.subjectattendence.reportbydate',
            'classes' => $this->classTeacherScope->classesForDropdown(),
            'types' => $types,
            'report' => $report,
            'searched' => $searched,
            'filters' => $filters,
        ]);
    }
}
