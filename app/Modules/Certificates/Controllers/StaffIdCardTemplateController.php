<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Certificates\Services\StaffIdCardTemplateService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Staffidcard — staff ID card template design CRUD.
 * Deferred: generate/print, SaaS quota.
 */
class StaffIdCardTemplateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StaffIdCardTemplateService $templates
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_id_card', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Staff ID Card',
            'contentView' => 'certificates::admin.staffidcard.index',
            'idcards' => $this->templates->list(),
            'editing' => null,
            'canAdd' => $this->permissions->hasPrivilege('staff_id_card', 'can_add'),
            'assetUrls' => [
                'backgroundUrl' => null,
                'logoUrl' => null,
                'signUrl' => null,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_id_card', 'can_add'), 403);

        $this->validateTemplate($request);
        $this->templates->create($this->templates->buildPayload($request));

        return redirect()->route('certificates.staffidcard_templates.index')
            ->with('success', 'Staff ID card template saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_id_card', 'can_edit'), 403);

        $editing = $this->templates->find($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Staff ID Card',
            'contentView' => 'certificates::admin.staffidcard.index',
            'idcards' => $this->templates->list(),
            'editing' => $editing,
            'canAdd' => false,
            'assetUrls' => $this->templates->assetUrls($editing),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_id_card', 'can_edit'), 403);

        $row = $this->templates->find($id);
        $this->validateTemplate($request);
        $this->templates->update($row, $this->templates->buildPayload($request, $row));

        return redirect()->route('certificates.staffidcard_templates.index')
            ->with('success', 'Staff ID card template updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_id_card', 'can_delete'), 403);

        $this->templates->delete($this->templates->find($id));

        return redirect()->route('certificates.staffidcard_templates.index')
            ->with('success', 'Staff ID card template deleted successfully.');
    }

    public function preview(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_id_card', 'can_view'), 403);

        $idcard = $this->templates->find($id);
        $scanCodeType = (string) (SchSetting::query()->value('scan_code_type') ?? 'barcode');

        return view('certificates::admin.staffidcard.preview', array_merge(
            ['idcard' => $idcard, 'scanCodeType' => $scanCodeType],
            $this->templates->assetUrls($idcard)
        ));
    }

    protected function validateTemplate(Request $request): void
    {
        $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'header_color' => ['nullable', 'string', 'max:100'],
            'background_image' => ['nullable', 'image', 'max:4096'],
            'logo_img' => ['nullable', 'image', 'max:4096'],
            'sign_image' => ['nullable', 'image', 'max:4096'],
            'removebackground_image' => ['nullable'],
            'removelogo_image' => ['nullable'],
            'removesign_image' => ['nullable'],
        ]);
    }
}
