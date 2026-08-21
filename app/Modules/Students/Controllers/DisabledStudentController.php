<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Students\Services\DisabledStudentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CI Student::disablestudentslist — search inactive students.
 */
class DisabledStudentController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DisabledStudentService $disabled,
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
            'classlist' => SchoolClass::query()->orderBy('class')->get(),
            'sectionOptions' => $this->sectionOptions((int) ($filters['class_id'] ?: 0)),
            'disabled' => $this->disabled,
        ]);
    }

    /**
     * @return list<object>
     */
    protected function sectionOptions(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }

        return DB::table('class_sections')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('class_sections.class_id', $classId)
            ->orderBy('sections.section')
            ->select(['sections.id as section_id', 'sections.section'])
            ->get()
            ->all();
    }
}
