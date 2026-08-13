<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Certificates\Services\GenerateStudentIdCardService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Generateidcard — search class/section & print selected student ID cards.
 * Deferred: AJAX JSON print, mPDF, single-student legacy generate().
 */
class GenerateStudentIdCardController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected GenerateStudentIdCardService $generate
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('generate_id_card', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'id_card' => $request->input('id_card'),
        ];

        $students = null;
        $selectedIdCard = null;

        $shouldSearch = $request->isMethod('post')
            || ($request->filled('class_id') && $request->filled('id_card'));

        if ($shouldSearch) {
            $data = $request->validate([
                'class_id' => ['required', 'integer'],
                'section_id' => ['nullable', 'integer'],
                'id_card' => ['required', 'integer'],
            ]);

            $filters['class_id'] = $data['class_id'];
            $filters['section_id'] = $data['section_id'] ?? null;
            $filters['id_card'] = $data['id_card'];

            $selectedIdCard = $this->generate->findTemplate((int) $data['id_card']);
            $sectionId = ! empty($data['section_id']) ? (int) $data['section_id'] : null;
            $students = $this->generate->searchStudents((int) $data['class_id'], $sectionId);
        }

        return view('shared::layouts.admin', [
            'title' => 'Generate Student ID Card',
            'contentView' => 'certificates::admin.idcard.generate',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'idcards' => $this->generate->listTemplates(),
            'filters' => $filters,
            'students' => $students,
            'selectedIdCard' => $selectedIdCard,
        ]);
    }

    public function print(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('generate_id_card', 'can_view'), 403);

        $data = $request->validate([
            'id_card' => ['required', 'integer'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
        ]);

        $idcard = $this->generate->findTemplate((int) $data['id_card']);
        $payload = $this->generate->buildPrintPayload($idcard, $data['student_ids']);

        abort_if($payload['rows'] === [], 422, 'No students selected for ID card print.');

        return view('certificates::admin.idcard.print', $payload);
    }
}
