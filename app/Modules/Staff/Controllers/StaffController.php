<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Roles\Models\Role;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\DataTableResponse;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Requests\StoreStaffRequest;
use App\Modules\Staff\Requests\UpdateStaffRequest;
use App\Modules\Staff\Services\StaffAdmissionService;
use App\Modules\Staff\Services\StaffDocumentService;
use App\Modules\Staff\Services\StaffProfileService;
use App\Modules\Staff\Services\StaffTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaffController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected CustomFieldValueService $customFields,
        protected StaffAdmissionService $admission,
        protected StaffProfileService $profile,
        protected StaffDocumentService $documents,
        protected StaffTimelineService $timeline,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Staff',
            'contentView' => 'staff::admin.index',
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_view'), 403);

        $draw = (int) $request->input('draw', 1);
        $rows = Staff::query()->orderBy('id')->limit(500)->get()->map(function (Staff $staff) {
            $profileUrl = route('staff.profile', $staff->id);
            $editUrl = route('staff.edit', $staff->id);

            return [
                $staff->id,
                $staff->employee_id,
                trim($staff->name.' '.$staff->surname),
                $staff->email,
                ((int) $staff->is_active === 1) ? 'Active' : 'Inactive',
                '<a href="'.$profileUrl.'" class="btn btn-default btn-xs">View</a> '
                .'<a href="'.$editUrl.'" class="btn btn-default btn-xs">Edit</a>',
            ];
        })->all();

        return DataTableResponse::make($draw, count($rows), count($rows), $rows);
    }

    public function create(): View
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_add'), 403);

        $schSetting = SchSetting::query()->orderBy('id')->first();

        return view('shared::layouts.admin', [
            'title' => 'Add Staff',
            'contentView' => 'staff::admin.create',
            'roles' => Role::query()->orderBy('id')->get(),
            'departments' => DB::table('department')->where('is_active', 'yes')->orderBy('id')->get(),
            'designations' => DB::table('staff_designation')->where('is_active', 'yes')->orderBy('id')->get(),
            'leaveTypes' => DB::table('leave_types')->orderBy('id')->get(),
            'schSetting' => $schSetting,
            'customFields' => $this->customFields->fieldsFor('staff'),
            'maritalStatuses' => $this->maritalStatuses(),
            'contractTypes' => $this->contractTypes(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_add'), 403);

        $customRows = $this->customFields->normalizePosted(
            'staff',
            (array) data_get($request->all(), 'custom_fields.staff', [])
        );

        try {
            $result = $this->admission->create($request->validated(), $customRows);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['employee_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff created successfully. Employee ID: '.$result['employee_id'].' — login password: '.$result['password']);
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_edit'), 403);

        $staffRow = $this->admission->findForEdit($id);
        abort_if($staffRow === null, 404);

        $staff = Staff::query()->findOrFail($id);
        $this->assertCanEditStaff($staff);

        $schSetting = SchSetting::query()->orderBy('id')->first();
        $leaveDetails = $this->admission->leaveDetailsForSession($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Staff',
            'contentView' => 'staff::admin.edit',
            'staff' => $staff,
            'staffRoleId' => (int) ($staffRow['role_id'] ?? 0),
            'roles' => Role::query()->orderBy('id')->get(),
            'departments' => DB::table('department')->where('is_active', 'yes')->orderBy('id')->get(),
            'designations' => DB::table('staff_designation')->where('is_active', 'yes')->orderBy('id')->get(),
            'leaveTypes' => DB::table('leave_types')->orderBy('id')->get(),
            'staffLeaveDetails' => $leaveDetails,
            'schSetting' => $schSetting,
            'customFields' => $this->customFields->fieldsFor('staff'),
            'customFieldValues' => $this->customFields->valuesMap('staff', $id),
            'maritalStatuses' => $this->maritalStatuses(),
            'contractTypes' => $this->contractTypes(),
        ]);
    }

    public function update(UpdateStaffRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_edit'), 403);

        $staff = Staff::query()->findOrFail($id);
        $this->assertCanEditStaff($staff);

        $customRows = $this->customFields->normalizePosted(
            'staff',
            (array) data_get($request->all(), 'custom_fields.staff', [])
        );

        try {
            $this->admission->update($id, $request->validated(), $customRows);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['employee_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('staff.index')
            ->with('success', __('system.update_message'));
    }

    public function profile(int $id): View
    {
        $this->assertCanViewStaffProfile($id);

        $staffProfile = $this->profile->profile($id);
        abort_if($staffProfile === null, 404);

        /** @var Staff $actor */
        $actor = Auth::guard('staff')->user();
        $enableDisable = (int) $actor->id !== $id;
        $visibleTimelineOnly = (int) $actor->id === $id;

        return view('shared::layouts.admin', [
            'title' => 'Staff Details',
            'contentView' => 'staff::admin.profile',
            'staffProfile' => $staffProfile,
            'enableDisable' => $enableDisable,
            'canDisableStaff' => $this->permissions->hasPrivilege('disable_staff', 'can_view'),
            'canEditStaff' => $this->permissions->hasPrivilege('staff', 'can_edit'),
            'canAddTimeline' => $this->permissions->hasPrivilege('staff_timeline', 'can_add'),
            'canEditTimeline' => $this->permissions->hasPrivilege('staff_timeline', 'can_edit'),
            'canDeleteTimeline' => $this->permissions->hasPrivilege('staff_timeline', 'can_delete'),
            'customFieldValues' => $this->customFields->valuesMap('staff', $id),
            'customFields' => $this->customFields->fieldsFor('staff'),
            'attendanceYears' => $this->profile->attendanceYearOptions(),
            'defaultAttendanceYear' => (int) date('Y'),
            'staffDocuments' => $this->documents->listForProfile($staffProfile),
            'timelineList' => $this->timeline->listFor($id, $visibleTimelineOnly),
            'editingTimeline' => request()->filled('edit_timeline')
                ? $this->timeline->find((int) request()->query('edit_timeline'))
                : null,
        ]);
    }

    public function downloadDocument(int $staffId, string $doc): BinaryFileResponse
    {
        $this->assertCanViewStaffProfile($staffId);

        $staffProfile = $this->profile->profile($staffId);
        abort_if($staffProfile === null, 404);

        try {
            $fileName = $this->documents->filename($staffProfile, $doc);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        abort_if($fileName === null, 404);

        $path = $this->documents->absolutePath($staffId, $fileName);
        abort_unless(File::isFile($path), 404);

        return response()->download($path, basename($fileName));
    }

    public function deleteDocument(int $id, string $doc): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_edit'), 403);

        $staff = Staff::query()->findOrFail($id);
        $this->assertCanEditStaff($staff);

        try {
            $this->documents->delete($id, $doc);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        return redirect()
            ->route('staff.profile', $id)
            ->with('success', (string) __('system.delete_message'));
    }

    public function ajaxAttendance(Request $request): JsonResponse
    {
        $staffId = (int) $request->input('id');
        $year = (int) $request->input('year');
        $this->assertCanViewStaffProfile($staffId);

        abort_if($year <= 0, 422);

        $staffProfile = $this->profile->profile($staffId);
        abort_if($staffProfile === null, 404);

        $payload = $this->profile->profileAttendanceMatrix($staffId, $year);
        $countForYear = $payload['countAttendance'][$year] ?? [];

        $page = view('staff::admin.partials.ajax_attendance', array_merge($payload, [
            'staff' => $staffProfile,
        ]))->render();

        return response()->json([
            'status' => 1,
            'countAttendance' => $countForYear,
            'page' => $page,
        ]);
    }

    public function disableStaff(Request $request, int $id): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('disable_staff', 'can_view'), 403);

        $target = Staff::query()->findOrFail($id);
        /** @var Staff $actor */
        $actor = Auth::guard('staff')->user();
        $this->profile->assertCanManageStatus($target, $actor);

        $actorRoleId = $this->profile->roleId((int) $actor->id);
        if ($actorRoleId === 7) {
            $validated = $request->validate([
                'date' => ['required', 'date'],
            ]);
            $disableAt = (string) $validated['date'];
            $this->profile->disable($id, $disableAt);

            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => (string) __('system.success_message'),
            ]);
        }

        $this->profile->disable($id, null);

        return redirect()
            ->route('staff.profile', $id)
            ->with('success', (string) __('system.success_message'));
    }

    public function enableStaff(int $id): RedirectResponse
    {
        $target = Staff::query()->findOrFail($id);
        /** @var Staff $actor */
        $actor = Auth::guard('staff')->user();
        $this->profile->assertCanManageStatus($target, $actor);

        $this->profile->enable($id);

        return redirect()
            ->route('staff.profile', $id)
            ->with('success', (string) __('system.success_message'));
    }

    protected function assertCanEditStaff(Staff $target): void
    {
        $roleId = (int) DB::table('staff_roles')->where('staff_id', $target->id)->value('role_id');
        if ($roleId !== 7) {
            return;
        }

        /** @var Staff|null $actor */
        $actor = Auth::guard('staff')->user();
        abort_if($actor === null || $actor->email !== $target->email, 403);
    }

    protected function assertCanViewStaffProfile(int $staffId): void
    {
        /** @var Staff|null $actor */
        $actor = Auth::guard('staff')->user();
        abort_if($actor === null, 403);

        if ((int) $actor->id !== $staffId) {
            abort_unless($this->permissions->hasPrivilege('staff', 'can_view'), 403);
        }
    }

    /**
     * CI payroll.php marital_status keys.
     *
     * @return array<string, string>
     */
    protected function maritalStatuses(): array
    {
        return [
            'Single' => __('system.single'),
            'Married' => __('system.married'),
            'Widowed' => __('system.widowed'),
            'Seperated' => __('system.separated'),
            'Not Specified' => __('system.not_specified'),
        ];
    }

    /**
     * CI payroll.php contracttype keys.
     *
     * @return array<string, string>
     */
    protected function contractTypes(): array
    {
        return [
            'permanent' => __('system.permanent'),
            'probation' => __('system.probation'),
        ];
    }
}
