<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\OnlineExam\Models\OnlineExam;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI user/Onlineexam — student take-exam portal.
 * Deferred: print, ranking, reports, mail/SMS, AJAX modal, SaaS storage quota.
 */
class StudentOnlineExamService
{
    public const OBJECTIVE_TYPES = ['singlechoice', 'true_false', 'multichoice'];

    public const DESCRIPTIVE_TYPE = 'descriptive';

    public function __construct(
        protected OnlineExamResultService $results,
        protected OnlineExamDocumentService $documents,
    ) {
    }

    public function currentStudentSessionId(): int
    {
        $id = (int) (session('current_class.student_session_id') ?? 0);
        if ($id <= 0) {
            throw ValidationException::withMessages([
                'student_session_id' => 'Please select a class first.',
            ]);
        }

        return $id;
    }

    /**
     * Assigned active exams for the student session.
     *
     * @return array{upcoming:Collection<int,object>,closed:Collection<int,object>}
     */
    public function listExams(int $studentSessionId): array
    {
        $now = now()->format('Y-m-d H:i:s');

        $base = DB::table('onlineexam')
            ->join('onlineexam_students', 'onlineexam_students.onlineexam_id', '=', 'onlineexam.id')
            ->where('onlineexam_students.student_session_id', $studentSessionId)
            ->where('onlineexam.is_active', 1)
            ->select([
                'onlineexam.*',
                'onlineexam_students.id as onlineexam_student_id',
                'onlineexam_students.is_attempted',
                DB::raw('(select count(*) from onlineexam_attempts where onlineexam_attempts.onlineexam_student_id = onlineexam_students.id) as counter'),
            ])
            ->orderByDesc('onlineexam.exam_from');

        $upcoming = (clone $base)->where('onlineexam.exam_to', '>=', $now)->get();
        $closed = (clone $base)->where('onlineexam.exam_to', '<', $now)->get();

        return ['upcoming' => $upcoming, 'closed' => $closed];
    }

    public function assignment(int $studentSessionId, int $examId): ?object
    {
        return DB::table('onlineexam_students')
            ->where('student_session_id', $studentSessionId)
            ->where('onlineexam_id', $examId)
            ->first();
    }

    public function exam(int $examId): OnlineExam
    {
        return OnlineExam::query()->findOrFail($examId);
    }

