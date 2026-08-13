<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Certificates\Services\DownloadTransferCertificateService;
use App\Modules\Certificates\Services\TransferCertificatePdfService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CI admin/Transfercertificate download + print_transfer_certificate (mPDF).
 * HTML print kept as secondary route; email deferred.
 */
class DownloadTransferCertificateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DownloadTransferCertificateService $download,
        protected TransferCertificatePdfService $pdf
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('download_tc', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
        ];

        $students = null;
        $shouldSearch = $request->isMethod('post')
            || $request->filled('class_id');

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
            'title' => 'Download Transfer Certificate',
            'contentView' => 'certificates::admin.transfercertificate.download',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'filters' => $filters,
            'students' => $students,
            'canViewSettings' => $this->permissions->hasPrivilege('tc_settings', 'can_view'),
            'canVerify' => $this->permissions->hasPrivilege('verify_tc', 'can_view'),
            'canPrepare' => $this->permissions->hasPrivilege('prepare_tc', 'can_view'),
        ]);
    }

    /**
     * CI print_transfer_certificate — issues TC no and returns mPDF binary (inline).
     */
    public function print(Request $request): Response
    {
        abort_unless($this->permissions->hasPrivilege('download_tc', 'can_view'), 403);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'student_session_id' => ['required', 'integer'],
            'is_regenerte' => ['nullable'],
        ]);

        $payload = $this->download->buildPrintPayload(
            (int) $data['student_id'],
            (int) $data['student_session_id'],
            $request->boolean('is_regenerte')
        );

        $pdf = $this->pdf->render($payload);

        return response($pdf['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf['filename'].'"',
            'Content-Length' => (string) strlen($pdf['binary']),
        ]);
    }

    /**
     * Browser HTML print (kept as optional fallback alongside mPDF).
     */
    public function printHtml(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('download_tc', 'can_view'), 403);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'student_session_id' => ['required', 'integer'],
            'is_regenerte' => ['nullable'],
        ]);

        $payload = $this->download->buildPrintPayload(
            (int) $data['student_id'],
            (int) $data['student_session_id'],
            $request->boolean('is_regenerte')
        );

        return view('certificates::admin.transfercertificate.print', $payload);
    }
}
