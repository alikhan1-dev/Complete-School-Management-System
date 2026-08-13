<?php

namespace App\Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Library\Services\LibraryMemberService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/member — members list + student/staff enroll + surrender.
 * Deferred: issue/return.
 */
class MemberController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected LibraryMemberService $members,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('issue_return', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Library Members',
            'contentView' => 'library::admin.members.index',
            'members' => $this->members->listMembers(),
            'canAddStudent' => $this->permissions->hasPrivilege('add_student', 'can_view'),
            'canAddStaff' => $this->permissions->hasPrivilege('add_staff_member', 'can_view'),
        ]);
    }

    public function students(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('add_student', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
        ];
        $rows = collect();

        if ($request->filled('class_id') || $request->filled('search')) {
            $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            ]);
            $rows = $this->members->searchStudents(
                (int) $filters['class_id'],
                $request->filled('section_id') ? (int) $filters['section_id'] : null
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Add Student Member',
            'contentView' => 'library::admin.members.students',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('add_student', 'can_view'), 403);

        $data = $request->validate([
            'member_id' => ['required', 'integer', 'exists:students,id'],
            'library_card_no' => ['required', 'string', 'max:50'],
            'class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
        ]);

        $this->members->enrollStudent((int) $data['member_id'], (string) $data['library_card_no']);

        return redirect()
            ->route('library.members.students', array_filter([
                'search' => 1,
                'class_id' => $data['class_id'] ?? null,
                'section_id' => $data['section_id'] ?? null,
            ]))
            ->with('success', 'Student enrolled as library member successfully.');
    }

    public function teachers(): View
    {
        abort_unless($this->permissions->hasPrivilege('add_staff_member', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Add Staff Member',
            'contentView' => 'library::admin.members.teachers',
            'rows' => $this->members->listStaffCandidates(),
        ]);
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('add_staff_member', 'can_view'), 403);

        $data = $request->validate([
            'member_id' => ['required', 'integer', 'exists:staff,id'],
            'library_card_no' => ['required', 'string', 'max:50'],
        ]);

        $this->members->enrollStaff((int) $data['member_id'], (string) $data['library_card_no']);

        return redirect()
            ->route('library.members.teachers')
            ->with('success', 'Staff enrolled as library member successfully.');
    }

    public function surrender(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('issue_return', 'can_view'), 403);

        $this->members->surrender($id);

        return redirect()
            ->route('library.members.index')
            ->with('success', 'Library membership surrendered successfully.');
    }
}
