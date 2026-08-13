<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\OnlineExam\Models\Question;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Question_model / admin/Question — question bank CRUD.
 * Deferred: CSV import, bulk delete, CMS image browser, teacher-scoped visibility.
 */
class QuestionBankService
{
    /**
     * @return array<string, string>
     */
    public function questionTypes(): array
    {
        return [
            'singlechoice' => 'Single Choice',
            'multichoice' => 'Multiple Choice',
            'true_false' => 'True/False',
            'descriptive' => 'Descriptive',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function questionLevels(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ];
    }

    /**
     * CI Customlib::getQuesOption.
     *
     * @return array<string, string>
     */
    public function optionKeys(): array
    {
        return [
            'opt_a' => 'A',
            'opt_b' => 'B',
            'opt_c' => 'C',
            'opt_d' => 'D',
            'opt_e' => 'E',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function trueFalseOptions(): array
    {
        return [
            'true' => 'True',
            'false' => 'False',
        ];
    }

    public function listQuestions(int $perPage = 25): LengthAwarePaginator
    {
        return DB::table('questions')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->leftJoin('classes', 'classes.id', '=', 'questions.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'questions.section_id')
            ->orderByDesc('questions.id')
            ->select([
                'questions.id',
                'questions.question',
                'questions.question_type',
                'questions.level',
                'questions.class_id',
                'questions.section_id',
                'questions.subject_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'classes.class as class_name',
                'sections.section as section_name',
            ])
            ->paginate($perPage);
    }

    public function find(int $id): Question
    {
        return Question::query()->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Request $request, ?Question $existing = null): array
    {
        $type = (string) $request->input('question_type');
        $payload = [
            'subject_id' => (int) $request->input('subject_id'),
            'question' => (string) $request->input('question'),
            'question_type' => $type,
            'level' => (string) $request->input('question_level'),
            'class_id' => (int) $request->input('class_id'),
            'section_id' => $request->filled('section_id') ? (int) $request->input('section_id') : null,
            'class_section_id' => $existing?->class_section_id,
            'descriptive_word_limit' => $existing
                ? (int) $existing->descriptive_word_limit
                : 0,
            'opt_a' => '',
            'opt_b' => '',
            'opt_c' => '',
            'opt_d' => '',
            'opt_e' => '',
            'correct' => '',
        ];

        if ($existing === null) {
            $payload['staff_id'] = Auth::guard('staff')->id();
        }

        if ($type === 'singlechoice') {
            $payload['opt_a'] = (string) $request->input('opt_a', '');
            $payload['opt_b'] = (string) $request->input('opt_b', '');
            $payload['opt_c'] = (string) $request->input('opt_c', '');
            $payload['opt_d'] = (string) $request->input('opt_d', '');
            $payload['opt_e'] = (string) $request->input('opt_e', '');
            $payload['correct'] = (string) $request->input('correct', '');
        } elseif ($type === 'true_false') {
            $payload['correct'] = (string) $request->input('correct_true_false', '');
        } elseif ($type === 'multichoice') {
            $payload['opt_a'] = (string) $request->input('opt_a', '');
            $payload['opt_b'] = (string) $request->input('opt_b', '');
            $payload['opt_c'] = (string) $request->input('opt_c', '');
            $payload['opt_d'] = (string) $request->input('opt_d', '');
            $payload['opt_e'] = (string) $request->input('opt_e', '');
            $answers = array_values(array_filter((array) $request->input('ans', [])));
            if ($answers === []) {
                throw ValidationException::withMessages([
                    'ans' => 'At least one correct answer is required for multiple choice.',
                ]);
            }
            $payload['correct'] = json_encode($answers);
        }

        return $payload;
    }

    public function create(array $payload): Question
    {
        return Question::query()->create($payload);
    }

    public function update(Question $question, array $payload): Question
    {
        $question->fill($payload);
        $question->save();

        return $question;
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }

    /**
     * Decode multichoice correct answers for edit forms.
     *
     * @return list<string>
     */
    public function decodedMultichoiceAnswers(?string $correct): array
    {
        if ($correct === null || $correct === '') {
            return [];
        }

        $decoded = json_decode($correct, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map('strval', $decoded));
    }
}
