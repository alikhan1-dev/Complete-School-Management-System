<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\OnlineExam\Models\OnlineExam;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI user/Onlineexam — student take-exam portal (first slice).
 * Deferred: descriptive file uploads, print, ranking, reports, mail/SMS, AJAX modal.
 */
class StudentOnlineExamService
{
    public const OBJECTIVE_TYPES = ['singlechoice', 'true_false', 'multichoice'];

    public function __construct(protected OnlineExamResultService $results)
    {
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
            ]);

        if ($random) {
            $query->inRandomOrder();
        } else {
            $query->orderByDesc('onlineexam_questions.id');
        }

        return $query->get();
    }

    /**
     * Persist objective answers and mark attempted (CI save + updateExamResult).
     *
     * @param  array<string, mixed>  $answers  keyed by onlineexam_question_id
     */
    public function submit(int $studentSessionId, int $examId, int $onlineexamStudentId, array $answers, bool $isStudentRole): void
    {
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

        if (now()->greaterThan(Carbon::parse((string) $exam->exam_to))) {
            // Still allow save if already in take (CI still posts); but if far past, block.
            // CI does not re-check exam_to on save — allow submit for started attempts.
        }

        $questionMap = $this->examQuestions($examId, false)->keyBy('onlineexam_question_id');
        $rows = [];

        foreach ($answers as $oqId => $value) {
            $oqId = (int) $oqId;
            $question = $questionMap->get($oqId);
            if (! $question) {
                continue;
            }

            $type = (string) $question->question_type;
            if (! in_array($type, self::OBJECTIVE_TYPES, true)) {
                continue;
            }

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

            $rows[] = [
                'onlineexam_student_id' => $onlineexamStudentId,
                'onlineexam_question_id' => $oqId,
                'select_option' => $select,
                // CI omits marks on student submit; schema here disallows null — store 0 until evaluation.
                'marks' => 0,
                'remark' => '',
                'attachment_name' => '',
                'attachment_upload_name' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ];
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
