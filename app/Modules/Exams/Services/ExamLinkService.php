<?php

namespace App\Modules\Exams\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Examgroup_model connectExam / verifyExamConnection / deleteExamGroupConnection —
 * link exams within a group with weightages (sum 100) and matching subject sets.
 *
 * Publish exam/result flags live on exam CRUD (is_publish / is_active).
 * Deferred: marksheet selection + SMS on publish.
 */
class ExamLinkService
{
    /**
     * CI getExamByExamGroupConnection
     *
     * @return Collection<int, object>
     */
    public function examsForLink(int $examGroupId): Collection
    {
        return DB::table('exam_group_class_batch_exams')
            ->leftJoin('exam_group_exam_connections', function ($join) {
                $join->on('exam_group_exam_connections.exam_group_id', '=', 'exam_group_class_batch_exams.exam_group_id')
                    ->on(
                        'exam_group_exam_connections.exam_group_class_batch_exams_id',
                        '=',
                        'exam_group_class_batch_exams.id'
                    );
            })
            ->where('exam_group_class_batch_exams.exam_group_id', $examGroupId)
            ->orderBy('exam_group_class_batch_exams.id')
            ->select([
                'exam_group_class_batch_exams.id',
                'exam_group_class_batch_exams.exam',
                'exam_group_class_batch_exams.session_id',
                DB::raw('IFNULL(exam_group_exam_connections.id, 0) as exam_group_exam_connection_id'),
                DB::raw('IFNULL(exam_group_exam_connections.exam_weightage, "0.00") as exam_weightage'),
                DB::raw('(select count(*) from exam_group_class_batch_exam_subjects where exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id = exam_group_class_batch_exams.id) as total_subjects'),
            ])
            ->get();
    }

    /**
     * @param  array<int|string, float|int|string>  $weightageByExamId  checked exam id => weightage
     */
    public function connectExams(int $examGroupId, array $weightageByExamId): void
    {
        $examIds = array_values(array_unique(array_map('intval', array_keys($weightageByExamId))));

        if ($examIds === []) {
            throw ValidationException::withMessages([
                'exam' => 'No exams selected.',
            ]);
        }

        if (count($examIds) <= 1) {
            throw ValidationException::withMessages([
                'exam' => 'Please select at least two or more exams.',
            ]);
        }

        $owned = DB::table('exam_group_class_batch_exams')
            ->where('exam_group_id', $examGroupId)
            ->whereIn('id', $examIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($owned) !== count($examIds)) {
            throw ValidationException::withMessages([
                'exam' => 'One or more selected exams do not belong to this exam group.',
            ]);
        }

        $totalWeightage = 0.0;
        foreach ($examIds as $examId) {
            $totalWeightage += (float) $weightageByExamId[$examId];
        }

        if (round($totalWeightage, 2) !== 100.0) {
            throw ValidationException::withMessages([
                'exam_weightage' => 'Exam weightage must be equal to 100.',
            ]);
        }

        $this->assertMatchingSubjects($examIds);

        DB::transaction(function () use ($examGroupId, $examIds, $weightageByExamId) {
            $keptExamIds = [];

            foreach ($examIds as $examId) {
                $payload = [
                    'exam_group_id' => $examGroupId,
                    'exam_group_class_batch_exams_id' => $examId,
                    'exam_weightage' => round((float) $weightageByExamId[$examId], 2),
                    'is_active' => 0,
                ];

                $existingId = DB::table('exam_group_exam_connections')
                    ->where('exam_group_id', $examGroupId)
                    ->where('exam_group_class_batch_exams_id', $examId)
                    ->value('id');

                if ($existingId) {
                    DB::table('exam_group_exam_connections')->where('id', $existingId)->update($payload);
                } else {
                    DB::table('exam_group_exam_connections')->insert($payload);
                }

                $keptExamIds[] = $examId;
            }

            DB::table('exam_group_exam_connections')
                ->where('exam_group_id', $examGroupId)
                ->whereNotIn('exam_group_class_batch_exams_id', $keptExamIds)
                ->delete();
        });
    }

    public function resetConnections(int $examGroupId): void
    {
        DB::table('exam_group_exam_connections')
            ->where('exam_group_id', $examGroupId)
            ->delete();
    }

    /**
     * CI verifyExamConnection + subject set comparison.
     *
     * @param  list<int>  $examIds
     */
    protected function assertMatchingSubjects(array $examIds): void
    {
        $rows = DB::table('exam_group_class_batch_exam_subjects')
            ->whereIn('exam_group_class_batch_exams_id', $examIds)
            ->select([
                'exam_group_class_batch_exams_id',
                'subject_id',
                DB::raw('count(subject_id) as subject_count'),
            ])
            ->groupBy('exam_group_class_batch_exams_id', 'subject_id')
            ->get();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'exam' => 'Exam subjects may be empty. Please check exam subjects.',
            ]);
        }

        /** @var array<int, array<int, int>> $byExam */
        $byExam = [];
        foreach ($rows as $row) {
            $examId = (int) $row->exam_group_class_batch_exams_id;
            $subjectId = (int) $row->subject_id;
            $byExam[$examId][$subjectId] = (int) $row->subject_count;
        }

        if (count($byExam) !== count($examIds)) {
            throw ValidationException::withMessages([
                'exam' => 'Please check exam subjects.',
            ]);
        }

        $referenceExamId = $examIds[0];
        $reference = $byExam[$referenceExamId] ?? [];
        ksort($reference);

        foreach ($examIds as $examId) {
            $current = $byExam[$examId] ?? [];
            ksort($current);

            if ($current !== $reference) {
                throw ValidationException::withMessages([
                    'exam' => 'Please check exam subjects. Linked exams must share the same subjects.',
                ]);
            }
        }
    }
}
