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
use App\Modules\Staff\Services\StaffAdmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected CustomFieldValueService $customFields,
        protected StaffAdmissionService $admission,
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
            return [
                $staff->id,
                $staff->employee_id,
                trim($staff->name.' '.$staff->surname),
                $staff->email,
                ((int) $staff->is_active === 1) ? 'Active' : 'Inactive',
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
