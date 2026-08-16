<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolGeneralSettingService;
use App\Modules\Settings\Support\SchSettingLists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

/**
 * CI Schsettings::index + generalsetting + getSchsetting.
 */
class SchoolGeneralSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolGeneralSettingService $settings,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->settings->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.general_setting'),
            'contentView' => 'settings::admin.general.index',
            'pageTitle' => __('system.general_setting'),
            'result' => $setting,
            'sessionlist' => $this->settings->sessions(),
            'timezoneList' => SchSettingLists::timezones(),
            'monthList' => SchSettingLists::months(),
            'daysList' => SchSettingLists::days(),
            'dateFormatList' => SchSettingLists::dateFormats(),
            'currency_formats' => SchSettingLists::currencyFormats(),
            'currencyPlace' => SchSettingLists::currencyPlaces(),
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::generalsetting — JSON because CI JS already posts this URL.
     */
    public function generalsetting(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $validator = Validator::make($request->all(), [
            'currency_format' => ['required'],
            'sch_session_id' => ['required'],
            'sch_name' => ['required'],
            'sch_phone' => ['required'],
            'sch_start_month' => ['required'],
            'sch_address' => ['required'],
            'sch_email' => ['required'],
            'sch_timezone' => ['required'],
            'currency_place' => ['required'],
            'sch_date_format' => ['required'],
            'sch_start_week' => ['required'],
            'base_url' => ['required'],
            'folder_path' => ['required'],
        ], [], [
            'currency_format' => __('system.currency_format'),
            'sch_session_id' => __('system.session'),
            'sch_name' => __('system.school_name'),
            'sch_phone' => __('system.phone'),
            'sch_start_month' => __('system.start_month'),
            'sch_address' => __('system.address'),
            'sch_email' => __('system.email'),
            'sch_timezone' => __('system.timezone'),
            'currency_place' => __('system.currency_place'),
            'sch_date_format' => __('system.date_format'),
            'sch_start_week' => __('system.start_day_of_week'),
            'base_url' => __('system.url'),
            'folder_path' => __('system.folder_path'),
        ]);

        if ($validator->fails()) {
            $error = [];
            foreach ([
                'sch_session_id', 'sch_name', 'sch_phone', 'sch_start_month', 'sch_start_week',
                'sch_address', 'sch_email', 'sch_timezone', 'currency_place', 'currency_format',
                'sch_date_format', 'base_url', 'folder_path',
            ] as $field) {
                $error[$field] = $validator->errors()->has($field)
                    ? '<p>'.$validator->errors()->first($field).'</p>'
                    : '';
            }

            if ($this->wantsCiJson($request)) {
                return response()->json(['status' => 'fail', 'error' => $error]);
            }

            return redirect('schsettings')->withErrors($validator)->withInput();
        }

        $this->settings->saveGeneral([
            'id' => $request->input('sch_id'),
            'session_id' => $request->input('sch_session_id'),
            'name' => $request->input('sch_name'),
            'phone' => $request->input('sch_phone'),
            'dise_code' => $request->input('sch_dise_code'),
            'start_month' => $request->input('sch_start_month'),
            'start_week' => $request->input('sch_start_week'),
            'address' => $request->input('sch_address'),
            'email' => $request->input('sch_email'),
            'timezone' => $request->input('sch_timezone'),
            'date_format' => $request->input('sch_date_format'),
            'currency_format' => $request->input('currency_format'),
            'currency_place' => $request->input('currency_place'),
            'base_url' => $request->input('base_url'),
            'folder_path' => $request->input('folder_path'),
        ]);

        $admin = session('admin', []);
        $admin['base_url'] = $request->input('base_url');
        $admin['folder_path'] = $request->input('folder_path');
        $admin['currency_format'] = $request->input('currency_format');
        $admin['date_format'] = $request->input('sch_date_format');
        $admin['start_week'] = date('w', strtotime((string) $request->input('sch_start_week')));
        $admin['timezone'] = $request->input('sch_timezone');
        $admin['currency_place'] = $request->input('currency_place');
        session(['admin' => $admin]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings')->with('success', __('system.success_message'));
    }

    public function getSchsetting(): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        return response()->json($this->settings->getSettingPayload());
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
