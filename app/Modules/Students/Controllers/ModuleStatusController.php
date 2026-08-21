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
            'message' => 'Admission/profile/docs/timeline/disable + disable reason + disabled students list + alumni list/details + alumni events done. Deferred: alumni mail/SMS + calendar JS; disabled list class-teacher scope + details cards.',
        ]);
    }
}
