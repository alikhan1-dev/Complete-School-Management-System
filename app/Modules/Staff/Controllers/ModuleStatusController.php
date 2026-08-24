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
            'message' => 'Staff list/DataTables + staff create persist done. Deferred: edit/delete/profile, documents, timeline, import, SaaS quota, credential mail/SMS.',
            'slices' => [
                'list_datatable' => 'done',
                'create' => 'done',
                'edit' => 'pending',
                'profile' => 'pending',
                'documents' => 'pending',
            ],
        ]);
    }
}