    /**
     * Effective publish_result flag (CI view.php quiz / auto_publish_date rules).
     */
    public function isResultPublished(OnlineExam $exam, ?object $assignment): bool
    {
        if ($assignment && (int) $assignment->is_attempted === 1 && (int) $exam->is_quiz === 1) {
            return true;
        }

        if ((int) $exam->publish_result === 1) {
            return true;
        }

        $auto = (string) ($exam->auto_publish_date ?? '');
        if ($auto !== '' && $auto !== '0000-00-00' && $auto !== '0000-00-00 00:00:00') {
            try {
                return Carbon::parse($auto)->lteOrEqualTo(now());
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    /**
     * CI Start Exam button gate (student role only — caller enforces role).
     */
    public function canStart(OnlineExam $exam, ?object $assignment, bool $isStudentRole): bool
    {
        if (! $isStudentRole || ! $assignment) {
            return false;
        }
        if ((int) $assignment->is_attempted === 1) {
            return false;
        }
        if (! (int) $exam->is_active) {
            return false;
        }
        if ($this->isResultPublished($exam, $assignment)) {
            return false;
        }

        $now = now();
        try {
            $from = Carbon::parse((string) $exam->exam_from);
            $to = Carbon::parse((string) $exam->exam_to);
        } catch (\Throwable) {
            return false;
        }

        return $now->greaterThanOrEqualTo($from) && $now->lessThanOrEqualTo($to);
    }

    public function attemptCount(int $onlineexamStudentId): int
    {
        return (int) DB::table('onlineexam_attempts')
            ->where('onlineexam_student_id', $onlineexamStudentId)
            ->count();
    }

    /**
     * Start take session: record attempt if under cap; return questions + timer.
     *
     * @return array{
     *   exam:OnlineExam,
     *   assignment:object,
     *   questions:Collection<int,object>,
     *   duration_seconds:int,
     *   question_status:int,
     *   blocked:bool,
     *   block_message:?string
     * }
     */
    public function beginTake(int $studentSessionId, int $examId, bool $isStudentRole): array
    {
        $exam = $this->exam($examId);
        $assignment = $this->assignment($studentSessionId, $examId);

        if (! $assignment) {
            throw ValidationException::withMessages([
                'exam' => 'You are not assigned to this exam.',
            ]);
        }

        if (! $isStudentRole) {
            throw ValidationException::withMessages([
                'exam' => 'Only students can take this exam.',
            ]);
        }

        if ((int) $assignment->is_attempted === 1) {
            return $this->blockedTake($exam, $assignment, 'You have already submitted this exam.');
        }

        if (! (int) $exam->is_active) {
            return $this->blockedTake($exam, $assignment, 'This exam is not active.');
        }

        $now = now();
        $to = Carbon::parse((string) $exam->exam_to);
        if ($now->greaterThan($to)) {
            return $this->blockedTake($exam, $assignment, 'Exam window has closed.');
        }

        $from = Carbon::parse((string) $exam->exam_from);
        if ($now->lessThan($from)) {
            return $this->blockedTake($exam, $assignment, 'Exam has not started yet.');
        }

        $attempts = $this->attemptCount((int) $assignment->id);
        $allowed = max(1, (int) $exam->attempt);

        if ($attempts >= $allowed) {
            return $this->blockedTake($exam, $assignment, 'No attempts remaining.');
        }

        DB::table('onlineexam_attempts')->insert([
            'onlineexam_student_id' => (int) $assignment->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $questions = $this->examQuestions($examId, (bool) $exam->is_random_question);
        $durationSeconds = $this->effectiveDurationSeconds($exam);

        return [
            'exam' => $exam,
            'assignment' => $assignment,
            'questions' => $questions,
            'duration_seconds' => $durationSeconds,
            'question_status' => 0,
            'blocked' => false,
            'block_message' => null,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function examQuestions(int $examId, bool $random): Collection
    {
        $query = DB::table('onlineexam_questions')
            ->join('questions', 'questions.id', '=', 'onlineexam_questions.question_id')
            ->where('onlineexam_questions.onlineexam_id', $examId)
            ->select([
                'onlineexam_questions.id as onlineexam_question_id',
                'onlineexam_questions.marks',
                'onlineexam_questions.neg_marks',
                'questions.id as question_id',
                'questions.question',
                'questions.question_type',
                'questions.opt_a',
                'questions.opt_b',
                'questions.opt_c',
                'questions.opt_d',
                'questions.opt_e',
                'questions.descriptive_word_limit',
            ]);

        if ($random) {
            $query->inRandomOrder();
        } else {
            $query->orderByDesc('onlineexam_questions.id');
        }

        return $query->get();
    }

    /**
     * Persist objective + descriptive answers and mark attempted (CI save + updateExamResult).
     *
     * @param  array<string, mixed>  $answers  keyed by onlineexam_question_id
     * @param  array<int, UploadedFile>  $attachments  keyed by onlineexam_question_id
     */
    public function submit(
        int $studentSessionId,
        int $examId,
        int $onlineexamStudentId,
        array $answers,
        array $attachments,
        bool $isStudentRole
    ): void {
        if (! $isStudentRole) {
            throw ValidationException::withMessages([
                'exam' => 'Only students can submit this exam.',
            ]);
        }

        $exam = $this->exam($examId);
        $assignment = $this->assignment($studentSessionId, $examId);

        if (! $assignment || (int) $assignment->id !== $onlineexamStudentId) {
            throw ValidationException::withMessages([
                'exam' => 'Invalid exam assignment.',
            ]);
        }

        if ((int) $assignment->is_attempted === 1) {
            throw ValidationException::withMessages([
                'exam' => 'You have already submitted this exam.',
            ]);
        }

        // CI does not re-check exam_to on save — allow submit for started attempts.

        $questionMap = $this->examQuestions($examId, false)->keyBy('onlineexam_question_id');
        $wordLimit = (int) ($exam->answer_word_count ?? -1);
        $oqIds = collect(array_keys($answers))
            ->merge(array_keys($attachments))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(fn (int $id) => $id > 0)
            ->values();

        $rows = [];

        foreach ($oqIds as $oqId) {
            $question = $questionMap->get($oqId);
            if (! $question) {
                continue;
            }

            $type = (string) $question->question_type;
            $value = $answers[$oqId] ?? ($answers[(string) $oqId] ?? null);
            $file = $attachments[$oqId] ?? null;

            if (in_array($type, self::OBJECTIVE_TYPES, true)) {
                if ($type === 'multichoice') {
                    if (! is_array($value) || $value === []) {
                        continue;
                    }
                    $select = json_encode(array_values($value));
                } else {
                    $select = is_scalar($value) ? (string) $value : '';
                    if ($select === '') {
                        continue;
                    }
                }

                $rows[] = $this->resultRow($onlineexamStudentId, $oqId, $select, '', '');
                continue;
            }

            if ($type !== self::DESCRIPTIVE_TYPE) {
                continue;
            }

            $select = is_scalar($value) ? (string) $value : '';
            $hasFile = $file instanceof UploadedFile && $file->isValid();

            // CI: save descriptive if answer text isset OR attachment uploaded
            if (trim($select) === '' && ! $hasFile) {
                continue;
            }

            if ($wordLimit > 0 && $this->wordCount($select) > $wordLimit) {
                throw ValidationException::withMessages([
                    "answers.{$oqId}" => "Answer exceeds the exam word limit of {$wordLimit}.",
                ]);
            }

            $attachmentName = '';
            $attachmentUpload = '';
            if ($hasFile) {
                $attachmentName = (string) $file->getClientOriginalName();
                $attachmentUpload = $this->documents->store($file);
            }

            $rows[] = $this->resultRow(
                $onlineexamStudentId,
                $oqId,
                $select,
                $attachmentName,
                $attachmentUpload
            );
        }

        DB::transaction(function () use ($rows, $onlineexamStudentId) {
            if ($rows !== []) {
                DB::table('onlineexam_student_results')->insert($rows);
            }
            DB::table('onlineexam_students')
                ->where('id', $onlineexamStudentId)
                ->update(['is_attempted' => 1, 'updated_at' => now()]);
        });
    }

    /**
     * Student may download only attachments belonging to their assignment.
     */
    public function downloadOwnAttachment(int $studentSessionId, string $doc): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $safe = basename($doc);
        abort_unless($safe !== '' && $safe === $doc && ! str_contains($safe, '..'), 404);

        $owns = DB::table('onlineexam_student_results')
            ->join('onlineexam_students', 'onlineexam_students.id', '=', 'onlineexam_student_results.onlineexam_student_id')
            ->where('onlineexam_students.student_session_id', $studentSessionId)
            ->where('onlineexam_student_results.attachment_upload_name', $safe)
            ->exists();
        abort_unless($owns, 404);

        return $this->documents->download($safe);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resultRow(
        int $onlineexamStudentId,
        int $oqId,
        string $select,
        string $attachmentName,
        string $attachmentUpload
    ): array {
        return [
            'onlineexam_student_id' => $onlineexamStudentId,
            'onlineexam_question_id' => $oqId,
            'select_option' => $select,
            // CI omits marks on student submit; schema here disallows null — store 0 until evaluation.
            'marks' => 0,
            'remark' => '',
            'attachment_name' => $attachmentName,
            'attachment_upload_name' => $attachmentUpload,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function wordCount(string $text): int
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        if ($plain === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function publishedScore(OnlineExam $exam, object $assignment): ?array
    {
        if (! $this->isResultPublished($exam, $assignment)) {
            return null;
        }
        if ((int) $assignment->is_attempted !== 1) {
            return null;
        }

        $rows = $this->results->resultRows((int) $assignment->id, (int) $exam->id);

        return $this->results->scoreSummary($exam, $rows);
    }

    public function durationToSeconds(?string $hms): int
    {
        $hms = trim((string) $hms);
        if ($hms === '') {
            return 0;
        }

        $parts = array_map('intval', explode(':', $hms));
        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }
        if (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        }

        return max(0, (int) $hms);
    }

    public function effectiveDurationSeconds(OnlineExam $exam): int
    {
        $configured = $this->durationToSeconds((string) $exam->duration);
        $remaining = max(0, Carbon::parse((string) $exam->exam_to)->getTimestamp() - now()->getTimestamp());

        if ($configured <= 0) {
            return $remaining;
        }

        return min($configured, $remaining);
    }

    /**
     * @return array{
     *   exam:OnlineExam,
     *   assignment:object,
     *   questions:Collection<int,object>,
     *   duration_seconds:int,
     *   question_status:int,
     *   blocked:bool,
     *   block_message:?string
     * }
     */
    protected function blockedTake(OnlineExam $exam, object $assignment, string $message): array
    {
        return [
            'exam' => $exam,
            'assignment' => $assignment,
            'questions' => collect(),
            'duration_seconds' => 0,
            'question_status' => 1,
            'blocked' => true,
            'block_message' => $message,
        ];
    }
}
