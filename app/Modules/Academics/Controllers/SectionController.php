<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Requests\StoreSectionRequest;
use App\Modules\Academics\Requests\UpdateSectionRequest;
use App\Modules\Academics\Services\OrphanStudentCleanup;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OrphanStudentCleanup $orphanCleanup,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('section', 'can_view'), 403);

        $sections = Section::query()->orderBy('id')->get();

        return view('shared::layouts.admin', [
            'title' => 'Sections',
            'contentView' => 'academics::admin.sections.index',
            'sections' => $sections,
        ]);
    }

    public function store(StoreSectionRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('section', 'can_add'), 403);

        Section::query()->create([
            'section' => $request->validated('section'),
            'is_active' => 'yes',
        ]);

        return redirect()->route('academics.sections.index')->with('success', 'Section created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('section', 'can_edit'), 403);

        $section = Section::query()->findOrFail($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Section',
            'contentView' => 'academics::admin.sections.edit',
            'sectionRow' => $section,
        ]);
    }

    public function update(UpdateSectionRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('section', 'can_edit'), 403);

        $section = Section::query()->findOrFail($id);
        $section->section = $request->validated('section');
        $section->save();

        return redirect()->route('academics.sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('section', 'can_delete'), 403);

        DB::transaction(function () use ($id) {
            $section = Section::query()->findOrFail($id);
            $section->delete();
            $this->orphanCleanup->removeStudentsWithoutSession();
        });

        return redirect()->route('academics.sections.index')->with('success', 'Section deleted successfully.');
    }

    /**
     * Exact CI JSON contract:
     * [{ "id": class_sections.id, "section_id": sections.id, "section": name }]
     * Class-teacher restriction mirrors CI Section_model::getClassBySection.
     */
    public function getByClass(Request $request): JsonResponse
    {
        $classId = (int) $request->query('class_id', 0);
        // CI Teacher_model::get_teacherrestricted_modesections honors ?day_wise=
        $dayWise = filled($request->query('day_wise'));

        $rows = collect($this->classTeacherScope->sectionsForClass($classId, $dayWise))
            ->map(function ($row) {
                return [
                    'id' => (string) $row->id,
                    'section_id' => (string) $row->section_id,
                    'section' => (string) ($row->section ?? ''),
                ];
            })
            ->values();

        return response()->json($rows);
    }
}
