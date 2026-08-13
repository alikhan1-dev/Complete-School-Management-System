<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\OnlineExam\Models\OnlineExamQuestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Onlineexamquestion_model / questionAdd — attach questions to an exam.
 * Deferred: heavy AJAX pager/modal picker; teacher-scoped bank filtering.
 */
class OnlineExamQuestionService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected OnlineExamService $exams
    ) {
    }

    public function exam(int $examId): OnlineExam
    {
        return $this->exams->find($examId);
    }

    /**
     * Questions already attached to this exam.
     *
     * @return Collection<int, object>
     */
    public function attachedQuestions(int $examId): Collection
    {
        return DB::table('onlineexam_questions')
            ->join('questions', 'questions.id', '=', 'onlineexam_questions.question_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->where('onlineexam_questions.onlineexam_id', $examId)
            ->orderBy('onlineexam_questions.id')
            ->select([
                'onlineexam_questions.id as onlineexam_question_id',
                'onlineexam_questions.marks',
                'onlineexam_questions.neg_marks',
                'questions.id as question_id',
                'questions.question',
                'questions.question_type',
                'questions.level',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
            ])
            ->get();
    }

    /**
     * Bank questions available to attach (not already on this exam).
     *
     * @param  array{subject_id?:mixed,question_type?:mixed,question_level?:mixed,keyword?:mixed,class_id?:mixed}  $filters
     */
    public function availableQuestions(OnlineExam $exam, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DB::table('questions')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->leftJoin('classes', 'classes.id', '=', 'questions.class_id')
            ->whereNotIn('questions.id', function ($sub) use ($exam) {
                $sub->select('question_id')
                    ->from('onlineexam_questions')
                    ->where('onlineexam_id', $exam->id)
                    ->whereNotNull('question_id');
            })
            ->orderByDesc('questions.id')
            ->select([
                'questions.id',
                'questions.question',
                'questions.question_type',
                'questions.level',
                'questions.class_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'classes.class as class_name',
            ]);

        // CI: quiz exams cannot include descriptive questions
        if ((int) $exam->is_quiz === 1) {
            $query->where('questions.question_type', '!=', 'descriptive');
        }

        if (! empty($filters['subject_id'])) {
            $query->where('questions.subject_id', (int) $filters['subject_id']);
        }
        if (! empty($filters['question_type'])) {
            $query->where('questions.question_type', (string) $filters['question_type']);
        }
        if (! empty($filters['question_level'])) {
            $query->where('questions.level', (string) $filters['question_level']);
        }
        if (! empty($filters['class_id'])) {
            $query->where('questions.class_id', (int) $filters['class_id']);
        }
        if (! empty($filters['keyword'])) {
            $query->where('questions.question', 'like', '%'.$filters['keyword'].'%');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function attach(OnlineExam $exam, int $questionId, float $marks, float $negMarks): OnlineExamQuestion
    {
        $exists = OnlineExamQuestion::query()
            ->where('onlineexam_id', $exam->id)
            ->where('question_id', $questionId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'question_id' => 'This question is already attached to the exam.',
            ]);
        }

        $question = DB::table('questions')->where('id', $questionId)->first();
        if (! $question) {
            throw ValidationException::withMessages([
                'question_id' => 'Selected question was not found.',
            ]);
        }

        if ((int) $exam->is_quiz === 1 && $question->question_type === 'descriptive') {
            throw ValidationException::withMessages([
                'question_id' => 'Descriptive questions cannot be attached to a quiz exam.',
            ]);
        }

        return OnlineExamQuestion::query()->create([
            'onlineexam_id' => $exam->id,
            'question_id' => $questionId,
            'session_id' => $this->currentSession->id(),
            'marks' => $marks,
            'neg_marks' => $negMarks,
            'is_active' => '0',
        ]);
    }

    public function updateMarks(OnlineExam $exam, int $onlineexamQuestionId, float $marks, float $negMarks): OnlineExamQuestion
    {
        $row = OnlineExamQuestion::query()
            ->where('id', $onlineexamQuestionId)
            ->where('onlineexam_id', $exam->id)
            ->firstOrFail();

        $row->marks = $marks;
        $row->neg_marks = $negMarks;
        $row->save();

        return $row;
    }

    public function detach(OnlineExam $exam, int $onlineexamQuestionId): void
    {
        $row = OnlineExamQuestion::query()
            ->where('id', $onlineexamQuestionId)
            ->where('onlineexam_id', $exam->id)
            ->firstOrFail();

        $row->delete();
    }
}
