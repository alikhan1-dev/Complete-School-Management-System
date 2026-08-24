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
            'message' => 'Staff list/DataTables + create + edit + profile (core + attendance AJAX) + disable/enable done. Deferred: timeline, payroll, documents, import, SaaS quota, credential mail/SMS.',
            'slices' => [
                'list_datatable' => 'done',
                'create' => 'done',
                'edit' => 'done',
                'profile' => 'done',
                'profile_attendance_ajax' => 'done',
                'disable_enable' => 'done',
                'documents' => 'pending',
            ],
        ]);
    }
}
