<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Certificates\Services\GenerateCertificateService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Generatecertificate — search class/section & print selected students.
 * Deferred: AJAX JSON print, mPDF, single-student legacy generate().
 */
class GenerateCertificateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected GenerateCertificateService $generate
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('generate_certificate', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'certificate_id' => $request->input('certificate_id'),
        ];

        $students = null;
        $selectedCertificate = null;

        $shouldSearch = $request->isMethod('post')
            || ($request->filled('class_id') && $request->filled('certificate_id'));

        if ($shouldSearch) {
            $data = $request->validate([
                'class_id' => ['required', 'integer'],
                'section_id' => ['nullable', 'integer'],
                'certificate_id' => ['required', 'integer'],
            ]);

            $filters['class_id'] = $data['class_id'];
            $filters['section_id'] = $data['section_id'] ?? null;
            $filters['certificate_id'] = $data['certificate_id'];

            $selectedCertificate = $this->generate->findCertificate((int) $data['certificate_id']);
            $sectionId = ! empty($data['section_id']) ? (int) $data['section_id'] : null;
            $students = $this->generate->searchStudents((int) $data['class_id'], $sectionId);
        }

        return view('shared::layouts.admin', [
            'title' => 'Generate Certificate',
            'contentView' => 'certificates::admin.certificate.generate',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'certificates' => $this->generate->listCertificates(),
            'filters' => $filters,
            'students' => $students,
            'selectedCertificate' => $selectedCertificate,
        ]);
    }

    public function print(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('generate_certificate', 'can_view'), 403);

        $data = $request->validate([
            'certificate_id' => ['required', 'integer'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
        ]);

        $certificate = $this->generate->findCertificate((int) $data['certificate_id']);
        $payload = $this->generate->buildPrintPayload($certificate, $data['student_ids']);

        abort_if($payload['rows'] === [], 422, 'No students selected for certificate print.');

        return view('certificates::admin.certificate.print', $payload);
    }
}
