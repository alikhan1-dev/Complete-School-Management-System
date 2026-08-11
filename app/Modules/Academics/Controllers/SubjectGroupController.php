<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Academics\Requests\StoreSubjectGroupRequest;
use App\Modules\Academics\Requests\UpdateSubjectGroupRequest;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubjectGroupController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected CurrentSessionResolver $currentSession
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('subject_group', 'can_view'), 403);

        $sessionId = $this->currentSession->id();

        $groups = SubjectGroup::query()
            ->with(['subjects', 'classSections.section', 'classSections.schoolClass'])
            ->where('session_id', $sessionId)
            ->orderBy('id')
            ->get();

        $classes = SchoolClass::query()->orderBy('id')->get();
        $subjects = Subject::query()->orderBy('id')->get();

        return view('shared::layouts.admin', [
            'title' => 'Subject Groups',
            'contentView' => 'academics::admin.subject_groups.index',
            'groups' => $groups,
            'classes' => $classes,
            'subjects' => $subjects,
            'sessionId' => $sessionId,
        ]);
    }

    public function store(StoreSubjectGroupRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('subject_group', 'can_add'), 403);

        $sessionId = $this->currentSession->id();

        DB::transaction(function () use ($request, $sessionId) {
            $group = SubjectGroup::query()->create([
                'name' => $request->validated('name'),
                'description' => $request->validated('description') ?? '',
                'session_id' => $sessionId,
            ]);

            foreach ($request->validated('subject') as $subjectId) {
                SubjectGroupSubject::query()->create([
                    'subject_group_id' => $group->id,
                    'subject_id' => (int) $subjectId,
                    'session_id' => $sessionId,
                ]);
            }

            foreach ($request->validated('sections') as $classSectionId) {
                SubjectGroupClassSection::query()->create([
                    'subject_group_id' => $group->id,
                    'class_section_id' => (int) $classSectionId,
                    'session_id' => $sessionId,
                    'is_active' => 1,
                ]);
            }
        });

        return redirect()->route('academics.subject_groups.index')->with('success', 'Subject group created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('subject_group', 'can_edit'), 403);

        $group = SubjectGroup::query()
            ->with(['subjects', 'classSections.schoolClass'])
            ->findOrFail($id);

        $classes = SchoolClass::query()->orderBy('id')->get();
        $subjects = Subject::query()->orderBy('id')->get();
        $selectedSubjectIds = $group->subjects->pluck('id')->all();
        $selectedClassSectionIds = $group->classSections->pluck('id')->all();
        $classId = optional($group->classSections->first())->class_id;

        return view('shared::layouts.admin', [
            'title' => 'Edit Subject Group',
            'contentView' => 'academics::admin.subject_groups.edit',
            'group' => $group,
            'classes' => $classes,
            'subjects' => $subjects,
            'selectedSubjectIds' => $selectedSubjectIds,
            'selectedClassSectionIds' => $selectedClassSectionIds,
            'classId' => $classId,
        ]);
    }

    public function update(UpdateSubjectGroupRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('subject_group', 'can_edit'), 403);

        $sessionId = $this->currentSession->id();
        $group = SubjectGroup::query()->findOrFail($id);

        $newSections = array_map('intval', $request->validated('sections'));
        $newSubjects = array_map('intval', $request->validated('subject'));
        // Match CI edit(): diff against server-side old_sections / old_subjects.
        $prevSections = $group->classSections()->pluck('class_sections.id')->map(fn ($id) => (int) $id)->all();
        $prevSubjects = $group->subjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($group, $request, $sessionId, $newSections, $newSubjects, $prevSections, $prevSubjects) {
            $group->name = $request->validated('name');
            $group->description = $request->validated('description') ?? '';
            $group->save();

            foreach (array_diff($newSubjects, $prevSubjects) as $subjectId) {
                SubjectGroupSubject::query()->firstOrCreate([
                    'subject_group_id' => $group->id,
                    'subject_id' => $subjectId,
                    'session_id' => $sessionId,
                ]);
            }
            if ($toRemove = array_diff($prevSubjects, $newSubjects)) {
                SubjectGroupSubject::query()
                    ->where('subject_group_id', $group->id)
                    ->whereIn('subject_id', $toRemove)
                    ->delete();
            }

            foreach (array_diff($newSections, $prevSections) as $classSectionId) {
                SubjectGroupClassSection::query()->firstOrCreate([
                    'subject_group_id' => $group->id,
                    'class_section_id' => $classSectionId,
                    'session_id' => $sessionId,
                ], ['is_active' => 1]);
            }
            if ($toRemoveSec = array_diff($prevSections, $newSections)) {
                SubjectGroupClassSection::query()
                    ->where('subject_group_id', $group->id)
                    ->whereIn('class_section_id', $toRemoveSec)
                    ->delete();
            }
        });

        return redirect()->route('academics.subject_groups.index')->with('success', 'Subject group updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('subject_group', 'can_delete'), 403);

        SubjectGroup::query()->findOrFail($id)->delete();

        return redirect()->route('academics.subject_groups.index')->with('success', 'Subject group deleted successfully.');
    }

    public function getGroupByClassAndSection(Request $request): JsonResponse
    {
        $classId = (int) $request->input('class_id');
        $sectionId = (int) $request->input('section_id');
        $sessionId = (int) ($request->input('session_id') ?: $this->currentSession->id());

        $classSection = DB::table('class_sections')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->first();

        if (! $classSection) {
            return response()->json([]);
        }

        $rows = DB::table('subject_group_class_sections')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_class_sections.subject_group_id')
            ->where('subject_group_class_sections.class_section_id', $classSection->id)
            ->where('subject_group_class_sections.session_id', $sessionId)
            ->select('subject_groups.name', 'subject_group_class_sections.*')
            ->get();

        return response()->json($rows);
    }

    public function getGroupSubjects(Request $request): JsonResponse
    {
        $groupId = (int) $request->input('subject_group_id');
        $sessionId = (int) ($request->input('session_id') ?: $this->currentSession->id());

        $rows = DB::table('subject_group_subjects')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_group_subjects.subject_group_id', $groupId)
            ->where('subject_group_subjects.session_id', $sessionId)
            ->select(
                'subject_group_subjects.*',
                'subjects.name',
                'subjects.code',
                'subjects.type'
            )
            ->get();

        return response()->json($rows);
    }
}
