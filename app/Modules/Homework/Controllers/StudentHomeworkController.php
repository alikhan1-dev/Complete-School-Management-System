<?php

namespace App\Modules\Homework\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Homework\Services\HomeworkDocumentService;
use App\Modules\Homework\Services\StudentHomeworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/Homework — portal list/detail/submit/download.
 * Deferred: daily assignment.
 */
class StudentHomeworkController extends Controller
{
    public function __construct(
        protected StudentHomeworkService $portal,
        protected HomeworkDocumentService $documents,
    ) {
    }

    public function index(): View
    {
        $lists = $this->portal->listHomework();

        return view('shared::layouts.student_parent', [
            'title' => 'Homework',
            'contentView' => 'homework::user.index',
            'upcoming' => $lists['upcoming'],
            'closed' => $lists['closed'],
        ]);
    }

    public function view(int $id): View
    {
        $payload = $this->portal->detail($id);
        $uploadMeta = $this->documents->uploadRulesFromFiletypes();

        return view('shared::layouts.student_parent', [
            'title' => 'Homework Detail',
            'contentView' => 'homework::user.view',
            'homework' => $payload['homework'],
            'submission' => $payload['submission'],
            'evaluation' => $payload['evaluation'],
            'evaluated' => $payload['evaluated'],
            'canSubmit' => $payload['canSubmit'],
            'uploadMeta' => $uploadMeta,
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $meta = $this->documents->uploadRulesFromFiletypes();

        $data = $request->validate([
            'homework_id' => ['required', 'integer'],
            'message' => ['required', 'string'],
            'file' => [
                'nullable',
                'file',
                File::types($meta['extensions'])->max($meta['max_kb']),
            ],
        ]);

        $file = $request->file('file');
        $file = $file instanceof UploadedFile ? $file : null;

        $this->portal->submit((int) $data['homework_id'], (string) $data['message'], $file);

        return redirect()
            ->route('user.homework.view', (int) $data['homework_id'])
            ->with('success', 'Homework submitted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        return $this->portal->downloadTeacherDocument($id);
    }

    /**
     * CI user/homework/assigmnetDownload/{id} — homework.id
     */
    public function downloadAssignment(int $id): BinaryFileResponse
    {
        return $this->portal->downloadOwnSubmission($id);
    }
}
