<?php

namespace App\Modules\Exams\Services;

use App\Modules\Academics\Models\Subject;
use App\Modules\Exams\Models\ExamGroupExam;
use App\Modules\Exams\Models\ExamGroupExamSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Examsubject_model / Batchsubject_model::getExamSubjects —
 * subjects attached to an exam_group_class_batch_exams row.
 */
class ExamSubjectService
{
    /**
     * @return Collection<int, object>
     */
    public function subjectsForExam(int $examId): Collection
    {
        return DB::table('exam_group_class_batch_exam_subjects')
            ->join('subjects', 'subjects.id', '=', 'exam_group_class_batch_exam_subjects.subject_id')
            ->where('exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id', $examId)
            ->orderBy('exam_group_class_batch_exam_subjects.id')
            ->select([
                'exam_group_class_batch_exam_subjects.*',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'subjects.type as subject_type',
            ])
            ->get();
    }

    public function findSubject(int $id): ExamGroupExamSubject
    {
        return ExamGroupExamSubject::query()->findOrFail($id);
    }

    /**
     * @return Collection<int, Subject>
     */
    public function availableSubjects(): Collection
    {
        return Subject::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveSubject(ExamGroupExam $exam, array $data, ?int $subjectRowId = null): ExamGroupExamSubject
    {
        $subjectId = (int) $data['subject_id'];

        $duplicate = ExamGroupExamSubject::query()
            ->where('exam_group_class_batch_exams_id', $exam->id)
            ->where('subject_id', $subjectId)
            ->when($subjectRowId, fn ($q) => $q->where('id', '!=', $subjectRowId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'subject_id' => 'This subject is already added to the exam.',
            ]);
        }

        $payload = [
            'exam_group_class_batch_exams_id' => $exam->id,
            'subject_id' => $subjectId,
            'date_from' => $data['date_from'],
            'time_from' => $data['time_from'],
            'duration' => $data['duration'],
            'room_no' => $data['room_no'],
            'max_marks' => $data['max_marks'],
            'min_marks' => $data['min_marks'],
            'credit_hours' => $data['credit_hours'],
            'is_active' => 0,
        ];

        if ($subjectRowId) {
            $row = ExamGroupExamSubject::query()->findOrFail($subjectRowId);
            abort_unless((int) $row->exam_group_class_batch_exams_id === (int) $exam->id, 404);
            $row->fill($payload);
            $row->save();

            return $row;
        }

        return ExamGroupExamSubject::query()->create($payload);
    }

    public function deleteSubject(ExamGroupExamSubject $row): void
    {
        DB::transaction(function () use ($row) {
            DB::table('exam_group_exam_results')
                ->where('exam_group_class_batch_exam_subject_id', $row->id)
                ->delete();
            $row->delete();
        });
    }
}
