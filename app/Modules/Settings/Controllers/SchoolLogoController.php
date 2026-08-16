<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolLogoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * CI Schsettings::logo + ajax_editlogo / ajax_editadmin_* / ajax_applogo.
 */
class SchoolLogoController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolLogoService $logos,
    ) {
    }

    public function logo(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->logos->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.logo'),
            'contentView' => 'settings::admin.logo.index',
            'pageTitle' => __('system.logo'),
            'result' => $setting,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
            'logos' => $this->logos,
        ]);
    }

    public function ajaxEditLogo(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handleUpload($request, 'image');
    }

    public function ajaxEditAdminLogo(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handleUpload($request, 'admin_logo');
    }

    public function ajaxEditAdminSmallLogo(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handleUpload($request, 'admin_small_logo');
    }

    public function ajaxAppLogo(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handleUpload($request, 'app_logo');
    }

    protected function handleUpload(Request $request, string $type): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return $this->failResponse($request, [
                'file' => '',
                'validate_storage' => '',
                'id' => '<p>'.__('system.id').' is required.</p>',
            ]);
        }

        $file = $request->file('file');
        $result = $this->logos->upload(
            $type,
            $id,
            $file instanceof UploadedFile ? $file : null
        );

        if (! ($result['ok'] ?? false)) {
            return $this->failResponse($request, $result['error'] ?? ['file' => ''], $result['message'] ?? null);
        }

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'success' => true,
                'error' => '',
                'message' => $result['message'] ?? __('system.success_message'),
            ]);
        }

        return redirect('schsettings/logo')->with('success', $result['message'] ?? __('system.success_message'));
    }

    /**
     * @param  array<string, string>  $error
     */
    protected function failResponse(Request $request, array $error, ?string $message = null): JsonResponse|RedirectResponse
    {
        if ($this->wantsCiJson($request)) {
            $payload = [
                'success' => false,
                'error' => $error,
            ];
            if ($message !== null) {
                $payload['message'] = $message;
            }

            return response()->json($payload);
        }

        $first = collect($error)->filter()->first() ?? 'Upload failed.';

        return redirect('schsettings/logo')->withErrors(['file' => strip_tags((string) $first)])->withInput();
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
