<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Certificates\Services\DownloadTransferCertificateService;
use App\Modules\Certificates\Services\PrepareTransferCertificateService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Transfercertificate prepare_tc + edit_custom_field / save_custom_fields.
 * Deferred: mPDF.
 */
class PrepareTransferCertificateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DownloadTransferCertificateService $download,
        protected PrepareTransferCertificateService $prepare
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('prepare_tc', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
        ];

        $students = null;
        $shouldSearch = $request->isMethod('post') || $request->filled('class_id');

        if ($shouldSearch) {
            $data = $request->validate([
                'class_id' => ['required', 'integer'],
                'section_id' => ['nullable', 'integer'],
            ]);

            $filters['class_id'] = $data['class_id'];
            $filters['section_id'] = $data['section_id'] ?? null;

            $sectionId = ! empty($data['section_id']) ? (int) $data['section_id'] : null;
            $students = $this->download->searchStudents((int) $data['class_id'], $sectionId);
        }

        return view('shared::layouts.admin', [
            'title' => 'Prepare Transfer Certificate',
            'contentView' => 'certificates::admin.transfercertificate.prepare',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'filters' => $filters,
            'students' => $students,
            'canViewSettings' => $this->permissions->hasPrivilege('tc_settings', 'can_view'),
            'canDownload' => $this->permissions->hasPrivilege('download_tc', 'can_view'),
            'canVerify' => $this->permissions->hasPrivilege('verify_tc', 'can_view'),
        ]);
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('prepare_tc', 'can_view'), 403);

        $student = $this->prepare->studentProfile($id);
        $form = $this->prepare->customFieldFormData($id);

        return view('shared::layouts.admin', [
            'title' => 'Fill Other Details',
            'contentView' => 'certificates::admin.transfercertificate.edit_custom_field',
            'student' => $student,
            'studentName' => $this->prepare->studentDisplayName($student),
            'customFields' => $form['fields'],
            'customFieldValues' => $form['values'],
            'canEdit' => $this->permissions->hasPrivilege('prepare_tc', 'can_edit')
                || $this->permissions->hasPrivilege('prepare_tc', 'can_add')
                || $this->permissions->hasPrivilege('prepare_tc', 'can_view'),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('prepare_tc', 'can_edit')
            || $this->permissions->hasPrivilege('prepare_tc', 'can_add')
            || $this->permissions->hasPrivilege('prepare_tc', 'can_view'),
            403
        );

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.transfer_certificate' => ['nullable', 'array'],
        ]);

        $studentId = (int) $data['student_id'];
        $this->prepare->studentProfile($studentId);

        $posted = (array) data_get($data, 'custom_fields.transfer_certificate', []);
        $errors = $this->prepare->validateCustomFields($posted);
        if ($errors !== []) {
            return redirect()
                ->route('certificates.tc_prepare.edit', $studentId)
                ->withInput()
                ->withErrors($errors);
        }

        $this->prepare->saveCustomFields($studentId, $posted);

        return redirect()
            ->route('certificates.tc_prepare.edit', $studentId)
            ->with('success', 'Transfer certificate details saved successfully.');
    }
}
