<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolBackendThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * CI Schsettings::backendtheme + savebackendtheme.
 */
class SchoolBackendThemeController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolBackendThemeService $themes,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->themes->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.backend_theme'),
            'contentView' => 'settings::admin.backend_theme.index',
            'pageTitle' => __('system.backend_theme'),
            'result' => $setting,
            'themeSetting' => $this->themes->themeArray($setting),
            'presetColors' => SchoolBackendThemeService::PRESET_COLORS,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::savebackendtheme — JSON because CI JS already posts this URL.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        // CI only requires sch_id; fail payload key is still "theme".
        $validator = Validator::make($request->all(), [
            'sch_id' => ['required'],
        ], [], [
            'sch_id' => __('system.id'),
        ]);

        if ($validator->fails()) {
            $error = ['theme' => $validator->errors()->has('sch_id')
                ? '<p>'.$validator->errors()->first('sch_id').'</p>'
                : ''];

            if ($this->wantsCiJson($request)) {
                return response()->json(['status' => 'fail', 'error' => $error]);
            }

            return redirect('schsettings/backendtheme')->withErrors($validator)->withInput();
        }

        $this->themes->save([
            'id' => $request->input('sch_id'),
            'theme_color' => $request->input('theme_color'),
            'theme_shadow' => $request->input('theme_shadow'),
            'theme_background' => $request->input('theme_background'),
            'theme_content' => $request->input('theme_content'),
            'theme_type' => $request->input('theme_type'),
            'theme_navigation' => $request->input('theme_navigation'),
            'theme_font_color' => $request->input('theme_font_color'),
        ]);

        $admin = session('admin', []);
        $admin['theme'] = [
            'theme_color' => $request->input('theme_color'),
            'theme_shadow' => $request->input('theme_shadow'),
            'theme_background' => $request->input('theme_background'),
            'theme_content' => $request->input('theme_content'),
            'theme_type' => $request->input('theme_type'),
            'theme_navigation' => $request->input('theme_navigation'),
            'theme_font_color' => $request->input('theme_font_color'),
        ];
        session(['admin' => $admin]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/backendtheme')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
