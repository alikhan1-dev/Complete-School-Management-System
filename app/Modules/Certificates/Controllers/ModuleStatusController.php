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
            'message' => 'Student certificates + ID card templates/generate done. Deferred: staff ID, TC, mPDF, QR matrix.',
            'slices' => [
                'student_certificate_templates' => 'done',
                'generate_student_certificate' => 'done',
                'student_id_card_templates' => 'done',
                'generate_student_id_card' => 'done',
                'staff_id_card' => 'deferred',
                'transfer_certificate' => 'deferred',
                'mpdf_email' => 'deferred',
            ],
        ]);
    }
}
