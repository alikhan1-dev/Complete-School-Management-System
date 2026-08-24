<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Staff',
            'status' => 'in_progress',
            'message' => 'Staff list/DataTables + create + edit + profile (incl. payroll + photo + leave summary + rating) + attendance AJAX + documents (incl. create/edit upload) + timeline + disable/enable + delete + import + SaaS quota hooks + credential notification hooks + teachers rating admin list done. Deferred: live credential mail/SMS gateways.',
            'slices' => [
                'list_datatable' => 'done',
                'create' => 'done',
                'edit' => 'done',
                'profile' => 'done',
                'profile_payroll' => 'done',
                'profile_leave_summary' => 'done',
                'profile_rating' => 'done',
                'rating_admin_list' => 'done',
                'saas_quota_hooks' => 'done',
                'credential_notification_hooks' => 'done',
                'photo_upload_create_edit' => 'done',
                'profile_attendance_ajax' => 'done',
                'disable_enable' => 'done',
                'documents' => 'done',
                'document_upload_create_edit' => 'done',
                'timeline' => 'done',
                'delete' => 'done',
                'import' => 'done',
            ],
        ]);
    }
}
