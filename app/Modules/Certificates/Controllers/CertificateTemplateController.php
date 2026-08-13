<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Certificates\Services\CertificateDocumentService;
use App\Modules\Certificates\Services\CertificateTemplateService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Certificate — student certificate template design CRUD.
 * Deferred: ID cards, staff ID, TC.
 */
class CertificateTemplateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected CertificateTemplateService $templates,
        protected CertificateDocumentService $documents
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('student_certificate', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Student Certificate',
            'contentView' => 'certificates::admin.certificate.index',
            'certificates' => $this->templates->listStudentCertificates(),
            'editing' => null,
            'placeholders' => $this->templates->placeholderHints(),
            'canAdd' => $this->permissions->hasPrivilege('student_certificate', 'can_add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_certificate', 'can_add'), 403);

        $this->validateTemplate($request);
        $this->templates->create($this->templates->buildPayload($request));

        return redirect()->route('certificates.templates.index')->with('success', 'Certificate template saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('student_certificate', 'can_edit'), 403);

        $editing = $this->templates->findStudentCertificate($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Student Certificate',
            'contentView' => 'certificates::admin.certificate.index',
            'certificates' => $this->templates->listStudentCertificates(),
            'editing' => $editing,
            'backgroundUrl' => $this->documents->url($editing->background_image),
            'placeholders' => $this->templates->placeholderHints(),
            'canAdd' => false,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_certificate', 'can_edit'), 403);

        $row = $this->templates->findStudentCertificate($id);
        $this->validateTemplate($request);
        $this->templates->update($row, $this->templates->buildPayload($request, $row));

        return redirect()->route('certificates.templates.index')->with('success', 'Certificate template updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_certificate', 'can_delete'), 403);

        $this->templates->delete($this->templates->findStudentCertificate($id));

        return redirect()->route('certificates.templates.index')->with('success', 'Certificate template deleted successfully.');
    }

    public function preview(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('student_certificate', 'can_view'), 403);

        $certificate = $this->templates->findStudentCertificate($id);

        return view('certificates::admin.certificate.preview', [
            'certificate' => $certificate,
            'backgroundUrl' => $this->documents->url($certificate->background_image),
        ]);
    }

    protected function validateTemplate(Request $request): void
    {
        $request->validate([
            'certificate_name' => ['required', 'string', 'max:100'],
            'certificate_text' => ['required', 'string'],
            'left_header' => ['nullable', 'string', 'max:100'],
            'center_header' => ['nullable', 'string', 'max:100'],
            'right_header' => ['nullable', 'string', 'max:100'],
            'left_footer' => ['nullable', 'string', 'max:100'],
            'center_footer' => ['nullable', 'string', 'max:100'],
            'right_footer' => ['nullable', 'string', 'max:100'],
            'header_height' => ['nullable', 'integer', 'min:0'],
            'content_height' => ['nullable', 'integer', 'min:0'],
            'footer_height' => ['nullable', 'integer', 'min:0'],
            'content_width' => ['nullable', 'integer', 'min:0'],
            'image_height' => ['nullable', 'integer', 'min:0'],
            'background_image' => ['nullable', 'image', 'max:4096'],
            'is_active_student_img' => ['nullable'],
            'removebackground_image' => ['nullable'],
        ]);
    }
}
