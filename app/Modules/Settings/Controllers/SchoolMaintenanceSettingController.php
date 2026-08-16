<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolMaintenanceSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Schsettings::maintenance + save_maintenance.
 * CI JSON uses numeric status 0/1 (not success/fail).
 */
class SchoolMaintenanceSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolMaintenanceSettingService $maintenance,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->maintenance->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.maintenance'),
            'contentView' => 'settings::admin.maintenance.index',
            'pageTitle' => __('system.maintenance'),
            'result' => $setting,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::save_maintenance — JSON because CI form posts via AJAX.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        // CI only applies trim|xss_clean — no required rule; validation effectively always passes.
        $this->maintenance->save([
            'id' => $request->input('sch_id'),
            'maintenance_mode' => $request->input('maintenance_mode'),
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 1,
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/maintenance')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
