<?php

namespace App\Modules\Hostel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hostel\Services\StudentHostelReportService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/hostelroom/studenthosteldetails — student hostel report.
 */
class StudentHostelReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentHostelReportService $reports,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('hostel_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = collect();

        if ($this->shouldSearch($request)) {
            $request->validate([
                'class_id' => ['required', 'integer'],
                'section_id' => ['required', 'integer'],
                'hostel_name' => ['nullable', 'integer'],
            ]);
            $rows = $this->reports->search($filters);
        }

        return view('shared::layouts.admin', [
            'title' => 'Student Hostel Report',
            'contentView' => 'hostel::admin.reports.student_hostel',
            'classes' => $this->reports->classes(),
            'hostels' => $this->reports->listHostels(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $this->shouldSearch($request),
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }

    /**
     * @return array{class_id:mixed,section_id:mixed,hostel_name:mixed,search:mixed}
     */
    protected function filterInput(Request $request): array
    {
        return [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'hostel_name' => $request->input('hostel_name'),
            'search' => $request->input('search'),
        ];
    }

    protected function shouldSearch(Request $request): bool
    {
        return $request->filled('search') || ($request->isMethod('post') && $request->has('search'));
    }
}
