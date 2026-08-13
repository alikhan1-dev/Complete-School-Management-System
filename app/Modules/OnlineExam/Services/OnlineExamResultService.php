<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\OnlineExam\Models\OnlineExam;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Onlineexamresult_model — admin result view + descriptive evaluation.
 * Deferred: student portal answer creation, ranking, full reports suite.
 */
class OnlineExamResultService
{
    public function __construct(protected OnlineExamService $exams)
    {
    }

    public function exam(int $examId): OnlineExam
    {
        return $this->exams->find($examId);
    }

    /**
     * Assigned students with attempt/result status for an exam.
     *
     * @return Collection<int, object>
     */
    public function assignedStudents(int $examId): Collection
    {
        return DB::table('onlineexam_students')
            ->join('student_session', 'student_session.id', '=', 'onlineexam_students.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('onlineexam_students.onlineexam_id', $examId)
            ->where('students.is_active', 'yes')
            ->orderByDesc('onlineexam_students.is_attempted')
            ->orderBy('students.firstname')
            ->select([
                'onlineexam_students.id as onlineexam_student_id',
                'onlineexam_students.is_attempted',
                'onlineexam_students.rank',
                'onlineexam_students.student_session_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'classes.class',
                'sections.section',
                DB::raw('(select count(*) from onlineexam_attempts where onlineexam_attempts.onlineexam_student_id = onlineexam_students.id) as attempt_count'),
                DB::raw('(select count(*) from onlineexam_student_results where onlineexam_student_results.onlineexam_student_id = onlineexam_students.id) as result_count'),
            ])
            ->get();
    }

    /**
     * Descriptive questions attached to exam (for evaluation filter).
     *
     * @return Collection<int, object>
     */
    public function descriptiveQuestions(int $examId): Collection
    {
        return DB::table('onlineexam_questions')
            ->join('questions', 'questions.id', '=', 'onlineexam_questions.question_id')
            ->where('onlineexam_questions.onlineexam_id', $examId)
            ->where('questions.question_type', 'descriptive')
            ->orderBy('questions.id')
            ->select([
                'questions.id as question_id',
                'questions.question',
                'onlineexam_questions.id as onlineexam_question_id',
                'onlineexam_questions.marks',
            ])
            ->get();
    }

    /**
     * CI getDescriptionRecord — descriptive answer rows for grading.
     *
     * @param  array{class_id?:mixed,section_id?:mixed,question_id?:mixed}  $filters
     */
    public function descriptiveAnswers(int $examId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = DB::table('onlineexam_student_results')
            ->join('onlineexam_questions', 'onlineexam_questions.id', '=', 'onlineexam_student_results.onlineexam_question_id')
            ->join('questions', 'questions.id', '=', 'onlineexam_questions.question_id')
            ->join('onlineexam_students', 'onlineexam_students.id', '=', 'onlineexam_student_results.onlineexam_student_id')
            ->join('student_session', 'student_session.id', '=', 'onlineexam_students.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('questions.question_type', 'descriptive')
            ->where('onlineexam_questions.onlineexam_id', $examId)
            ->orderBy('onlineexam_student_results.id')
            ->select([
                'onlineexam_student_results.id',
                'onlineexam_student_results.select_option',
                'onlineexam_student_results.marks',
                'onlineexam_student_results.remark',
                'onlineexam_student_results.attachment_name',
                'onlineexam_student_results.attachment_upload_name',
                'onlineexam_questions.marks as question_marks',
                'questions.id as question_id',
                'questions.question',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'students.mobileno',
                'students.guardian_name',
                'students.guardian_phone',
                'classes.class',
                'sections.section',
            ]);

        if (! empty($filters['class_id'])) {
            $query->where('student_session.class_id', (int) $filters['class_id']);
        }
        if (! empty($filters['section_id'])) {
            $query->where('student_session.section_id', (int) $filters['section_id']);
        }
        if (! empty($filters['question_id'])) {
            $query->where('questions.id', (int) $filters['question_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * CI getResultByStudent — exam questions left-joined to student answers.
     *
     * @return Collection<int, object>
     */
    public function resultRows(int $onlineexamStudentId, int $examId): Collection
    {
        return DB::table('onlineexam_questions')
            ->join('questions', 'questions.id', '=', 'onlineexam_questions.question_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->leftJoin('onlineexam_student_results', function ($join) use ($onlineexamStudentId) {
                $join->on('onlineexam_student_results.onlineexam_question_id', '=', 'onlineexam_questions.id')
                    ->where('onlineexam_student_results.onlineexam_student_id', '=', $onlineexamStudentId);
            })
            ->where('onlineexam_questions.onlineexam_id', $examId)
            ->orderBy('onlineexam_questions.id')
            ->select([
                'onlineexam_questions.id as onlineexam_question_id',
                'onlineexam_questions.marks',
                'onlineexam_questions.neg_marks',
                'questions.question',
                'questions.question_type',
                'questions.opt_a',
                'questions.opt_b',
                'questions.opt_c',
                'questions.opt_d',
                'questions.opt_e',
                'questions.correct',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'onlineexam_student_results.id as onlineexam_student_result_id',
                'onlineexam_student_results.marks as score_marks',
                'onlineexam_student_results.select_option',
                'onlineexam_student_results.remark',
                'onlineexam_student_results.attachment_name',
                'onlineexam_student_results.attachment_upload_name',
            ])
            ->get();
    }

    public function findAssignedStudent(int $examId, int $onlineexamStudentId): object
    {
        $row = DB::table('onlineexam_students')
            ->join('student_session', 'student_session.id', '=', 'onlineexam_students.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('onlineexam_students.id', $onlineexamStudentId)
            ->where('onlineexam_students.onlineexam_id', $examId)
            ->select([
                'onlineexam_students.*',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'classes.class',
                'sections.section',
                'student_session.id as student_session_id',
            ])
            ->first();

        abort_unless($row, 404);

        return $row;
    }

    /**
     * CI _getstudentresult scoring (getMarks + neg marking).
     *
     * @param  Collection<int, object>  $rows
     * @return array{
     *   total_question:int,
     *   correct_ans:int,
     *   wrong_ans:int,
     *   not_attempted:int,
     *   exam_total_marks:float,
     *   exam_total_scored:float,
     *   exam_total_neg_marks:float,
     *   exam_total_descriptive:int,
     *   score_percent:float,
     *   rows:list<array{row:object,get_marks:float,scr_marks:float}>
     * }
     */
    public function scoreSummary(OnlineExam $exam, Collection $rows): array
    {
        $correct = 0;
        $wrong = 0;
        $notAttempted = 0;
        $totalMarks = 0.0;
        $scored = 0.0;
        $negMarks = 0.0;
        $descriptive = 0;
        $scoredRows = [];

        foreach ($rows as $row) {
            $getMarks = (float) $row->marks;
            $scrMarks = $this->scoredMarksForQuestion($row);
            $totalMarks += $getMarks;
            $scored += $scrMarks;

            if ($row->question_type === 'descriptive') {
                $descriptive++;
            }

            $selected = $row->select_option;
            if ($selected === null || $selected === '') {
                $negMarks += (float) $row->neg_marks;
                $notAttempted++;
            } elseif (in_array($row->question_type, ['singlechoice', 'true_false'], true)) {
                if ((string) $selected === (string) $row->correct) {
                    $correct++;
                } else {
                    $negMarks += (float) $row->neg_marks;
                    $wrong++;
                }
            } elseif ($row->question_type === 'multichoice') {
                if ($this->arraysEqualJson($row->correct, $selected)) {
                    $correct++;
                } else {
                    $negMarks += (float) $row->neg_marks;
                    $wrong++;
                }
            }

            $scoredRows[] = [
                'row' => $row,
                'get_marks' => $getMarks,
                'scr_marks' => $scrMarks,
            ];
        }

        if (! (int) $exam->is_neg_marking) {
            $negMarks = 0.0;
        }

        $net = $scored - $negMarks;
        $percent = $totalMarks > 0 ? round(($net * 100) / $totalMarks, 2) : 0.0;

        return [
            'total_question' => $rows->count(),
            'correct_ans' => $correct,
            'wrong_ans' => $wrong,
            'not_attempted' => $notAttempted,
            'exam_total_marks' => $totalMarks,
            'exam_total_scored' => $net,
            'exam_total_neg_marks' => $negMarks,
            'exam_total_descriptive' => $descriptive,
            'score_percent' => $percent,
            'rows' => $scoredRows,
        ];
    }

    public function fillMarks(int $resultId, float $marks, ?string $remark, float $questionMarks): void
    {
        if ($marks < 0 || $marks > $questionMarks) {
            throw ValidationException::withMessages([
                'fill_mark' => 'Marks must be between 0 and '.$questionMarks.'.',
            ]);
        }

        $updated = DB::table('onlineexam_student_results')
            ->where('id', $resultId)
            ->update([
                'marks' => $marks,
                'remark' => (string) ($remark ?? ''),
            ]);

        abort_unless($updated > 0, 404);
    }

    public function downloadAttachment(string $filename): BinaryFileResponse
    {
        $safe = basename($filename);
        abort_unless($safe !== '' && $safe === $filename && ! str_contains($safe, '..'), 404);

        $path = public_path('uploads/onlinexam_images/'.$safe);
        abort_unless(is_file($path), 404);

        return response()->download($path, $safe);
    }

    protected function scoredMarksForQuestion(object $question): float
    {
        $selected = $question->select_option;
        if ($selected === null || $selected === '') {
            return 0.0;
        }

        if (in_array($question->question_type, ['singlechoice', 'true_false'], true)) {
            return (string) $selected === (string) $question->correct
                ? (float) $question->marks
                : 0.0;
        }

        if ($question->question_type === 'multichoice') {
            return $this->arraysEqualJson($question->correct, $selected)
                ? (float) $question->marks
                : 0.0;
        }

        if ($question->question_type === 'descriptive') {
            return (float) ($question->score_marks ?? 0);
        }

        return 0.0;
    }

    protected function arraysEqualJson(?string $a, ?string $b): bool
    {
        $left = json_decode((string) $a, true);
        $right = json_decode((string) $b, true);
        if (! is_array($left) || ! is_array($right)) {
            return false;
        }

        sort($left);
        sort($right);

        return $left === $right;
    }
}
