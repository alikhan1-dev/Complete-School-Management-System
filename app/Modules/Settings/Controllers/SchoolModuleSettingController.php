<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolModuleSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * CI admin/Module — system / student / parent module toggles.
 */
class SchoolModuleSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolModuleSettingService $modules,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('superadmin', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => __('system.modules'),
            'contentView' => 'settings::admin.module.index',
            'pageTitle' => __('system.modules'),
            'permissionList' => $this->modules->systemModules(),
            'studentpermissionList' => $this->modules->studentParentModules(),
            'parentpermissionList' => $this->modules->studentParentModules(),
        ]);
    }

    /**
     * CI admin/module/changeStatus — JSON {status:1, msg}.
     */
    public function changeStatus(Request $request): JsonResponse|RedirectResponse|Response
    {
        $id = (int) $request->input('id');
        if ($id <= 0) {
            return $this->emptyWhenMissingId($request);
        }

        $this->modules->changeSystemStatus($id, (int) $request->input('status', 0));

        return $this->statusChanged($request);
    }

    /**
     * CI admin/module/changeStudentStatus — student or parent column.
     * View parent toggles also POST here (not changeParentStatus).
     */
    public function changeStudentStatus(Request $request): JsonResponse|RedirectResponse|Response
    {
        $id = (int) $request->input('id');
        if ($id <= 0) {
            return $this->emptyWhenMissingId($request);
        }

        $role = (string) $request->input('role', 'student');
        if (! in_array($role, ['student', 'parent'], true)) {
            $role = 'student';
        }

        $this->modules->changeStudentParentStatus($id, $role, (int) $request->input('status', 0));

        return $this->statusChanged($request);
    }

    /**
     * CI PHP wrote permission_parent (not in this schema). Live view never posts here.
     * Map to permission_student.parent so the CI URL still exists.
     */
    public function changeParentStatus(Request $request): JsonResponse|RedirectResponse|Response
    {
        $request->merge(['role' => 'parent']);

        return $this->changeStudentStatus($request);
    }

    protected function statusChanged(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('system.status_change_successfully');

        if ($this->wantsCiJson($request)) {
            return response()->json(['status' => 1, 'msg' => $message]);
        }

        return redirect('admin/module')->with('success', $message);
    }

    protected function emptyWhenMissingId(Request $request): Response
    {
        if ($this->wantsCiJson($request)) {
            return response('', 200);
        }

        return redirect('admin/module');
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
