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
            'message' => 'Admin persist + public form/review/status/submit/edit + checkout + applicant file persist done. Deferred: live gateway APIs, captcha, custom fields, examresult, live mail/SMS, SaaS quota.',
            'slices' => [
                'settings' => 'done',
                'form_fields' => 'done',
                'applications' => 'done',
                'enroll' => 'done',
                'public_form' => 'done',
                'public_edit' => 'done',
                'checkout' => 'done',
                'public_files' => 'done',
                'live_gateways' => 'deferred',
            ],
        ]);
    }
}
