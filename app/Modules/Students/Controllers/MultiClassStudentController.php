<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use App\Modules\Students\Services\MultiClassStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Student::multiclass + savemulticlass.
 */
class MultiClassStudentController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected MultiClassStudentService $multiClass,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('multi_class_student', 'can_view'), 403);

        $students = [];
        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);
        $classes = $this->classTeacherScope->classesForDropdown();

        if ($request->isMethod('post')) {
            $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
            ]);
            $classId = (int) $request->input('class_id');
            $sectionId = (int) $request->input('section_id');
            $students = $this->multiClass->searchByClassSection($classId, $sectionId);
        }

        return view('shared::layouts.admin', [
            'title' => __('system.multi_class_student'),
            'contentView' => 'students::admin.multiclass.index',
            'classes' => $classes,
            'schSetting' => SchSetting::query()->first(),
            'students' => $students,
            'selectedClassId' => $classId,
            'selectedSectionId' => $sectionId,
            'canAdd' => $this->permissions->hasPrivilege('multi_class_student', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('multi_class_student', 'can_edit'),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('multi_class_student', 'can_add')
            || $this->permissions->hasPrivilege('multi_class_student', 'can_edit'),
            403
        );

        $studentId = (int) $request->input('student_id', 0);
        $rowCounts = (array) $request->input('row_count', []);

        if ($studentId <= 0) {
            return response()->json([
                'status' => '0',
                'error' => ['student_id' => 'The student id field is required.'],
                'message' => (string) __('system.something_went_wrong'),
            ]);
        }

        if ($rowCounts === []) {
            return response()->json([
                'status' => '0',
                'error' => ['row_count[]' => 'The row count field is required.'],
                'message' => (string) __('system.something_went_wrong'),
            ]);
        }

        $rows = [];
        $errors = [];
        $duplicates = [];
        foreach ($rowCounts as $rowCount) {
            $rowCount = (string) $rowCount;
            $classId = (int) $request->input('class_id_'.$rowCount, 0);
            $sectionId = (int) $request->input('section_id_'.$rowCount, 0);
            if ($classId <= 0) {
                $errors['class_id_'.$rowCount] = 'The '.__('system.class').' field is required.';
            }
            if ($sectionId <= 0) {
                $errors['section_id_'.$rowCount] = 'The '.__('system.section').' field is required.';
            }
            if ($classId > 0 && $sectionId > 0) {
                $key = $classId.'-'.$sectionId;
                $duplicates[$key] = ($duplicates[$key] ?? 0) + 1;
                $rows[] = ['class_id' => $classId, 'section_id' => $sectionId];
            }
        }

        if ($errors !== []) {
            return response()->json([
                'status' => '0',
                'error' => $errors,
                'message' => (string) __('system.something_went_wrong'),
            ]);
        }

        foreach ($duplicates as $count) {
            if ($count > 1) {
                return response()->json([
                    'status' => 0,
                    'error' => '',
                    'message' => (string) __('system.duplicate_entry'),
                ]);
            }
        }

        $ok = $this->multiClass->syncSessions($studentId, $rows);
        if (! $ok) {
            return response()->json([
                'status' => 0,
                'error' => '',
                'message' => (string) __('system.duplicate_entry'),
            ]);
        }

        return response()->json([
            'status' => 1,
            'error' => '',
            'message' => (string) __('system.success_message'),
        ]);
    }
}
