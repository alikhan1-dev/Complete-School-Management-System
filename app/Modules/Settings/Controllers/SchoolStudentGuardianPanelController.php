<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolStudentGuardianPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Schsettings::studentguardianpanel + studentguardian.
 */
class SchoolStudentGuardianPanelController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolStudentGuardianPanelService $panels,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->panels->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.student_guardian_panel'),
            'contentView' => 'settings::admin.student_guardian_panel.index',
            'pageTitle' => __('system.student_guardian_panel'),
            'result' => $setting,
            'studentLoginOptions' => $this->panels->decodeLoginOptions($setting->student_login),
            'parentLoginOptions' => $this->panels->decodeLoginOptions($setting->parent_login),
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::studentguardian — no field validation; always success JSON.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        // CI: isset($_POST['student_timeline']) → enabled else disabled (checkbox value ignored).
        $timeline = $request->has('student_timeline') ? 'enabled' : 'disabled';

        $this->panels->save([
            'id' => $request->input('sch_id'),
            'student_timeline' => $timeline,
            'student_login' => $request->input('student_login'),
            'parent_login' => $request->input('parent_login'),
            'student_panel_login' => $request->has('student_panel_login') ? 1 : 0,
            'parent_panel_login' => $request->has('parent_panel_login') ? 1 : 0,
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/studentguardianpanel')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
