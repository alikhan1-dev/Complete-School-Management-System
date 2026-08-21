<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\OnlineExam\Models\OnlineExam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Onlineexam_model — exam definition CRUD + open/closed lists.
 * Deferred: mail/SMS publish, teacher-scoped lists, attach questions, assign students, portal.
 */
class OnlineExamService
{
    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * Upcoming / open exams: exam_to >= now, current session.
     *
     * @return Collection<int, object>
     */
    public function listOpenExams(): Collection
    {
        return $this->baseListQuery()
            ->where('onlineexam.exam_to', '>=', now()->format('Y-m-d H:i:s'))
            ->orderByDesc('onlineexam.exam_from')
            ->get();
    }

    /**
     * Closed exams: exam_to < now, current session.
     *
     * @return Collection<int, object>
     */
    public function listClosedExams(): Collection
    {
        return $this->baseListQuery()
            ->where('onlineexam.exam_to', '<', now()->format('Y-m-d H:i:s'))
            ->orderByDesc('onlineexam.exam_from')
            ->get();
    }

    public function find(int $id): OnlineExam
    {
        $exam = OnlineExam::query()->findOrFail($id);
        abort_unless((int) $exam->session_id === $this->currentSession->id(), 404);

        return $exam;
    }

    public function currentSessionId(): int
    {
        return $this->currentSession->id();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Request $request, ?OnlineExam $existing = null): array
    {
        $isQuiz = $request->boolean('is_quiz');
        $autoPublish = $this->nullableDateTime($request->input('auto_publish_date'));

        $payload = [
            'exam' => (string) $request->input('exam'),
            'attempt' => (int) $request->input('attempt'),
            'exam_from' => $this->requiredDateTime($request->input('exam_from'), 'exam_from'),
            'exam_to' => $this->requiredDateTime($request->input('exam_to'), 'exam_to'),
            'duration' => (string) $request->input('duration'),
            'description' => (string) $request->input('description'),
            'passing_percentage' => (float) $request->input('passing_percentage'),
            'answer_word_count' => (int) $request->input('word_limit'),
            'is_active' => $request->boolean('is_active') ? '1' : '0',
            'publish_result' => $request->boolean('publish_result') ? 1 : 0,
            'is_marks_display' => $request->boolean('is_marks_display') ? 1 : 0,
            'is_neg_marking' => $request->boolean('is_neg_marking') ? 1 : 0,
            'is_random_question' => $request->boolean('is_random_question') ? 1 : 0,
            'is_quiz' => $isQuiz ? 1 : 0,
            'auto_publish_date' => $autoPublish,
        ];

        if ($isQuiz) {
            $payload['publish_result'] = 0;
            $payload['auto_publish_date'] = null;
        }

        if ($existing === null) {
            $payload['session_id'] = $this->currentSession->id();
            $payload['is_rank_generated'] = 0;
            $payload['publish_exam_notification'] = 0;
            $payload['publish_result_notification'] = 0;
            $payload['time_from'] = null;
            $payload['time_to'] = null;
        }

        return $payload;
    }

    public function create(array $payload): OnlineExam
    {
        return OnlineExam::query()->create($payload);
    }

    public function update(OnlineExam $exam, array $payload): OnlineExam
    {
        $exam->fill($payload);
        $exam->save();

        return $exam;
    }

    public function delete(OnlineExam $exam): void
    {
        $exam->delete();
    }

    /**
     * Format DB datetime for datetime-local inputs.
     */
    public function toInputDateTime(?string $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d\TH:i');
        } catch (\Throwable) {
            return '';
        }
    }

    protected function baseListQuery()
    {
        return DB::table('onlineexam')
            ->where('onlineexam.session_id', $this->currentSession->id())
            ->select([
                'onlineexam.*',
                DB::raw('(select count(*) from onlineexam_questions where onlineexam_questions.onlineexam_id=onlineexam.id) as total_ques'),
            ]);
    }

    protected function requiredDateTime(mixed $value, string $field): string
    {
        $parsed = $this->nullableDateTime($value);
        if ($parsed === null) {
            throw ValidationException::withMessages([
                $field => 'A valid date and time is required.',
            ]);
        }

        return $parsed;
    }

    protected function nullableDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse(str_replace('T', ' ', (string) $value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
