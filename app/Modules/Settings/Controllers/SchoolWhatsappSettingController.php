<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolWhatsappSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * CI Schsettings::whatsappsettings + savewhatsappsettings.
 * Fail status is string "fail"; success status is numeric 1.
 */
class SchoolWhatsappSettingController extends Controller
{
    /** @var list<string> */
    private const PANEL_TOGGLES = [
        'front_side_whatsapp',
        'admin_panel_whatsapp',
        'student_panel_whatsapp',
    ];

    /** @var list<string> */
    private const ERROR_FIELDS = [
        'sch_id',
        'front_side_whatsapp',
        'admin_panel_whatsapp',
        'student_panel_whatsapp',
        'front_side_whatsapp_mobile',
        'admin_panel_whatsapp_mobile',
        'student_panel_whatsapp_mobile',
        'front_side_whatsapp_from',
        'front_side_whatsapp_to',
        'admin_panel_whatsapp_from',
        'admin_panel_whatsapp_to',
        'student_panel_whatsapp_from',
        'student_panel_whatsapp_to',
        'time_to',
    ];

    public function __construct(
        protected PermissionService $permissions,
        protected SchoolWhatsappSettingService $whatsapp,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->whatsapp->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.whatsapp_settings'),
            'contentView' => 'settings::admin.whatsapp.index',
            'pageTitle' => __('system.whatsapp_settings'),
            'result' => $setting,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::savewhatsappsettings — JSON because CI JS posts this URL.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $rules = [
            'sch_id' => ['required'],
        ];
        $attributes = [
            'sch_id' => 'sch_id',
        ];

        foreach (self::PANEL_TOGGLES as $toggle) {
            $mobile = $toggle.'_mobile';
            $from = $toggle.'_from';
            $to = $toggle.'_to';

            if ($request->boolean($toggle)) {
                $rules[$mobile] = ['required'];
                $attributes[$mobile] = __('system.mobile_no');
            }

            $fromVal = $request->input($from);
            $toVal = $request->input($to);

            if (empty($fromVal) && ! empty($toVal)) {
                $rules[$from] = ['required'];
                $attributes[$from] = __('system.time_from');
            }

            if (! empty($fromVal) && empty($toVal)) {
                $rules[$to] = ['required'];
                $attributes[$to] = __('system.time_to');
            }
        }

        $validator = Validator::make($request->all(), $rules, [], $attributes);

        // CI: rules on synthetic field "time_to" with callback_time_check.
        $validator->after(function ($validator) use ($request) {
            foreach (self::PANEL_TOGGLES as $toggle) {
                $fromRaw = $request->input($toggle.'_from');
                $toRaw = $request->input($toggle.'_to');
                if ($fromRaw === null || $fromRaw === '' || $toRaw === null || $toRaw === '') {
                    continue;
                }

                $from = strtotime((string) $fromRaw);
                $to = strtotime((string) $toRaw);
                if (! empty($from) && ! empty($to) && $from >= $to) {
                    // CI message: "%s cannot less than from time %s" for field time_to.
                    $validator->errors()->add(
                        'time_to',
                        __('system.time_to').' cannot less than from time '.__('system.time_to')
                    );
                    break;
                }
            }
        });

        if ($validator->fails()) {
            $error = [];
            foreach (self::ERROR_FIELDS as $field) {
                $error[$field] = $validator->errors()->has($field)
                    ? '<p>'.$validator->errors()->first($field).'</p>'
                    : '';
            }

            if ($this->wantsCiJson($request)) {
                return response()->json(['status' => 'fail', 'error' => $error]);
            }

            return redirect('schsettings/whatsappsettings')->withErrors($validator)->withInput();
        }

        $frontFrom = $request->input('front_side_whatsapp_from') ?: null;
        $frontTo = $request->input('front_side_whatsapp_to') ?: null;
        $adminFrom = $request->input('admin_panel_whatsapp_from') ?: null;
        $adminTo = $request->input('admin_panel_whatsapp_to') ?: null;
        $studentFrom = $request->input('student_panel_whatsapp_from') ?: null;
        $studentTo = $request->input('student_panel_whatsapp_to') ?: null;

        $this->whatsapp->save([
            'id' => $request->input('sch_id'),
            'front_side_whatsapp' => $request->input('front_side_whatsapp'),
            'front_side_whatsapp_mobile' => $request->input('front_side_whatsapp_mobile'),
            'front_side_whatsapp_from' => $frontFrom,
            'front_side_whatsapp_to' => $frontTo,
            'admin_panel_whatsapp' => $request->input('admin_panel_whatsapp'),
            'admin_panel_whatsapp_mobile' => $request->input('admin_panel_whatsapp_mobile'),
            'admin_panel_whatsapp_from' => $adminFrom,
            'admin_panel_whatsapp_to' => $adminTo,
            'student_panel_whatsapp' => $request->input('student_panel_whatsapp'),
            'student_panel_whatsapp_mobile' => $request->input('student_panel_whatsapp_mobile'),
            'student_panel_whatsapp_from' => $studentFrom,
            'student_panel_whatsapp_to' => $studentTo,
        ]);

        $admin = session('admin', []);
        $admin['admin_panel_whatsapp'] = $request->input('admin_panel_whatsapp');
        $admin['admin_panel_whatsapp_mobile'] = $request->input('admin_panel_whatsapp_mobile');
        $admin['admin_panel_whatsapp_from'] = $adminFrom;
        $admin['admin_panel_whatsapp_to'] = $adminTo;
        session(['admin' => $admin]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 1,
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/whatsappsettings')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
