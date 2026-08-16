<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolGoogleDriveSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * CI Schsettings::googledrivesetting + savegoogledrive.
 * Success: {st:0,msg}; fail: {st:1,msg:{field:errors}}.
 */
class SchoolGoogleDriveSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolGoogleDriveSettingService $drive,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->drive->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.google_drive_setting'),
            'contentView' => 'settings::admin.google_drive.index',
            'pageTitle' => __('system.google_drive_setting'),
            'settingResult' => $setting,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::savegoogledrive — JSON because CI form posts via AJAX.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $validator = Validator::make($request->all(), [
            'client_id' => ['required'],
            'api_key' => ['required'],
            'project_number' => ['required'],
            'is_enable' => ['required'],
        ], [], [
            'client_id' => __('system.client_id'),
            'api_key' => __('system.api_key'),
            'project_number' => __('system.project_number_app_id'),
            'is_enable' => __('system.status'),
        ]);

        if ($validator->fails()) {
            $msg = [
                'client_id' => $validator->errors()->has('client_id')
                    ? '<p>'.$validator->errors()->first('client_id').'</p>'
                    : '',
                'api_key' => $validator->errors()->has('api_key')
                    ? '<p>'.$validator->errors()->first('api_key').'</p>'
                    : '',
                'project_number' => $validator->errors()->has('project_number')
                    ? '<p>'.$validator->errors()->first('project_number').'</p>'
                    : '',
                'is_enable' => $validator->errors()->has('is_enable')
                    ? '<p>'.$validator->errors()->first('is_enable').'</p>'
                    : '',
            ];

            if ($this->wantsCiJson($request)) {
                return response()->json(['st' => 1, 'msg' => $msg]);
            }

            return redirect('schsettings/googledrivesetting')->withErrors($validator)->withInput();
        }

        // CI stores posted values as-is (JS appends enabled/disabled for all four flags).
        $this->drive->save([
            'id' => $request->input('id'),
            'client_id' => $request->input('client_id'),
            'api_key' => $request->input('api_key'),
            'project_number' => $request->input('project_number'),
            'is_enable' => $request->input('is_enable'),
            'is_student' => $request->input('is_student'),
            'is_parent' => $request->input('is_parent'),
            'is_staff' => $request->input('is_staff'),
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'st' => 0,
                'msg' => __('system.update_message'),
            ]);
        }

        return redirect('schsettings/googledrivesetting')->with('success', __('system.update_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
