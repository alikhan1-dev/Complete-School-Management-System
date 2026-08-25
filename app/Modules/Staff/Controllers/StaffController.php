<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Leave\Services\LeaveRequestService;
use App\Modules\Payroll\Services\PayrollService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\DataTableResponse;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Requests\StoreStaffRequest;
use App\Modules\Staff\Requests\UpdateStaffRequest;
use App\Modules\Staff\Services\StaffAdmissionService;
use App\Modules\Staff\Services\StaffDeleteService;
use App\Modules\Staff\Services\StaffDocumentService;
use App\Modules\Staff\Services\StaffImportService;
use App\Modules\Staff\Services\StaffListService;
use App\Modules\Staff\Services\StaffPhotoService;
use App\Modules\Staff\Services\StaffProfileService;
use App\Modules\Staff\Services\StaffRatingService;
use App\Modules\Staff\Services\StaffTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected CustomFieldValueService $customFields,
        protected StaffAdmissionService $admission,
        protected StaffProfileService $profile,
        protected StaffDocumentService $documents,
        protected StaffPhotoService $photos,
        protected StaffDeleteService $deletion,
        protected StaffImportService $importer,
        protected StaffListService $staffList,
        protected PayrollService $payroll,
        protected SchoolContext $school,
        protected StaffTimelineService $timeline,
        protected LeaveRequestService $leaveRequests,
        protected StaffRatingService $ratings,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Staff',
            'contentView' => 'staff::admin.index',
            'canAdd' => $this->permissions->hasPrivilege('staff', 'can_add'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_view'), 403);

        $draw = (int) $request->input('draw', 1);
        $canDelete = $this->permissions->hasPrivilege('staff', 'can_delete');
        /** @var Staff|null $actor */
        $actor = Auth::guard('staff')->user();

        $rows = $this->staffList->activeStaffQuery()->limit(500)->get()->map(function (Staff $staff) use ($canDelete, $actor) {
            $profileUrl = route('staff.profile', $staff->id);
            $editUrl = route('staff.edit', $staff->id);

            $actions = '<a href="'.$profileUrl.'" class="btn btn-default btn-xs">View</a> '
                .'<a href="'.$editUrl.'" class="btn btn-default btn-xs">Edit</a>';

            if ($canDelete && $actor !== null) {
                $roleId = (int) DB::table('staff_roles')->where('staff_id', $staff->id)->value('role_id');
                if ((int) $actor->id !== (int) $staff->id && $roleId !== 7) {
                    $deleteUrl = route('staff.destroy', $staff->id);
                    $actions .= ' <a href="'.$deleteUrl.'" class="btn btn-danger btn-xs" '
                        .'onclick="return confirm('.json_encode((string) __('system.delete_confirm')).');">Delete</a>';
                }
            }

            return [
                $staff->id,
                $staff->employee_id,
                trim($staff->name.' '.$staff->surname),
                $staff->email,
                ((int) $staff->is_active === 1) ? 'Active' : 'Inactive',
                $actions,
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
            'roles' => $this->staffList->rolesForFilter(),
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
            $result = $this->admission->create(
                $request->validated(),
                $customRows,
                $this->documents->uploadsFromRequest($request),
                $this->photos->photoFromRequest($request),
            );
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
            'roles' => $this->staffList->rolesForFilter(),
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
            $this->admission->update(
                $id,
                $request->validated(),
                $customRows,
                $this->documents->uploadsFromRequest($request),
                $this->photos->photoFromRequest($request),
            );
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
        $salarySummary = $this->payroll->paidSalarySummary($id);
        $isTeacherProfile = $this->ratings->isTeacherProfile($staffProfile);

        return view('shared::layouts.admin', [
            'title' => 'Staff Details',
            'contentView' => 'staff::admin.profile',
            'staffProfile' => $staffProfile,
            'enableDisable' => $enableDisable,
            'canDisableStaff' => $this->permissions->hasPrivilege('disable_staff', 'can_view'),
            'canEditStaff' => $this->permissions->hasPrivilege('staff', 'can_edit'),
            'canViewPayroll' => $this->permissions->hasPrivilege('staff_payroll', 'can_view'),
            'canAddTimeline' => $this->permissions->hasPrivilege('staff_timeline', 'can_add'),
            'canEditTimeline' => $this->permissions->hasPrivilege('staff_timeline', 'can_edit'),
            'canDeleteTimeline' => $this->permissions->hasPrivilege('staff_timeline', 'can_delete'),
            'customFieldValues' => $this->customFields->valuesMap('staff', $id),
            'customFields' => $this->customFields->fieldsFor('staff'),
            'attendanceYears' => $this->profile->attendanceYearOptions(),
            'defaultAttendanceYear' => (int) date('Y'),
            'staffDocuments' => $this->documents->listForProfile($staffProfile),
            'staffPhotoUrl' => $this->photos->publicUrl((string) ($staffProfile->image ?? '')),
            'timelineList' => $this->timeline->listFor($id, $visibleTimelineOnly),
            'staffPayroll' => $this->payroll->staffPayrollForProfile($id),
            'salarySummary' => $salarySummary,
            'payrollStatusLabels' => PayrollService::PAYROLL_STATUS,
            'paymentModeLabels' => PayrollService::PAYMENT_MODE,
            'leaveDetails' => $this->leaveRequests->profileLeaveDetails($id),
            'staffLeaves' => $this->leaveRequests->listRequests($id),
            'leaveStatusLabels' => LeaveRequestService::STATUS_LABELS,
            'canViewLeaveRequest' => $this->permissions->hasPrivilege('approve_leave_request', 'can_view'),
            'isTeacherProfile' => $isTeacherProfile,
            'staffRatingSummary' => $isTeacherProfile ? $this->ratings->summaryForProfile($id) : null,
            'staffReviews' => $isTeacherProfile ? $this->ratings->approvedReviews($id) : [],
            'currencySymbol' => $this->school->currencySymbol(),
            'schoolDateFormat' => $this->school->dateFormat(),
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

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_delete'), 403);

        $target = Staff::query()->findOrFail($id);
        /** @var Staff $actor */
        $actor = Auth::guard('staff')->user();
        $this->deletion->assertCanDelete($target, $actor);
        $this->deletion->delete($id);

        return redirect()
            ->route('staff.index')
            ->with('success', (string) __('system.delete_message'));
    }

    public function import(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_add'), 403);

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'role' => ['required', 'integer', 'min:1'],
                'designation' => ['nullable'],
                'department' => ['nullable'],
                'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            ]);

            $uploaded = $request->file('file');
            abort_unless($uploaded !== null, 422);

            if (strtolower($uploaded->getClientOriginalExtension()) !== 'csv') {
                return redirect()
                    ->route('staff.import')
                    ->withErrors(['file' => (string) __('system.extension_not_allowed')]);
            }

            $result = $this->importer->importFromCsv(
                $uploaded->getRealPath(),
                (int) $validated['role'],
                StaffImportService::normalizeOptionalId($validated['department'] ?? null),
                StaffImportService::normalizeOptionalId($validated['designation'] ?? null),
            );

            return redirect()
                ->route('staff.import')
                ->with('success', __('system.total').' '.$result['total'].' '
                    .__('system.records_found_in_CSV_file_total').' '.$result['imported'].' '
                    .__('system.records_imported_successfully'));
        }

        return view('shared::layouts.admin', [
            'title' => __('system.staff_import'),
            'contentView' => 'staff::admin.import',
            'fields' => StaffImportService::DISPLAY_FIELDS,
            'roles' => $this->staffList->rolesForFilter(),
            'departments' => DB::table('department')->where('is_active', 'yes')->orderBy('id')->get(),
            'designations' => DB::table('staff_designation')->where('is_active', 'yes')->orderBy('id')->get(),
        ]);
    }

    public function exportFormat(): StreamedResponse|BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff', 'can_add'), 403);

        $path = $this->importer->sampleCsvPath();
        abort_unless(File::isFile($path), 404);

        return response()->download($path, 'staff_csvfile.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
