<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OnlineExam\Services\OnlineExamDocumentService;
use App\Modules\OnlineExam\Services\StudentOnlineExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/Onlineexam — student portal take-exam (objective + descriptive).
 * Deferred: print, ranking, reports, mail/SMS, SaaS storage quota.
 */
class StudentOnlineExamController extends Controller
{
    public function __construct(
        protected StudentOnlineExamService $portal,
        protected OnlineExamDocumentService $documents,
    ) {
    }

    protected function isStudentRole(): bool
    {
        $user = Auth::guard('student_parent')->user();

        return $user && (string) ($user->role ?? '') === 'student';
    }

    public function index(): View
    {
        $sessionId = $this->portal->currentStudentSessionId();
        $lists = $this->portal->listExams($sessionId);

        return view('shared::layouts.student_parent', [
            'title' => 'Online Examinations',
            'contentView' => 'onlineexam::user.index',
            'upcoming' => $lists['upcoming'],
            'closed' => $lists['closed'],
            'isStudent' => $this->isStudentRole(),
        ]);
    }

    public function view(int $id): View
    {
        $sessionId = $this->portal->currentStudentSessionId();
        $exam = $this->portal->exam($id);
        $assignment = $this->portal->assignment($sessionId, $id);
        $published = $assignment ? $this->portal->isResultPublished($exam, $assignment) : false;
        $canStart = $this->portal->canStart($exam, $assignment, $this->isStudentRole());
        $score = $assignment && $published
            ? $this->portal->publishedScore($exam, $assignment)
            : null;

        return view('shared::layouts.student_parent', [
            'title' => $exam->exam,
            'contentView' => 'onlineexam::user.view',
            'exam' => $exam,
            'assignment' => $assignment,
            'canStart' => $canStart,
            'resultPublished' => $published,
            'score' => $score,
            'isStudent' => $this->isStudentRole(),
            'attemptCount' => $assignment ? $this->portal->attemptCount((int) $assignment->id) : 0,
        ]);
    }

    public function take(int $id): View|RedirectResponse
    {
        $sessionId = $this->portal->currentStudentSessionId();
        $payload = $this->portal->beginTake($sessionId, $id, $this->isStudentRole());

        if ($payload['blocked']) {
            return redirect()
                ->route('user.onlineexam.view', $id)
                ->with('error', $payload['block_message'] ?? 'Unable to start exam.');
        }

        $uploadMeta = $this->documents->uploadRulesFromFiletypes();

        return view('shared::layouts.student_parent', [
            'title' => 'Take Exam — '.$payload['exam']->exam,
            'contentView' => 'onlineexam::user.take',
            'exam' => $payload['exam'],
            'assignment' => $payload['assignment'],
            'questions' => $payload['questions'],
            'durationSeconds' => $payload['duration_seconds'],
            'uploadExtensions' => $uploadMeta['extensions'],
            'uploadMaxKb' => $uploadMeta['max_kb'],
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $meta = $this->documents->uploadRulesFromFiletypes();

        $data = $request->validate([
            'exam_id' => ['required', 'integer'],
            'onlineexam_student_id' => ['required', 'integer'],
            'answers' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => [
                'nullable',
                'file',
                File::types($meta['extensions'])->max($meta['max_kb']),
            ],
        ]);

        $sessionId = $this->portal->currentStudentSessionId();

        /** @var array<int, UploadedFile> $attachments */
        $attachments = [];
        foreach ($request->file('attachments', []) as $oqId => $file) {
            if ($file instanceof UploadedFile) {
                $attachments[(int) $oqId] = $file;
            }
        }

        $this->portal->submit(
            $sessionId,
            (int) $data['exam_id'],
            (int) $data['onlineexam_student_id'],
            (array) ($data['answers'] ?? []),
            $attachments,
            $this->isStudentRole()
        );

        return redirect()
            ->route('user.onlineexam.index')
            ->with('success', 'Exam submitted successfully.');
    }

    public function downloadAttachment(string $doc): BinaryFileResponse
    {
        $sessionId = $this->portal->currentStudentSessionId();

        return $this->portal->downloadOwnAttachment($sessionId, $doc);
    }
}
