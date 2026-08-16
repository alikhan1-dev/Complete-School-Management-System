<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolChatSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Schsettings::chatsetting + savechatsetting.
 */
class SchoolChatSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolChatSettingService $chatSettings,
    ) {
    }

    public function index(): View
    {
        // CI: module_lib->hasActive('chat') then access_denied.
        abort_unless($this->chatSettings->isChatModuleActive(), 403);
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->chatSettings->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            // CI view title uses student_guardian_panel lang key.
            'title' => __('system.student_guardian_panel'),
            'contentView' => 'settings::admin.chat_setting.index',
            'pageTitle' => __('system.student_guardian_panel'),
            'result' => $setting,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::savechatsetting — no validation; always success JSON.
     * CI does not re-check module active on save.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $this->chatSettings->save([
            'id' => $request->input('sch_id'),
            'student_delete_chat' => $request->input('student_delete_chat'),
            'guardian_delete_chat' => $request->input('guardian_delete_chat'),
            'staff_delete_chat' => $request->input('staff_delete_chat'),
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/chatsetting')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
