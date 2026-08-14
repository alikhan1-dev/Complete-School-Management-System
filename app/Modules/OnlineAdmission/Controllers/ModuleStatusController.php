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
            'message' => 'Admin settings + application list/edit/enroll/delete persist done. Deferred: public Welcome admission/examresult, payment gateways, fees/transport on enroll, live mail/SMS, barcode, SaaS quota, CI DataTables JSON.',
            'slices' => [
                'settings' => 'done',
                'form_fields' => 'done',
                'applications' => 'done',
                'enroll' => 'done',
                'public_form' => 'deferred',
                'payments' => 'deferred',
            ],
        ]);
    }
}
