<?php

namespace App\Modules\Exams\Services;

use App\Modules\Exams\Models\ExamGroup;
use App\Modules\Exams\Models\ExamGroupExam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Examgroup_model — exam groups + exams within a group.
 * Deferred: subjects, student assign, marks, link exams, marksheet publish SMS.
 */
class ExamGroupService
{
    /**
     * CI config exam_type keys => labels.
     *
     * @return array<string, string>
     */
    public function examTypes(): array
    {
        return [
            'basic_system' => 'Basic System',
            'school_grade_system' => 'School Grade System',
            'coll_grade_system' => 'College Grade System',
            'gpa' => 'GPA Grading System',
            'average_passing' => 'Average Passing',
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function listGroups(): Collection
    {
        return DB::table('exam_groups')
            ->select([
                'exam_groups.*',
                DB::raw('(select count(*) from exam_group_class_batch_exams where exam_group_class_batch_exams.exam_group_id = exam_groups.id) as counter'),
            ])
            ->orderBy('exam_groups.id')
            ->get();
    }

    public function findGroup(int $id): ExamGroup
    {
        return ExamGroup::query()->findOrFail($id);
    }

    /**
     * @return Collection<int, object>
     */
    public function examsForGroup(int $examGroupId): Collection
    {
        return DB::table('exam_group_class_batch_exams')
            ->join('sessions', 'sessions.id', '=', 'exam_group_class_batch_exams.session_id')
            ->where('exam_group_class_batch_exams.exam_group_id', $examGroupId)
            ->orderByDesc('exam_group_class_batch_exams.id')
            ->select([
                'exam_group_class_batch_exams.*',
                'sessions.session',
                DB::raw('(select count(*) from exam_group_class_batch_exam_subjects where exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id = exam_group_class_batch_exams.id) as total_subjects'),
            ])
            ->get();
    }

    public function findExam(int $id): ExamGroupExam
    {
        return ExamGroupExam::query()->findOrFail($id);
    }

    public function createGroup(array $data): ExamGroup
    {
        return ExamGroup::query()->create([
            'name' => $data['name'],
            'exam_type' => $data['exam_type'],
            'description' => $data['description'] ?? '',
            'is_active' => 0,
        ]);
    }

    public function updateGroup(ExamGroup $group, array $data): ExamGroup
    {
        $group->fill([
            'name' => $data['name'],
            'exam_type' => $data['exam_type'],
            'description' => $data['description'] ?? '',
            'is_active' => 0,
        ]);
        $group->save();

        return $group;
    }

    public function deleteGroup(ExamGroup $group): void
    {
        DB::transaction(function () use ($group) {
            $examIds = ExamGroupExam::query()
                ->where('exam_group_id', $group->id)
                ->pluck('id');

            if ($examIds->isNotEmpty()) {
                DB::table('exam_group_class_batch_exam_subjects')
                    ->whereIn('exam_group_class_batch_exams_id', $examIds)
                    ->delete();
                ExamGroupExam::query()->whereIn('id', $examIds)->delete();
            }

            $group->delete();
        });
    }

    public function saveExam(array $data, ?int $examId = null): ExamGroupExam
    {
        $payload = [
            'exam' => $data['exam'],
            'exam_group_id' => (int) $data['exam_group_id'],
            'session_id' => (int) $data['session_id'],
            'description' => $data['description'] ?? '',
            'use_exam_roll_no' => (int) ($data['use_exam_roll_no'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 0),
            'is_publish' => (int) ($data['is_publish'] ?? 0),
            'passing_percentage' => $data['passing_percentage'] ?? null,
        ];

        if ($examId) {
            $exam = ExamGroupExam::query()->findOrFail($examId);
            $exam->fill($payload);
            $exam->save();

            return $exam;
        }

        $payload['is_rank_generated'] = 0;

        return ExamGroupExam::query()->create($payload);
    }

    public function deleteExam(ExamGroupExam $exam): void
    {
        DB::transaction(function () use ($exam) {
            DB::table('exam_group_class_batch_exam_subjects')
                ->where('exam_group_class_batch_exams_id', $exam->id)
                ->delete();
            $exam->delete();
        });
    }
}
