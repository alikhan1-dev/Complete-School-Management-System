<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Transport\Services\StudentTransportFeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/pickuppoint/student_fees + student_transport_months + add_student_fees.
 */
class StudentTransportFeeController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentTransportFeeService $fees,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_transport_fees', 'can_view'), 403);

        $students = collect();
        $searched = false;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'class_id' => ['required', 'integer'],
                'section_id' => ['nullable', 'integer'],
            ]);
            $searched = true;
            $students = $this->fees->searchByClassSection(
                (int) $validated['class_id'],
                isset($validated['section_id']) && $validated['section_id'] !== ''
                    ? (int) $validated['section_id']
                    : null
            );
        }

        return view('shared::layouts.admin', [
            'title' => __('system.student_transport_fees'),
            'contentView' => 'transport::admin.student_fees.index',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'classId' => $request->input('class_id'),
            'sectionId' => $request->input('section_id'),
            'students' => $students,
            'searched' => $searched,
            'showFatherName' => $this->fees->showFatherName(),
            'currencySymbol' => $this->school->currencySymbol(),
            'canAssign' => $this->permissions->hasPrivilege('student_transport_fees', 'can_add')
                || $this->permissions->hasPrivilege('student_transport_fees', 'can_edit'),
        ]);
    }

    /**
     * CI admin/pickuppoint/student_transport_months — JSON {status, error, page}.
     */
    public function months(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_transport_fees', 'can_view'), 403);

        $validated = $request->validate([
            'student_session_id' => ['required', 'integer'],
        ]);

        $payload = $this->fees->monthsForStudentSession((int) $validated['student_session_id']);

        $page = view('transport::admin.student_fees.months', [
            'student' => $payload['student'],
            'routePickupPoint' => $payload['route_pickup_point'],
            'routePickupPointId' => $payload['route_pickup_point_id'],
            'studentSessionId' => (int) $validated['student_session_id'],
            'fees' => $payload['fees'],
            'hasFeemaster' => $payload['has_feemaster'],
            'currencySymbol' => $this->school->currencySymbol(),
            'showRollNo' => $this->fees->showRollNo(),
            'studentName' => $this->fees->studentDisplayName($payload['student']),
            'canAssign' => $this->permissions->hasPrivilege('student_transport_fees', 'can_add')
                || $this->permissions->hasPrivilege('student_transport_fees', 'can_edit'),
        ])->render();

        return response()->json([
            'status' => '1',
            'error' => '',
            'page' => $page,
        ]);
    }

    /**
     * CI admin/pickuppoint/add_student_fees — JSON {status, error, message}.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('student_transport_fees', 'can_add')
            || $this->permissions->hasPrivilege('student_transport_fees', 'can_edit'),
            403
        );

        $validated = $request->validate([
            'student_session_id' => ['required', 'integer'],
            'route_pickup_point_id' => ['required', 'integer'],
            'transport_route_fee' => ['nullable', 'array'],
            'transport_route_fee.*' => ['integer'],
            'prev_ids' => ['nullable', 'array'],
            'prev_ids.*' => ['nullable'],
        ]);

        $selected = array_map('intval', $validated['transport_route_fee'] ?? []);
        $prevIds = $validated['prev_ids'] ?? [];
        $existingByMaster = [];

        foreach ($selected as $masterId) {
            $key = 'student_transport_fee_id_'.$masterId;
            $existingByMaster[$masterId] = (int) $request->input($key, 0);
        }

        $this->fees->assign(
            (int) $validated['student_session_id'],
            (int) $validated['route_pickup_point_id'],
            $selected,
            is_array($prevIds) ? $prevIds : [],
            $existingByMaster,
        );

        return response()->json([
            'status' => 1,
            'error' => '',
            'message' => __('system.success_message'),
        ]);
    }
}
