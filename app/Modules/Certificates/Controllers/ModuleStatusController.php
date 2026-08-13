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
            'message' => 'Certificates module complete for Phase 5 admin: templates, ID cards, full TC (settings/download/verify/prepare) with mPDF. Deferred: email of certificates.',
            'slices' => [
                'student_certificate_templates' => 'done',
                'generate_student_certificate' => 'done',
                'student_id_card_templates' => 'done',
                'generate_student_id_card' => 'done',
                'staff_id_card_templates' => 'done',
                'generate_staff_id_card' => 'done',
                'transfer_certificate_settings' => 'done',
                'transfer_certificate_download' => 'done',
                'transfer_certificate_verify' => 'done',
                'transfer_certificate_prepare' => 'done',
                'transfer_certificate_mpdf' => 'done',
                'certificate_email' => 'deferred',
            ],
        ]);
    }
}
