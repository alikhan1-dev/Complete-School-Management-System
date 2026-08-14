<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Services\CaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CI admin/Captcha — superadmin captcha page toggles.
 */
class CaptchaSettingController extends Controller
{
    public function __construct(
        protected CaptchaService $captcha,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->isSuperAdmin(), 403);

        return view('shared::layouts.admin', [
            'title' => 'Captcha Setting',
            'contentView' => 'settings::admin.captcha.index',
            'pageTitle' => 'Captcha Setting',
            'insertedFields' => $this->captcha->listSettings(),
        ]);
    }

    public function changeStatus(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->isSuperAdmin(), 403);
        $name = trim((string) $request->input('name', ''));
        abort_unless($name !== '', 404);
        $this->captcha->updateStatus($name, (int) $request->input('status'));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['msg' => 'Record updated successfully.']);
        }

        return redirect('admin/captcha')->with('success', 'Record updated successfully.');
    }

    protected function isSuperAdmin(): bool
    {
        $staff = Auth::guard('staff')->user();

        return $staff !== null && method_exists($staff, 'isSuperAdmin') && $staff->isSuperAdmin();
    }
}
