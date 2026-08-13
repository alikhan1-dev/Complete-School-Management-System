<?php

namespace App\Modules\Homework\Services;

use App\Modules\Homework\Models\Homework;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Homework evaluation / add_evaluation / assigmnetDownload.
 * Deferred: mail/SMS, evaluation reports, DataTable homework_docs.
 */
class HomeworkEvaluationService
{
    public function __construct(
        protected HomeworkService $homework,
        protected HomeworkDocumentService $documents,
    ) {
    }

    /**
     * @return array{homework:object,students:Collection<int,object>,maxMarks:float,hasMaxMarks:bool}
     */
    public function evaluationPayload(int $homeworkId): array
    {
        $detail = $this->homework->findDetailed($homeworkId);
        $students = $this->studentsForHomework($homeworkId);

        $maxMarks = (float) ($detail->marks ?? 0);
        $hasMaxMarks = $detail->marks !== null && $detail->marks !== '' && $maxMarks > 0;

        return [
            'homework' => $detail,
            'students' => $students,
            'maxMarks' => $maxMarks,
            'hasMaxMarks' => $hasMaxMarks,
        ];
    }

    /**
     * @param  array<string, mixed>  $studentList  session_id => evaluation_id (0 = new)
     * @param  array<string, mixed>  $studentIds
     * @param  array<string, mixed>  $marks
     * @param  array<string, mixed>  $notes
     */
    public function save(
        int $homeworkId,
        string $evaluationDate,
        array $studentList,
        array $studentIds,
        array $marks,
        array $notes
    ): void {
        $homework = $this->homework->find($homeworkId);
        $staffId = (int) (Auth::guard('staff')->id() ?? 0);
        abort_unless($staffId > 0, 403);

        if ($studentList === []) {
            throw ValidationException::withMessages([
                'student_list' => 'Please select at least one student.',
            ]);
        }

        $maxMarks = $homework->marks;
        $hasMaxMarks = $maxMarks !== null && $maxMarks !== '' && (float) $maxMarks > 0;
        $max = (float) $maxMarks;

        $keepIds = [];
        $inserts = [];
        $updates = [];

        foreach ($studentList as $sessionId => $evaluationId) {
            $sessionId = (int) $sessionId;
            $evaluationId = (int) $evaluationId;
            $studentId = (int) ($studentIds[$sessionId] ?? $studentIds[(string) $sessionId] ?? 0);

            if ($sessionId <= 0 || $studentId <= 0) {
                continue;
            }

            // Ensure student belongs to this homework class/section/session
            $belongs = DB::table('student_session')
                ->where('id', $sessionId)
                ->where('student_id', $studentId)
                ->where('class_id', (int) $homework->class_id)
                ->where('section_id', (int) $homework->section_id)
                ->where('session_id', (int) $homework->session_id)
                ->exists();
            if (! $belongs) {
                continue;
            }

            $rawMark = $marks[$sessionId] ?? ($marks[(string) $sessionId] ?? null);
            $newMarks = null;
            if ($hasMaxMarks && $rawMark !== null && $rawMark !== '') {
                $newMarks = (float) $rawMark;
                if ($newMarks > $max || $newMarks < 0) {
                    throw ValidationException::withMessages([
                        "marks.{$sessionId}" => "Marks must be between 0 and {$max}.",
                    ]);
                }
            }

            $note = (string) ($notes[$sessionId] ?? $notes[(string) $sessionId] ?? '');

            if ($evaluationId <= 0) {
                $inserts[] = [
                    'homework_id' => $homeworkId,
                    'student_session_id' => $sessionId,
                    'student_id' => $studentId,
                    'note' => $note,
                    'marks' => $newMarks,
                    'date' => $evaluationDate,
                    'status' => 'Complete',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
                $keepIds[] = $evaluationId;
                $updates[$evaluationId] = [
                    'note' => $note,
                    'marks' => $newMarks,
                    'updated_at' => now(),
                ];
            }
        }

        if ($inserts === [] && $updates === []) {
            throw ValidationException::withMessages([
                'student_list' => 'Please select at least one valid student.',
            ]);
        }

        DB::transaction(function () use ($homeworkId, $evaluationDate, $staffId, $inserts, $updates, &$keepIds) {
            DB::table('homework')->where('id', $homeworkId)->update([
                'evaluation_date' => $evaluationDate,
                'evaluated_by' => $staffId,
                'updated_at' => now(),
            ]);

            foreach ($inserts as $row) {
                $keepIds[] = (int) DB::table('homework_evaluation')->insertGetId($row);
            }

            foreach ($updates as $id => $payload) {
                DB::table('homework_evaluation')->where('id', $id)->where('homework_id', $homeworkId)->update($payload);
            }

            $deleteQuery = DB::table('homework_evaluation')->where('homework_id', $homeworkId);
            if ($keepIds !== []) {
                $deleteQuery->whereNotIn('id', $keepIds);
            }
            $deleteQuery->delete();
        });
    }

    public function downloadAssignment(int $submitAssignmentId): BinaryFileResponse
    {
        $row = DB::table('submit_assignment')->where('id', $submitAssignmentId)->first();
        abort_unless($row !== null, 404);

        $docs = (string) ($row->docs ?? '');
        abort_unless($docs !== '', 404);

        // Ensure homework is in current session
        $this->homework->find((int) $row->homework_id);

        return $this->documents->downloadAssignment($docs);
    }

    /**
     * @return Collection<int, object>
     */
    protected function studentsForHomework(int $homeworkId): Collection
    {
        $rows = DB::table('student_session')
            ->join('homework', function ($join) use ($homeworkId) {
                $join->on('homework.class_id', '=', 'student_session.class_id')
                    ->on('homework.section_id', '=', 'student_session.section_id')
                    ->on('homework.session_id', '=', 'student_session.session_id')
                    ->where('homework.id', '=', $homeworkId);
            })
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('homework_evaluation', function ($join) use ($homeworkId) {
                $join->on('homework_evaluation.student_session_id', '=', 'student_session.id')
                    ->where('homework_evaluation.homework_id', '=', $homeworkId);
            })
            ->where('students.is_active', 'yes')
            ->orderByDesc('students.id')
            ->select([
                'student_session.id as student_session_id',
                'student_session.student_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                DB::raw('IFNULL(homework_evaluation.id, 0) as homework_evaluation_id'),
                'homework_evaluation.note',
                'homework_evaluation.marks as evaluation_marks',
            ])
            ->get();

        $studentIds = $rows->pluck('student_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $assignments = collect();
        if ($studentIds !== []) {
            $assignments = DB::table('submit_assignment')
                ->where('homework_id', $homeworkId)
                ->whereIn('student_id', $studentIds)
                ->select(['id', 'student_id', 'docs', 'message', 'file_name'])
                ->get()
                ->groupBy('student_id');
        }

        return $rows->map(function (object $row) use ($assignments) {
            $row->assignments = $assignments->get((int) $row->student_id, collect());

            return $row;
        });
    }
}
