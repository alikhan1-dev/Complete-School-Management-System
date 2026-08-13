<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 5 Certificates migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Certificates',
            'status' => 'in_progress',
            'message' => 'Student + staff ID card/cert templates and generate done. TC settings done; download/verify deferred.',
            'slices' => [
                'student_certificate_templates' => 'done',
                'generate_student_certificate' => 'done',
                'student_id_card_templates' => 'done',
                'generate_student_id_card' => 'done',
                'staff_id_card_templates' => 'done',
                'generate_staff_id_card' => 'done',
                'transfer_certificate_settings' => 'done',
                'transfer_certificate_download' => 'pending',
                'transfer_certificate_verify' => 'deferred',
                'mpdf_email' => 'deferred',
            ],
        ]);
    }
}
