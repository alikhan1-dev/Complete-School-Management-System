<?php

namespace App\Modules\Exams\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Exams\Models\ExamGroup;
use App\Modules\Exams\Models\ExamGroupExam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Examgroupstudent_model / Examstudent_model — assign students to exam group
 * and to a batch exam (exam_group_students / exam_group_class_batch_exam_students).
 */
class ExamAssignService
{
    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * CI searchExamGroupStudents — roster for an exam group.
     *
     * @return Collection<int, object>
     */
    public function searchGroupStudents(int $examGroupId, int $classId, int $sectionId, int $sessionId): Collection
    {
        return DB::table('student_session')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->leftJoin('exam_group_students', function ($join) use ($examGroupId) {
                $join->on('exam_group_students.student_id', '=', 'students.id')
                    ->where('exam_group_students.exam_group_id', '=', $examGroupId);
            })
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.id')
            ->select([
                'students.id as student_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.gender',
                'classes.class',
                'sections.section',
                'student_session.id as student_session_id',
                DB::raw("IFNULL(categories.category, '') as category"),
                DB::raw('IFNULL(exam_group_students.id, 0) as exam_group_student_id'),
            ])
            ->get();
    }

    /**
     * CI Examstudent_model::searchExamStudents — students for a batch exam.
     *
     * @return Collection<int, object>
     */
    public function searchExamStudents(int $examId, int $classId, int $sectionId): Collection
    {
        $sessionId = $this->currentSession->id();

        return DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->leftJoin('exam_group_class_batch_exam_students', function ($join) use ($examId) {
                $join->on('exam_group_class_batch_exam_students.student_session_id', '=', 'student_session.id')
                    ->where('exam_group_class_batch_exam_students.exam_group_class_batch_exam_id', '=', $examId);
            })
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.admission_no')
            ->select([
                'students.id as student_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.gender',
                'classes.class',
                'sections.section',
                'student_session.id as student_session_id',
                DB::raw("IFNULL(categories.category, '') as category"),
                DB::raw('IFNULL(exam_group_class_batch_exam_students.id, 0) as exam_student_id'),
            ])
            ->get();
    }

    /**
     * @param  list<int|string>  $checkedStudentIds
     * @param  list<int|string>  $allStudentIds
     * @param  array<int, int>  $studentSessionByStudentId  student_id => student_session_id
     */
    public function syncGroupStudents(
        int $examGroupId,
        array $checkedStudentIds,
        array $allStudentIds,
        array $studentSessionByStudentId
    ): void {
        $checked = array_values(array_unique(array_map('intval', $checkedStudentIds)));
        $all = array_values(array_unique(array_map('intval', $allStudentIds)));
        $toDelete = array_values(array_diff($all, $checked));

        DB::transaction(function () use ($examGroupId, $checked, $toDelete, $studentSessionByStudentId) {
            foreach ($checked as $studentId) {
                $exists = DB::table('exam_group_students')
                    ->where('exam_group_id', $examGroupId)
                    ->where('student_id', $studentId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('exam_group_students')->insert([
                    'exam_group_id' => $examGroupId,
                    'student_id' => $studentId,
                    'student_session_id' => (int) ($studentSessionByStudentId[$studentId] ?? 0),
                    'is_active' => 0,
                ]);
            }

            if ($toDelete !== []) {
                DB::table('exam_group_students')
                    ->where('exam_group_id', $examGroupId)
                    ->whereIn('student_id', $toDelete)
                    ->delete();
            }
        });
    }

    /**
     * CI Examstudent_model::add_student
     *
     * @param  list<int|string>  $checkedStudentSessionIds
     * @param  list<int|string>  $allStudentSessionIds
     * @param  array<int, int>  $studentIdBySession  student_session_id => student_id
     */
    public function syncExamStudents(
        int $examId,
        array $checkedStudentSessionIds,
        array $allStudentSessionIds,
        array $studentIdBySession
    ): void {
        $checked = array_values(array_unique(array_map('intval', $checkedStudentSessionIds)));
        $all = array_values(array_unique(array_map('intval', $allStudentSessionIds)));
        $toDelete = array_values(array_diff($all, $checked));

        DB::transaction(function () use ($examId, $checked, $toDelete, $studentIdBySession) {
            foreach ($checked as $studentSessionId) {
                $exists = DB::table('exam_group_class_batch_exam_students')
                    ->where('exam_group_class_batch_exam_id', $examId)
                    ->where('student_session_id', $studentSessionId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('exam_group_class_batch_exam_students')->insert([
                    'exam_group_class_batch_exam_id' => $examId,
                    'student_id' => (int) ($studentIdBySession[$studentSessionId] ?? 0),
                    'student_session_id' => $studentSessionId,
                    'rank' => 0,
                    'is_active' => 0,
                ]);
            }

            if ($toDelete !== []) {
                $examStudentIds = DB::table('exam_group_class_batch_exam_students')
                    ->where('exam_group_class_batch_exam_id', $examId)
                    ->whereIn('student_session_id', $toDelete)
                    ->pluck('id');

                if ($examStudentIds->isNotEmpty()) {
                    DB::table('exam_group_exam_results')
                        ->whereIn('exam_group_class_batch_exam_student_id', $examStudentIds)
                        ->delete();
                }

                DB::table('exam_group_class_batch_exam_students')
                    ->where('exam_group_class_batch_exam_id', $examId)
                    ->whereIn('student_session_id', $toDelete)
                    ->delete();
            }
        });
    }

    public function examsForGroup(ExamGroup $group): Collection
    {
        return ExamGroupExam::query()
            ->where('exam_group_id', $group->id)
            ->orderBy('id')
            ->get(['id', 'exam', 'date_from', 'date_to', 'session_id']);
    }
}
