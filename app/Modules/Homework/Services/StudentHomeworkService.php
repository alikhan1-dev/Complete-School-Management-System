<?php

namespace App\Modules\Homework\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/Homework — student/parent portal list, detail, submit, downloads.
 * Deferred: daily assignment, mail/SMS, SaaS storage quota.
 */
class StudentHomeworkService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected HomeworkDocumentService $documents,
    ) {
    }

    /**
     * @return array{student_session_id:int,student_id:int,class_id:int,section_id:int,session_id:int}
     */
    public function currentContext(): array
    {
        $studentSessionId = (int) (session('current_class.student_session_id') ?? 0);
        if ($studentSessionId <= 0) {
            throw ValidationException::withMessages([
                'student_session_id' => 'Please select a class first.',
            ]);
        }

        $row = DB::table('student_session')->where('id', $studentSessionId)->first();
        if (! $row) {
            throw ValidationException::withMessages([
                'student_session_id' => 'Selected class is invalid.',
            ]);
        }

        $sessionId = (int) $this->currentSession->id();
        if ($sessionId <= 0 || (int) $row->session_id !== $sessionId) {
            // Still allow if sch_settings session matches student_session; otherwise soft-check
            $sessionId = (int) $row->session_id;
        }

        return [
            'student_session_id' => $studentSessionId,
            'student_id' => (int) $row->student_id,
            'class_id' => (int) $row->class_id,
            'section_id' => (int) $row->section_id,
            'session_id' => $sessionId,
        ];
    }

    /**
     * @return array{upcoming:Collection<int,object>,closed:Collection<int,object>}
     */
    public function listHomework(): array
    {
        $ctx = $this->currentContext();

        return [
            'upcoming' => $this->listByWindow($ctx, true),
            'closed' => $this->listByWindow($ctx, false),
        ];
    }

    /**
     * @return array{
     *   homework:object,
     *   submission:?object,
     *   evaluation:?object,
     *   evaluated:bool,
     *   canSubmit:bool
     * }
     */
    public function detail(int $homeworkId): array
    {
        $ctx = $this->currentContext();
        $homework = $this->findAssignedHomework($homeworkId, $ctx);

        $submission = DB::table('submit_assignment')
            ->where('homework_id', $homeworkId)
            ->where('student_id', $ctx['student_id'])
            ->first();

        $evaluation = DB::table('homework_evaluation')
            ->where('homework_id', $homeworkId)
            ->where('student_session_id', $ctx['student_session_id'])
            ->first();

        $evaluated = $evaluation !== null;

        return [
            'homework' => $homework,
            'submission' => $submission,
            'evaluation' => $evaluation,
            'evaluated' => $evaluated,
            'canSubmit' => ! $evaluated,
        ];
    }

    public function submit(int $homeworkId, string $message, ?UploadedFile $file): void
    {
        $ctx = $this->currentContext();
        $this->findAssignedHomework($homeworkId, $ctx);

        $existingEval = DB::table('homework_evaluation')
            ->where('homework_id', $homeworkId)
            ->where('student_session_id', $ctx['student_session_id'])
            ->exists();
        if ($existingEval) {
            throw ValidationException::withMessages([
                'homework_id' => 'This homework has already been evaluated.',
            ]);
        }

        $payload = [
            'homework_id' => $homeworkId,
            'student_id' => $ctx['student_id'],
            'message' => $message,
            'updated_at' => now(),
        ];

        if ($file instanceof UploadedFile && $file->isValid()) {
            $payload['docs'] = $this->documents->storeAssignment($file);
            $payload['file_name'] = (string) $file->getClientOriginalName();
        }

        $existing = DB::table('submit_assignment')
            ->where('homework_id', $homeworkId)
            ->where('student_id', $ctx['student_id'])
            ->first();

        if ($existing) {
            // Keep previous docs if no new file (CI parity)
            if (! isset($payload['docs'])) {
                unset($payload['docs'], $payload['file_name']);
            }
            DB::table('submit_assignment')
                ->where('id', $existing->id)
                ->update($payload);
        } else {
            $payload['docs'] = $payload['docs'] ?? '';
            $payload['file_name'] = $payload['file_name'] ?? '';
            $payload['created_at'] = now();
            DB::table('submit_assignment')->insert($payload);
        }
    }

    public function downloadTeacherDocument(int $homeworkId): BinaryFileResponse
    {
        $ctx = $this->currentContext();
        $homework = $this->findAssignedHomework($homeworkId, $ctx);
        $doc = (string) ($homework->document ?? '');
        abort_unless($doc !== '', 404);

        return $this->documents->download($doc);
    }

    /**
     * CI user/homework/assigmnetDownload/{id} — id is homework.id
     */
    public function downloadOwnSubmission(int $homeworkId): BinaryFileResponse
    {
        $ctx = $this->currentContext();
        $this->findAssignedHomework($homeworkId, $ctx);

        $row = DB::table('submit_assignment')
            ->where('homework_id', $homeworkId)
            ->where('student_id', $ctx['student_id'])
            ->first();
        abort_unless($row !== null && (string) ($row->docs ?? '') !== '', 404);

        return $this->documents->downloadAssignment((string) $row->docs);
    }

    /**
     * @param  array{student_session_id:int,student_id:int,class_id:int,section_id:int,session_id:int}  $ctx
     * @return Collection<int, object>
     */
    protected function listByWindow(array $ctx, bool $upcoming): Collection
    {
        $today = now()->format('Y-m-d');
        $query = DB::table('homework')
            ->leftJoin('homework_evaluation', function ($join) use ($ctx) {
                $join->on('homework_evaluation.homework_id', '=', 'homework.id')
                    ->where('homework_evaluation.student_session_id', '=', $ctx['student_session_id']);
            })
            ->join('classes', 'classes.id', '=', 'homework.class_id')
            ->join('sections', 'sections.id', '=', 'homework.section_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'homework.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->where('homework.class_id', $ctx['class_id'])
            ->where('homework.section_id', $ctx['section_id'])
            ->where('homework.session_id', $ctx['session_id'])
            ->select([
                'homework.*',
                'classes.class',
                'sections.section',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'subject_groups.name as subject_group_name',
                DB::raw('IFNULL(homework_evaluation.id, 0) as homework_evaluation_id'),
                'homework_evaluation.marks as evaluation_marks',
                'homework_evaluation.note as evaluation_note',
            ])
            ->orderByDesc('homework.homework_date');

        if ($upcoming) {
            $query->where('homework.submit_date', '>=', $today);
        } else {
            $query->where('homework.submit_date', '<', $today);
        }

        $rows = $query->get();
        $studentId = $ctx['student_id'];
        $submittedIds = DB::table('submit_assignment')
            ->where('student_id', $studentId)
            ->whereIn('homework_id', $rows->pluck('id')->all() ?: [0])
            ->pluck('homework_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $submittedSet = array_flip($submittedIds);

        return $rows->map(function (object $row) use ($submittedSet) {
            if ((int) $row->homework_evaluation_id > 0) {
                $row->portal_status = 'evaluated';
            } elseif (isset($submittedSet[(int) $row->id])) {
                $row->portal_status = 'submitted';
            } else {
                $row->portal_status = 'pending';
            }

            return $row;
        });
    }

    /**
     * @param  array{class_id:int,section_id:int,session_id:int}  $ctx
     */
    protected function findAssignedHomework(int $homeworkId, array $ctx): object
    {
        $row = DB::table('homework')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'homework.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->where('homework.id', $homeworkId)
            ->where('homework.class_id', $ctx['class_id'])
            ->where('homework.section_id', $ctx['section_id'])
            ->where('homework.session_id', $ctx['session_id'])
            ->select([
                'homework.*',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'subject_groups.name as subject_group_name',
            ])
            ->first();

        abort_unless($row !== null, 404);

        return $row;
    }
}
