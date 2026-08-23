<?php

namespace App\Modules\Parents\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 2 Parents migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Parents',
            'status' => 'in_progress',
            'message' => 'Parent portal credentials on student profile + getlogindetail + send student/parent password endpoints done. Deferred: live mail/SMS/WhatsApp for student_login_credential; sibling childs append (CI also omits); parent portal feature expansion.',
            'slices' => [
                'admission_parent_user_create' => 'done',
                'sibling_parent_reuse_on_admit' => 'done',
                'profile_credentials_login_detail' => 'done',
                'send_student_parent_password_endpoints' => 'done',
                'live_login_credential_gateways' => 'deferred',
            ],
        ]);
    }
}
