<?php

namespace App\Modules\Exams\Services;

use App\Modules\Exams\Models\ExamGroupExamSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Examgroupstudent_model::examGroupSubjectResult + add_result —
 * marks entry for assigned exam students on a subject.
 */
class ExamMarksService
{
    /**
     * CI attendence_exam config keys.
     *
     * @return list<string>
     */
    public function attendanceOptions(): array
    {
        return ['absent'];
    }

    public function findExamSubject(int $examSubjectId): object
    {
        $row = DB::table('exam_group_class_batch_exam_subjects')
            ->join('subjects', 'subjects.id', '=', 'exam_group_class_batch_exam_subjects.subject_id')
            ->where('exam_group_class_batch_exam_subjects.id', $examSubjectId)
            ->select([
                'exam_group_class_batch_exam_subjects.*',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
            ])
            ->first();

        abort_unless($row, 404);

        return $row;
    }

    /**
     * Subjects attached to an exam (for dropdown).
     *
     * @return Collection<int, object>
     */
    public function subjectsForExam(int $examId): Collection
    {
        return DB::table('exam_group_class_batch_exam_subjects')
            ->join('subjects', 'subjects.id', '=', 'exam_group_class_batch_exam_subjects.subject_id')
            ->where('exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id', $examId)
            ->orderBy('exam_group_class_batch_exam_subjects.id')
            ->select([
                'exam_group_class_batch_exam_subjects.id',
                'exam_group_class_batch_exam_subjects.max_marks',
                'exam_group_class_batch_exam_subjects.min_marks',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
            ])
            ->get();
    }

    /**
     * CI examGroupSubjectResult
     *
     * @return Collection<int, object>
     */
    public function studentsForSubjectMarks(
        int $examSubjectId,
        int $classId,
        int $sectionId,
        int $sessionId
    ): Collection {
        return DB::table('exam_group_class_batch_exam_subjects')
            ->join(
                'exam_group_class_batch_exams',
                'exam_group_class_batch_exams.id',
                '=',
                'exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id'
            )
            ->join('subjects', 'subjects.id', '=', 'exam_group_class_batch_exam_subjects.subject_id')
            ->join(
                'exam_group_class_batch_exam_students',
                'exam_group_class_batch_exam_students.exam_group_class_batch_exam_id',
                '=',
                'exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id'
            )
            ->join(
                'student_session',
                'student_session.id',
                '=',
                'exam_group_class_batch_exam_students.student_session_id'
            )
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->leftJoin('exam_group_exam_results', function ($join) {
                $join->on(
                    'exam_group_exam_results.exam_group_class_batch_exam_subject_id',
                    '=',
                    'exam_group_class_batch_exam_subjects.id'
                )->on(
                    'exam_group_exam_results.exam_group_class_batch_exam_student_id',
                    '=',
                    'exam_group_class_batch_exam_students.id'
                );
            })
            ->where('exam_group_class_batch_exam_subjects.id', $examSubjectId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->orderByRaw('CAST(students.admission_no AS UNSIGNED) asc')
            ->select([
                'exam_group_class_batch_exam_students.id as exam_group_class_batch_exam_students_id',
                'exam_group_class_batch_exam_students.roll_no as exam_roll_no',
                'exam_group_class_batch_exams.use_exam_roll_no',
                'students.id as student_id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.gender',
                DB::raw("IFNULL(categories.category, '') as category"),
                DB::raw('IFNULL(exam_group_exam_results.id, 0) as exam_group_exam_result_id'),
                DB::raw("IFNULL(exam_group_exam_results.attendence, '') as exam_group_exam_result_attendance"),
                DB::raw("IFNULL(exam_group_exam_results.get_marks, '') as exam_group_exam_result_get_marks"),
                DB::raw("IFNULL(exam_group_exam_results.note, '') as exam_group_exam_result_note"),
            ])
            ->get();
    }

    /**
     * CI add_result — upsert marks per assigned exam student.
     *
     * @param  list<int|string>  $examStudentIds
     * @param  array<string, mixed>  $input
     */
    public function saveMarks(int $examSubjectId, array $examStudentIds, array $input): void
    {
        $subject = ExamGroupExamSubject::query()->findOrFail($examSubjectId);
        $maxMarks = (float) $subject->max_marks;

        DB::transaction(function () use ($examSubjectId, $examStudentIds, $input, $maxMarks) {
            foreach ($examStudentIds as $examStudentId) {
                $examStudentId = (int) $examStudentId;
                $attendanceKey = 'exam_group_student_attendance_'.$examStudentId;
                $markKey = 'exam_group_student_mark_'.$examStudentId;
                $noteKey = 'exam_group_student_note_'.$examStudentId;

                $attendance = isset($input[$attendanceKey]) && $input[$attendanceKey] !== ''
                    ? (string) $input[$attendanceKey]
                    : 'present';

                $rawMarks = $input[$markKey] ?? null;
                if ($attendance === 'absent') {
                    $marks = 0;
                } elseif ($rawMarks === null || $rawMarks === '') {
                    $marks = 0;
                } else {
                    $marks = (float) $rawMarks;
                    if ($marks < 0 || $marks > $maxMarks) {
                        throw ValidationException::withMessages([
                            $markKey => 'Marks must be between 0 and '.$maxMarks.'.',
                        ]);
                    }
                }

                $payload = [
                    'exam_group_class_batch_exam_subject_id' => $examSubjectId,
                    'exam_group_class_batch_exam_student_id' => $examStudentId,
                    'attendence' => $attendance,
                    'get_marks' => $marks,
                    'note' => (string) ($input[$noteKey] ?? ''),
                    'is_active' => 0,
                ];

                $existingId = DB::table('exam_group_exam_results')
                    ->where('exam_group_class_batch_exam_subject_id', $examSubjectId)
                    ->where('exam_group_class_batch_exam_student_id', $examStudentId)
                    ->value('id');

                if ($existingId) {
                    DB::table('exam_group_exam_results')->where('id', $existingId)->update($payload);
                } else {
                    DB::table('exam_group_exam_results')->insert($payload);
                }
            }
        });
    }
}
