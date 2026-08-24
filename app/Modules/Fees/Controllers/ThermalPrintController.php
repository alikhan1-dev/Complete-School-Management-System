<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\ThermalPrintService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI System Setting > Thermal Print (addon thermalprint-config).
 */
class ThermalPrintController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ThermalPrintService $thermal,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);
        abort_unless($this->thermal->settingsTableReady(), 404);

        return view('shared::layouts.admin', [
            'title' => (string) __('system.thermal_print'),
            'contentView' => 'fees::admin.thermal_print.index',
            'settings' => $this->thermal->settings() ?? [
                'school_name' => '',
                'address' => '',
                'footer_text' => '',
                'is_print' => 0,
            ],
            'canEdit' => $this->permissions->hasPrivilege('collect_fees', 'can_edit'),
            'moduleActive' => $this->thermal->hasActiveModule(),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_edit'), 403);
        abort_unless($this->thermal->settingsTableReady(), 404);

        $data = $request->validate([
            'school_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'footer_text' => ['nullable', 'string'],
            'is_print' => ['nullable'],
        ]);

        $this->thermal->save($data);

        return redirect()
            ->route('fees.thermal_print.index')
            ->with('success', (string) __('system.record_updated_successfully'));
    }
}
