<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Certificates\Services\DownloadTransferCertificateService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Transfercertificate::verify_tc — look up issued TC by number.
 * Deferred: prepare TC, custom fields, mPDF re-download from verify page.
 */
class VerifyTransferCertificateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DownloadTransferCertificateService $download
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('verify_tc', 'can_view'), 403);

        $tcNo = $request->input('student_tc_no');
        $searched = false;
        $preview = null;

        if ($request->isMethod('post') || $request->filled('student_tc_no')) {
            $data = $request->validate([
                'student_tc_no' => ['required', 'string', 'max:50'],
            ]);

            $searched = true;
            $tcNo = $data['student_tc_no'];
            $preview = $this->download->buildVerifyPayload($tcNo);
        }

        return view('shared::layouts.admin', [
            'title' => 'Verify Transfer Certificate',
            'contentView' => 'certificates::admin.transfercertificate.verify',
            'studentTcNo' => $tcNo,
            'searched' => $searched,
            'preview' => $preview,
            'canDownload' => $this->permissions->hasPrivilege('download_tc', 'can_view'),
            'canViewSettings' => $this->permissions->hasPrivilege('tc_settings', 'can_view'),
            'canPrepare' => $this->permissions->hasPrivilege('prepare_tc', 'can_view'),
        ]);
    }
}
