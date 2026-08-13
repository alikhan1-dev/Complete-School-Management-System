<?php

namespace App\Modules\Homework\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Homework\Services\HomeworkDocumentService;
use App\Modules\Homework\Services\HomeworkService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Homework.php — admin list/create/edit/delete/download (first slice).
 * Deferred: evaluation, daily assignment, reports, mail/SMS, student portal.
 */
class HomeworkController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected HomeworkService $homework,
        protected HomeworkDocumentService $documents,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'subject_group_id' => $request->input('subject_group_id'),
            'subject_id' => $request->input('subject_id'),
        ];

        if ($request->filled('class_id')) {
            $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
                'subject_group_id' => ['nullable', 'integer', 'exists:subject_groups,id'],
                'subject_id' => ['nullable', 'integer', 'exists:subject_group_subjects,id'],
            ]);
        }

        $lists = $this->homework->listForFilters($filters);

        return view('shared::layouts.admin', [
            'title' => 'Homework',
            'contentView' => 'homework::admin.index',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'sections' => Section::query()->orderBy('section')->get(),
            'filters' => $filters,
            'upcoming' => $lists['upcoming'],
            'closed' => $lists['closed'],
            'canAdd' => $this->permissions->hasPrivilege('homework', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('homework', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('homework', 'can_delete'),
            'uploadMeta' => $this->documents->uploadRulesFromFiletypes(),
        ]);
    }

    public function create(): View
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_add'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Create Homework',
            'contentView' => 'homework::admin.form',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'sections' => Section::query()->orderBy('section')->get(),
            'editing' => null,
            'uploadMeta' => $this->documents->uploadRulesFromFiletypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_add'), 403);

        $data = $this->validatedPayload($request);
        $file = $request->file('userfile');
        $file = $file instanceof UploadedFile ? $file : null;

        $this->homework->store($data, $file);

        return redirect()
            ->route('homework.index', [
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
            ])
            ->with('success', 'Homework created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_edit'), 403);

        $editing = $this->homework->findDetailed($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Homework',
            'contentView' => 'homework::admin.form',
            'classes' => SchoolClass::query()->orderBy('class')->get(),
            'sections' => Section::query()->orderBy('section')->get(),
            'editing' => $editing,
            'uploadMeta' => $this->documents->uploadRulesFromFiletypes(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_edit'), 403);

        $homework = $this->homework->find($id);
        $data = $this->validatedPayload($request);
        $file = $request->file('userfile');
        $file = $file instanceof UploadedFile ? $file : null;

        $this->homework->update($homework, $data, $file);

        return redirect()
            ->route('homework.index', [
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
            ])
            ->with('success', 'Homework updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_delete'), 403);

        $homework = $this->homework->find($id);
        $classId = (int) $homework->class_id;
        $sectionId = (int) $homework->section_id;
        $this->homework->delete($homework);

        return redirect()
            ->route('homework.index', [
                'class_id' => $classId,
                'section_id' => $sectionId,
            ])
            ->with('success', 'Homework deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('homework', 'can_view'), 403);

        return $this->homework->download($this->homework->find($id));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedPayload(Request $request): array
    {
        $meta = $this->documents->uploadRulesFromFiletypes();

        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'subject_group_id' => ['required', 'integer', 'exists:subject_groups,id'],
            'subject_group_subject_id' => ['required', 'integer', 'exists:subject_group_subjects,id'],
            'homework_date' => ['required', 'date'],
            'submit_date' => ['required', 'date', 'after_or_equal:homework_date'],
            'marks' => ['nullable', 'numeric', 'min:0'],
            'description' => ['required', 'string'],
            'userfile' => [
                'nullable',
                'file',
                File::types($meta['extensions'])->max($meta['max_kb']),
            ],
        ]);

        // Ensure selected subject belongs to selected group
        $belongs = DB::table('subject_group_subjects')
            ->where('id', (int) $data['subject_group_subject_id'])
            ->where('subject_group_id', (int) $data['subject_group_id'])
            ->exists();
        abort_unless($belongs, 422);

        return $data;
    }
}
