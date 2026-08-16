<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolMobileAppSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Schsettings::mobileapp + savemobileapp.
 * Purchase-code registration (admin/admin/updateandappCode) is deferred.
 */
class SchoolMobileAppSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolMobileAppSettingService $mobileApp,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->mobileApp->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.mobile_app'),
            'contentView' => 'settings::admin.mobile_app.index',
            'pageTitle' => __('system.mobile_app'),
            'result' => $setting,
            // Live Envato andapp_validate deferred — treat as not registered locally.
            'appResponse' => false,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::savemobileapp — no field validation; always success JSON in CI.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $this->mobileApp->save([
            'id' => $request->input('sch_id'),
            'mobile_api_url' => $request->input('mobile_api_url'),
            'app_primary_color_code' => $request->input('app_primary_color_code'),
            'app_secondary_color_code' => $request->input('app_secondary_color_code'),
            'admin_mobile_api_url' => $request->input('admin_mobile_api_url'),
            'admin_app_primary_color_code' => $request->input('admin_app_primary_color_code'),
            'admin_app_secondary_color_code' => $request->input('admin_app_secondary_color_code'),
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/mobileapp')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
