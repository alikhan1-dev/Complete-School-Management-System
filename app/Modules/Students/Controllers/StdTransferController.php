<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Students\Services\StudentPromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StdTransferController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentPromotionService $promotion
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('promote_student', 'can_view'), 403);

        $classes = SchoolClass::query()->orderBy('id')->get();
        $sessions = AcademicSession::query()->orderBy('id')->get();
        $resultlist = null;
        $posted = [
            'class_post' => null,
            'section_post' => null,
            'class_promoted_post' => null,
            'section_promoted_post' => null,
            'session_promoted_post' => null,
        ];

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
                'session_id' => ['required', 'integer', 'exists:sessions,id'],
                'class_promote_id' => ['required', 'integer', 'exists:classes,id'],
                'section_promote_id' => ['required', 'integer', 'exists:sections,id'],
            ]);

            $resultlist = $this->promotion->searchNonPromoted(
                (int) $validated['class_id'],
                (int) $validated['section_id'],
                (int) $validated['session_id'],
                (int) $validated['class_promote_id'],
                (int) $validated['section_promote_id']
            );

            $posted = [
                'class_post' => (int) $validated['class_id'],
                'section_post' => (int) $validated['section_id'],
                'class_promoted_post' => (int) $validated['class_promote_id'],
                'section_promoted_post' => (int) $validated['section_promote_id'],
                'session_promoted_post' => (int) $validated['session_id'],
            ];
        }

        return view('shared::layouts.admin', [
            'title' => 'Promote Students',
            'contentView' => 'students::admin.stdtransfer.index',
            'classes' => $classes,
            'sessions' => $sessions,
            'resultlist' => $resultlist,
            'schSetting' => SchSetting::query()->first(),
            ...$posted,
            'oldInput' => $request->all(),
        ]);
    }

    public function promote(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('promote_student', 'can_view'), 403);

        $validator = validator($request->all(), [
            'session_id' => ['required', 'integer', 'exists:sessions,id'],
            'class_promote_id' => ['required', 'integer', 'exists:classes,id'],
            'section_promote_id' => ['required', 'integer', 'exists:sections,id'],
            'student_list' => ['required', 'array', 'min:1'],
            'student_list.*' => ['integer', 'exists:students,id'],
            'class_post' => ['required', 'integer', 'exists:classes,id'],
            'section_post' => ['required', 'integer', 'exists:sections,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'msg' => [
                    'session_id' => $validator->errors()->first('session_id'),
                    'class_promote_id' => $validator->errors()->first('class_promote_id'),
                    'section_promote_id' => $validator->errors()->first('section_promote_id'),
                    'student_list' => $validator->errors()->first('student_list'),
                ],
            ]);
        }

        $studentIds = $request->input('student_list', []);
        $results = [];
        $nextStatuses = [];
        foreach ($studentIds as $id) {
            $results[(int) $id] = (string) $request->input('result_'.$id, 'pass');
            // Preserve CI spelling "countinue"
            $nextStatuses[(int) $id] = (string) $request->input('next_working_'.$id, 'countinue');
        }

        $this->promotion->promote(
            $studentIds,
            $results,
            $nextStatuses,
            (int) $request->input('session_id'),
            (int) $request->input('class_promote_id'),
            (int) $request->input('section_promote_id'),
            (int) $request->input('class_post'),
            (int) $request->input('section_post')
        );

        return response()->json(['status' => 'success', 'msg' => '']);
    }
}
