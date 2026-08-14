<?php

namespace App\Modules\OnlineAdmission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Online Admission migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'OnlineAdmission',
            'status' => 'in_progress',
            'message' => 'Admin persist + public form/review/status/submit/edit + checkout + applicant files + custom fields + enroll copy to student + enroll document/photo copy + enroll barcode/qrcode + admission captcha persist done. Deferred: live gateway APIs, live mail/SMS, SaaS quota.',
            'slices' => [
                'settings' => 'done',
                'form_fields' => 'done',
                'applications' => 'done',
                'enroll' => 'done',
                'public_form' => 'done',
                'public_edit' => 'done',
                'checkout' => 'done',
                'public_files' => 'done',
                'custom_fields' => 'done',
                'enroll_custom_fields' => 'done',
                'enroll_files' => 'done',
                'enroll_barcode' => 'done',
                'captcha' => 'done',
                'live_gateways' => 'deferred',
            ],
        ]);
    }
}
