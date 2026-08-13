<?php

namespace App\Modules\Homework\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Homework\Services\HomeworkDocumentService;
use App\Modules\Homework\Services\StudentDailyAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/homework/dailyassignment* — student portal CRUD.
 */
class StudentDailyAssignmentController extends Controller
{
    public function __construct(
        protected StudentDailyAssignmentService $daily,
        protected HomeworkDocumentService $documents,
    ) {
    }

    public function index(): View
    {
        return view('shared::layouts.student_parent', [
            'title' => 'Daily Assignment',
            'contentView' => 'homework::user.daily.index',
            'rows' => $this->daily->listForCurrentStudent(),
            'subjects' => $this->daily->availableSubjects(),
            'uploadMeta' => $this->documents->uploadRulesFromFiletypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedPayload($request);
        $file = $request->file('file');
        $file = $file instanceof UploadedFile ? $file : null;

        $this->daily->store($data, $file);

        return redirect()
            ->route('user.homework.daily.index')
            ->with('success', 'Daily assignment created successfully.');
    }

    public function edit(int $id): View
    {
        $editing = $this->daily->findOwned($id);

        return view('shared::layouts.student_parent', [
            'title' => 'Edit Daily Assignment',
            'contentView' => 'homework::user.daily.form',
            'editing' => $editing,
            'subjects' => $this->daily->availableSubjects(),
            'uploadMeta' => $this->documents->uploadRulesFromFiletypes(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $row = $this->daily->findOwned($id);
        $data = $this->validatedPayload($request);
        $file = $request->file('file');
        $file = $file instanceof UploadedFile ? $file : null;

        $this->daily->update($row, $data, $file);

        return redirect()
            ->route('user.homework.daily.index')
            ->with('success', 'Daily assignment updated successfully.');
    }

    /**
     * CI user/homework/updatedailyassignment — id via POST assigment_id.
     */
    public function updateFromBody(Request $request): RedirectResponse
    {
        $id = (int) $request->input('assigment_id', 0);
        abort_unless($id > 0, 404);

        return $this->update($request, $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->daily->delete($this->daily->findOwned($id));

        return redirect()
            ->route('user.homework.daily.index')
            ->with('success', 'Daily assignment deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        return $this->daily->download($id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedPayload(Request $request): array
    {
        $meta = $this->documents->uploadRulesFromFiletypes();

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject_group_subject_id' => ['required', 'integer', 'exists:subject_group_subjects,id'],
            'description' => ['nullable', 'string'],
            'file' => [
                'nullable',
                'file',
                File::types($meta['extensions'])->max($meta['max_kb']),
            ],
        ]);
    }
}
