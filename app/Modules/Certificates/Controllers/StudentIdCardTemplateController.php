<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Certificates\Services\StudentIdCardTemplateService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Studentidcard — student ID card template design CRUD.
 * Deferred: generate/print, barcode/QR generation, SaaS quota.
 */
class StudentIdCardTemplateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentIdCardTemplateService $templates
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('student_id_card', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Student ID Card',
            'contentView' => 'certificates::admin.idcard.index',
            'idcards' => $this->templates->list(),
            'editing' => null,
            'canAdd' => $this->permissions->hasPrivilege('student_id_card', 'can_add'),
            'assetUrls' => [
                'backgroundUrl' => null,
                'logoUrl' => null,
                'signUrl' => null,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_id_card', 'can_add'), 403);

        $this->validateTemplate($request);
        $this->templates->create($this->templates->buildPayload($request));

        return redirect()->route('certificates.idcard_templates.index')
            ->with('success', 'Student ID card template saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('student_id_card', 'can_edit'), 403);

        $editing = $this->templates->find($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Student ID Card',
            'contentView' => 'certificates::admin.idcard.index',
            'idcards' => $this->templates->list(),
            'editing' => $editing,
            'canAdd' => false,
            'assetUrls' => $this->templates->assetUrls($editing),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_id_card', 'can_edit'), 403);

        $row = $this->templates->find($id);
        $this->validateTemplate($request);
        $this->templates->update($row, $this->templates->buildPayload($request, $row));

        return redirect()->route('certificates.idcard_templates.index')
            ->with('success', 'Student ID card template updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_id_card', 'can_delete'), 403);

        $this->templates->delete($this->templates->find($id));

        return redirect()->route('certificates.idcard_templates.index')
            ->with('success', 'Student ID card template deleted successfully.');
    }

    public function preview(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('student_id_card', 'can_view'), 403);

        $idcard = $this->templates->find($id);
        $scanCodeType = (string) (SchSetting::query()->value('scan_code_type') ?? 'barcode');

        return view('certificates::admin.idcard.preview', array_merge(
            ['idcard' => $idcard, 'scanCodeType' => $scanCodeType],
            $this->templates->assetUrls($idcard)
        ));
    }

    protected function validateTemplate(Request $request): void
    {
        $request->validate([
            'school_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:100'],
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
