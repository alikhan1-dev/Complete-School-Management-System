<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Services\AlumniService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Alumni::alumnilist + add + deletestudent.
 * Deferred: mail/SMS on alumni details, SaaS quota.
 */
class AlumniController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected AlumniService $alumni,
    ) {
    }

    public function alumnilist(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('manage_alumni', 'can_view'), 403);

        $filters = [
            'session_id' => $request->input('session_id', ''),
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'search_text' => $request->input('search_text', ''),
            'search' => $request->input('search', ''),
        ];
        $resultlist = null;
        $errors = [];

        if ($request->isMethod('post')) {
            if ($filters['search'] === 'search_filter') {
                if ($filters['session_id'] === '' || $filters['session_id'] === null) {
                    $errors['session_id'] = 'The '.__('system.session').' field is required.';
                }
                if ($filters['class_id'] === '' || $filters['class_id'] === null) {
                    $errors['class_id'] = 'The '.__('system.class').' field is required.';
                }
                if ($errors === []) {
                    $resultlist = $this->alumni->searchByFilter(
                        (int) $filters['session_id'],
                        (int) $filters['class_id'],
                        $filters['section_id'] !== '' ? (int) $filters['section_id'] : null
                    );
                }
            } elseif ($filters['search'] === 'search_full') {
                $resultlist = $this->alumni->searchByAdmissionNo((string) $filters['search_text']);
            }
        }

        return view('shared::layouts.admin', [
            'title' => __('system.alumni_student'),
            'contentView' => 'students::admin.alumni.list',
            'filters' => $filters,
            'errors' => $errors,
            'resultlist' => $resultlist,
            'alumniMap' => $this->alumni->alumniDetailsByStudentId(),
            'sessionlist' => AcademicSession::query()->orderByDesc('id')->get(),
            'classlist' => SchoolClass::query()->orderBy('class')->get(),
            'sectionOptions' => $this->sectionOptions((int) ($filters['class_id'] ?: 0)),
            'alumni' => $this->alumni,
            'canAdd' => $this->permissions->hasPrivilege('manage_alumni', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('manage_alumni', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('manage_alumni', 'can_delete'),
        ]);
    }

    public function form(Request $request, int $studentId): View|RedirectResponse
    {
        $existing = $this->alumni->findByStudentId($studentId);
        if ($existing) {
            abort_unless($this->permissions->hasPrivilege('manage_alumni', 'can_edit'), 403);
        } else {
            abort_unless($this->permissions->hasPrivilege('manage_alumni', 'can_add'), 403);
        }

        $student = Student::query()->findOrFail($studentId);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'current_phone' => ['required', 'string', 'max:255'],
                'current_email' => ['nullable', 'string', 'max:255'],
                'occupation' => ['nullable', 'string'],
                'address' => ['nullable', 'string'],
                'documents' => ['nullable', 'image', 'max:5120'],
            ], [
                'current_phone.required' => 'The '.__('system.current_phone').' field is required.',
            ]);

            $this->alumni->save(
                $studentId,
                $data,
                $request->file('documents')
            );

            return redirect()
                ->route('students.alumni.list')
                ->with('success', __('system.success_message'));
        }

        return view('shared::layouts.admin', [
            'title' => $existing ? __('system.edit') : __('system.add'),
            'contentView' => 'students::admin.alumni.form',
            'student' => $student,
            'existing' => $existing,
            'alumni' => $this->alumni,
        ]);
    }

    public function deletestudent(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('manage_alumni', 'can_delete'), 403);
        $this->alumni->deleteByStudentId($id);

        return redirect()
            ->route('students.alumni.list')
            ->with('success', __('system.delete_message'));
    }

    /**
     * @return list<object>
     */
    protected function sectionOptions(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('class_sections')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('class_sections.class_id', $classId)
            ->orderBy('sections.section')
            ->select(['sections.id as section_id', 'sections.section'])
            ->get()
            ->all();
    }
}
