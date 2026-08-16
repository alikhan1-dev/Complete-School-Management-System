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
 * CI Schsettings::login_page_background + add_admin_login_background.
 */
class SchoolLoginBackgroundController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolLogoService $logos,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->logos->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.login_page_background'),
            'contentView' => 'settings::admin.login_background.index',
            'pageTitle' => __('system.login_page_background'),
            'result' => $setting,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
            'logos' => $this->logos,
        ]);
    }

    /**
     * CI Schsettings::add_admin_login_background — JSON because CI JS posts FormData here.
     */
    public function addAdminLoginBackground(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return $this->failResponse($request, [
                'file' => '<p>'.__('system.id').' is required.</p>',
            ]);
        }

        $file = $request->file('file');
        $result = $this->logos->uploadLoginBackground(
            $id,
            (string) $request->input('logo_type', ''),
            $file instanceof UploadedFile ? $file : null
        );

        if (! ($result['ok'] ?? false)) {
            // CI fail shape only exposes file (no validate_storage on this endpoint).
            $error = ['file' => $result['error']['file'] ?? '<p>Upload failed.</p>'];

            return $this->failResponse($request, $error, $result['message'] ?? null);
        }

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'success' => true,
                'error' => '',
                'message' => $result['message'] ?? __('system.success_message'),
            ]);
        }

        return redirect('schsettings/login_page_background')
            ->with('success', $result['message'] ?? __('system.success_message'));
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

        return redirect('schsettings/login_page_background')
            ->withErrors(['file' => strip_tags((string) $first)])
            ->withInput();
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
