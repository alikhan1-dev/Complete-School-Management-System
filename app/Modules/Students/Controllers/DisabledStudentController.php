<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Students\Services\DisabledStudentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Student::disablestudentslist — search inactive students.
 */
class DisabledStudentController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DisabledStudentService $disabled,
        protected ClassTeacherScopeService $classTeacherScope,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('disable_student', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'search_text' => $request->input('search_text', ''),
            'search' => $request->input('search', ''),
        ];
        $resultlist = null;
        $errors = [];

        if ($request->isMethod('post')) {
            if ($filters['search'] === 'search_filter') {
                if ($filters['class_id'] === '' || $filters['class_id'] === null) {
                    $errors['class_id'] = 'The '.__('system.class').' field is required.';
                }
                if ($errors === []) {
                    $resultlist = $this->disabled->searchByClassSection(
                        (int) $filters['class_id'],
                        $filters['section_id'] !== '' ? (int) $filters['section_id'] : null
                    );
                }
            } elseif ($filters['search'] === 'search_full') {
                $resultlist = $this->disabled->searchFullText((string) $filters['search_text']);
            }
        }

        return view('shared::layouts.admin', [
            'title' => __('system.disabled_students'),
            'contentView' => 'students::admin.disabled.list',
            'filters' => $filters,
            'errors' => $errors,
            'resultlist' => $resultlist,
            'reasonMap' => $this->disabled->reasonMap(),
            'classlist' => $this->classTeacherScope->classesForDropdown(),
            'sectionOptions' => $this->classTeacherScope->sectionsForClass((int) ($filters['class_id'] ?: 0)),
            'disabled' => $this->disabled,
            'tableCustomFields' => $this->disabled->tableCustomFields(),
            'dateFormat' => (string) $this->school->dateFormat(),
        ]);
    }
}
