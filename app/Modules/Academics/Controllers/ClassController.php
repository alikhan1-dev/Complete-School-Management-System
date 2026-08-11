<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Requests\StoreClassRequest;
use App\Modules\Academics\Requests\UpdateClassRequest;
use App\Modules\Academics\Services\OrphanStudentCleanup;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OrphanStudentCleanup $orphanCleanup
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('class', 'can_view'), 403);

        $classes = SchoolClass::query()
            ->with(['classSections.section'])
            ->orderBy('id')
            ->get();

        $sections = Section::query()->orderBy('id')->get();

        return view('shared::layouts.admin', [
            'title' => 'Classes',
            'contentView' => 'academics::admin.classes.index',
            'classes' => $classes,
            'sections' => $sections,
        ]);
    }

    public function store(StoreClassRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('class', 'can_add'), 403);

        DB::transaction(function () use ($request) {
            $class = SchoolClass::query()->create([
                'class' => $request->validated('class'),
                'is_active' => 'yes',
            ]);

            foreach ($request->validated('sections') as $sectionId) {
                ClassSection::query()->create([
                    'class_id' => $class->id,
                    'section_id' => (int) $sectionId,
                    'is_active' => 'yes',
                ]);
            }
        });

        return redirect()->route('academics.classes.index')->with('success', 'Class created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('class', 'can_edit'), 403);

        $class = SchoolClass::query()->with('classSections')->findOrFail($id);
        $sections = Section::query()->orderBy('id')->get();
        $selectedSectionIds = $class->classSections->pluck('section_id')->all();

        return view('shared::layouts.admin', [
            'title' => 'Edit Class',
            'contentView' => 'academics::admin.classes.edit',
            'classRow' => $class,
            'sections' => $sections,
            'selectedSectionIds' => $selectedSectionIds,
        ]);
    }

    public function update(UpdateClassRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('class', 'can_edit'), 403);

        $class = SchoolClass::query()->findOrFail($id);
        $newSections = array_map('intval', $request->validated('sections'));
        // Always diff against DB (CI old list), not only submitted prev_sections.
        $prevSections = $class->classSections()->pluck('section_id')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($class, $newSections, $prevSections, $request) {
            $class->class = $request->validated('class');
            $class->save();

            $toAdd = array_values(array_diff($newSections, $prevSections));
            $toRemove = array_values(array_diff($prevSections, $newSections));

            foreach ($toAdd as $sectionId) {
                ClassSection::query()->firstOrCreate(
                    [
                        'class_id' => $class->id,
                        'section_id' => $sectionId,
                    ],
                    ['is_active' => 'yes']
                );
            }

            if ($toRemove !== []) {
                ClassSection::query()
                    ->where('class_id', $class->id)
                    ->whereIn('section_id', $toRemove)
                    ->delete();
            }
        });

        return redirect()->route('academics.classes.index')->with('success', 'Class updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('class', 'can_delete'), 403);

        DB::transaction(function () use ($id) {
            $class = SchoolClass::query()->findOrFail($id);
            $class->delete(); // cascades class_sections / student_session via FK
            $this->orphanCleanup->removeStudentsWithoutSession();
        });

        return redirect()->route('academics.classes.index')->with('success', 'Class deleted successfully.');
    }

    /**
     * CI: GET classes/get_section/{id} — HTML options of sections for a class.
     */
    public function getSectionHtml(int $id): Response
    {
        $rows = ClassSection::query()
            ->with('section')
            ->where('class_id', $id)
            ->get();

        $html = '';
        foreach ($rows as $row) {
            if (! $row->section) {
                continue;
            }
            $html .= '<option value="'.$row->section->id.'">'.e($row->section->section).'</option>';
        }

        return response($html);
    }
}
