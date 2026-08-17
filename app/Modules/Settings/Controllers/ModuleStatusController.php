<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder for Phase 2–8 migration of the Settings module.
 * Feature code will be ported from smart_7.2 with parity testing.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Settings',
            'status' => 'in_progress',
            'message' => 'Captcha + general setting + logo + login page background + backend theme + mobile app URL/colors + student/guardian panel + fees flags + ID auto-generation + attendance type (core) + staff/student auto-attendance schedules + class times + maintenance + WhatsApp + chat delete flags + Google Drive picker + miscellaneous + module toggles + currency persist done. Deferred: Envato andapp register, SaaS quota.',
            'slices' => [
                'captcha' => 'done',
                'general_setting' => 'done',
                'logos' => 'done',
                'login_page_background' => 'done',
                'backend_theme' => 'done',
                'mobile_app' => 'done',
                'student_guardian_panel' => 'done',
                'fees' => 'done',
                'id_auto_generation' => 'done',
                'attendance_type' => 'done',
                'attendance_schedules' => 'done',
                'maintenance' => 'done',
                'whatsapp' => 'done',
                'chat_setting' => 'done',
                'google_drive' => 'done',
                'miscellaneous' => 'done',
                'modules' => 'done',
                'currency' => 'done',
            ],
        ]);
    }
}