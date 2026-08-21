<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Students',
            'status' => 'in_progress',
            'message' => 'Admission/profile/docs/timeline/disable flow + disable reason master done. Deferred: alumni module, online admission fees-on-admit.',
        ]);
    }
}
