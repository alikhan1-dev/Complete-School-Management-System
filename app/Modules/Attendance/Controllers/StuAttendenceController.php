<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Attendance\Services\StudentDayAttendanceService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * CI admin/Stuattendence — student day attendance mark + save.
 */
class StuAttendenceController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentDayAttendanceService $attendance
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_attendance', 'can_view'), 403);

        $resultList = null;
        $types = collect();
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'date' => $request->input('date', date('Y-m-d')),
        ];
        $isFirstTime = true;

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'date' => ['required', 'date'],
                'search' => ['nullable', 'string'],
            ]);

            $filters['class_id'] = $data['class_id'];
            $filters['section_id'] = $data['section_id'];
            $filters['date'] = $data['date'];
            $types = $this->attendance->activeTypes();

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
                        'attendence_type_id' => $typeId,
                        'date' => $data['date'],
                        'remark' => $request->input('remark'.$sessionId, ''),
                        'in_time' => $request->input('in_time_'.$sessionId),
                        'out_time' => $request->input('out_time_'.$sessionId),
                    ];
                }

                try {
                    $count = $this->attendance->addOrUpdate($rows);
                } catch (InvalidArgumentException $e) {
                    return back()->withInput()->withErrors(['search' => $e->getMessage()]);
                }

                // CI redirects clean to index after save.
                return redirect()
                    ->route('attendance.stuattendence.index')
                    ->with('success', "Attendance saved for {$count} student(s).");
            }

            try {
                $resultList = $this->attendance->searchClassSection(
                    (int) $data['class_id'],
                    (int) $data['section_id'],
                    $data['date']
                );
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['class_id' => $e->getMessage()]);
            }

            foreach ($resultList as $row) {
                if (! empty($row->attendence_type_id)) {
                    $isFirstTime = false;
                    break;
                }
            }
        }

        return view('shared::layouts.admin', [
            'title' => 'Student Attendance',
            'contentView' => 'attendance::admin.stuattendence.index',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'types' => $types,
            'resultList' => $resultList,
            'filters' => $filters,
            'isFirstTime' => $isFirstTime,
            'canAdd' => $this->permissions->hasPrivilege('student_attendance', 'can_add'),
        ]);
    }

    /**
     * CI admin/stuattendence/attendencereport — Attendance By Date (read-only prepared list).
     * Privilege: attendance_by_date can_view.
     * Class-teacher restricted class list deferred.
     */
    public function attendencereport(Request $request): View|RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('attendance_by_date', 'can_view')
            || $this->permissions->hasPrivilege('student_attendance', 'can_view'),
            403
        );

        $resultList = null;
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

            try {
                $resultList = $this->attendance->searchPreparedByDate(
                    (int) $data['class_id'],
                    (int) $data['section_id'],
                    $data['date']
                );
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['class_id' => $e->getMessage()]);
            }
        }

        return view('shared::layouts.admin', [
            'title' => 'Attendance By Date',
            'contentView' => 'attendance::admin.stuattendence.attendencereport',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'types' => $types,
            'resultList' => $resultList,
            'filters' => $filters,
        ]);
    }
}
