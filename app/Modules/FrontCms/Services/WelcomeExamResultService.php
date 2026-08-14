<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Academics\Support\ExamTypes;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\DB;

/**
 * CI Welcome::examresult + examstudent_model / examgroupstudent_model / examresult_model.
 */
class WelcomeExamResultService
{
    public function __construct(
        protected CurrentSessionResolver $session,
        protected SchoolContext $school,
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) $this->school->get('exam_result');
    }

    /**
     * CI examstudent_model::getstudentexam — current session only.
     *
     * @return list<array<string, mixed>>
     */
    public function studentExams(string $admissionNo): array
    {
        return DB::table('exam_group_class_batch_exam_students')
            ->join(
                'exam_group_class_batch_exams',
                'exam_group_class_batch_exams.id',
                '=',
                'exam_group_class_batch_exam_students.exam_group_class_batch_exam_id'
            )
            ->join('students', 'students.id', '=', 'exam_group_class_batch_exam_students.student_id')
            ->join('student_session', 'student_session.id', '=', 'exam_group_class_batch_exam_students.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('students.admission_no', $admissionNo)
            ->where('exam_group_class_batch_exams.session_id', $this->session->id())
            ->select([
                'exam_group_class_batch_exams.exam',
                'exam_group_class_batch_exams.passing_percentage',
                'exam_group_class_batch_exams.id',
                'exam_group_class_batch_exam_students.student_session_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.roll_no',
                'students.admission_no',
                'classes.class as class_name',
                'sections.section as section_name',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();
    }

    public function studentSessionIdByAdmissionNo(string $admissionNo): ?int
    {
        $id = DB::table('student_session')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('students.admission_no', $admissionNo)
            ->where('student_session.session_id', $this->session->id())
            ->value('student_session.id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * CI examgroupstudent_model::getexamresult($sessionId, $examId, true, true).
     *
     * @return list<object>
     */
    public function publishedExamResult(?int $studentSessionId, int $examId): array
    {
        if ($studentSessionId === null || $studentSessionId <= 0 || $examId <= 0) {
            return [];
        }

        $rows = DB::table('exam_group_class_batch_exam_students')
            ->join(
                'exam_group_class_batch_exams',
                'exam_group_class_batch_exams.id',
                '=',
                'exam_group_class_batch_exam_students.exam_group_class_batch_exam_id'
            )
            ->join('exam_groups', 'exam_groups.id', '=', 'exam_group_class_batch_exams.exam_group_id')
            ->where('exam_group_class_batch_exam_students.exam_group_class_batch_exam_id', $examId)
            ->where('exam_group_class_batch_exam_students.student_session_id', $studentSessionId)
            ->where('exam_group_class_batch_exams.is_active', 1)
            ->where('exam_group_class_batch_exams.is_publish', 1)
            ->orderBy('exam_group_class_batch_exam_students.id')
            ->select([
                'exam_group_class_batch_exam_students.*',
                'exam_group_class_batch_exams.exam_group_id',
                'exam_group_class_batch_exams.exam',
                'exam_group_class_batch_exams.passing_percentage',
                'exam_group_class_batch_exams.date_from',
                'exam_group_class_batch_exams.date_to',
                'exam_group_class_batch_exams.description',
                'exam_groups.name',
                'exam_groups.exam_type',
            ])
            ->get();

        $exams = [];
        foreach ($rows as $row) {
            $row->exam_result = $this->studentExamResults(
                (int) $row->exam_group_class_batch_exam_id,
                (int) $row->exam_group_id,
                (int) $row->id,
                (int) $row->student_id
            );
            $exams[] = $row;
        }

        return $exams;
    }

    /**
     * CI grade_model::getGradeDetails.
     *
     * @return list<array{exam_key:string,exm_type_value:string,exam_grade_values:list<object>}>
     */
    public function gradeDetails(): array
    {
        $grades = [];
        foreach (ExamTypes::options() as $key => $label) {
            $grades[] = [
                'exam_key' => $key,
                'exm_type_value' => $label,
                'exam_grade_values' => DB::table('grades')
                    ->where('exam_type', $key)
                    ->orderBy('id')
                    ->get()
                    ->all(),
            ];
        }

        return $grades;
    }

    /**
     * @return list<object>
     */
    public function marksDivisions(): array
    {
        return DB::table('mark_divisions')->orderBy('id')->get()->all();
    }

    public function studentDisplayName(array $student): string
    {
        $parts = [trim((string) ($student['firstname'] ?? ''))];
        if ((bool) $this->school->get('middlename') && filled($student['middlename'] ?? null)) {
            $parts[] = trim((string) $student['middlename']);
        }
        if ((bool) $this->school->get('lastname') && filled($student['lastname'] ?? null)) {
            $parts[] = trim((string) $student['lastname']);
        }

        return trim(implode(' ', array_filter($parts)));
    }

    /**
     * CI findGradePoints in themes/default/pages/examresult.php.
     *
     * @param  list<array<string, mixed>>  $examGrade
     */
    public function findGradePoints(array $examGrade, string $examType, float $percentage): float
    {
        foreach ($examGrade as $examGradeValue) {
            if (($examGradeValue['exam_key'] ?? '') !== $examType) {
                continue;
            }
            foreach ($examGradeValue['exam_grade_values'] ?? [] as $gradeValue) {
                if ((float) $gradeValue->mark_from >= $percentage && (float) $gradeValue->mark_upto <= $percentage) {
                    return (float) $gradeValue->point;
                }
            }
        }

        return 0.0;
    }

    /**
     * CI findExamGrade.
     *
     * @param  list<array<string, mixed>>  $examGrade
     */
    public function findExamGrade(array $examGrade, string $examType, float $percentage): string
    {
        foreach ($examGrade as $examGradeValue) {
            if (($examGradeValue['exam_key'] ?? '') !== $examType) {
                continue;
            }
            foreach ($examGradeValue['exam_grade_values'] ?? [] as $gradeValue) {
                if ((float) $gradeValue->mark_from >= $percentage && (float) $gradeValue->mark_upto <= $percentage) {
                    return (string) $gradeValue->name;
                }
            }
        }

        return '';
    }

    /**
     * CI findExamDivision (legacy comparison is inverted vs typical range checks).
     *
     * @param  list<object>  $marksDivision
     */
    public function findExamDivision(array $marksDivision, float $percentage): string
    {
        foreach ($marksDivision as $divisionValue) {
            if ((float) $divisionValue->percentage_from >= $percentage && (float) $divisionValue->percentage_to <= $percentage) {
                return (string) $divisionValue->name;
            }
        }

        return '';
    }

    /**
     * CI examresult_model::getStudentExamResults.
     *
     * @return array<string, mixed>
     */
    protected function studentExamResults(
        int $examId,
        int $examGroupId,
        int $examStudentId,
        int $studentId
    ): array {
        $student = $this->examStudentById($examStudentId);
        $result = [
            'student' => $student,
            'exam_connection' => 0,
            'result' => [],
            'exams' => [],
            'exam_connection_list' => [],
        ];

        $connections = DB::table('exam_group_exam_connections')
            ->where('exam_group_id', $examGroupId)
            ->orderBy('id')
            ->get()
            ->all();
        $result['exam_connection_list'] = $connections;

        $examConnection = false;
        if ($connections !== []) {
            $last = $connections[array_key_last($connections)];
            if ((int) $last->exam_group_class_batch_exams_id === $examId) {
                $examConnection = true;
                $result['exam_connection'] = 1;
            }
        }

        if ($examConnection) {
            foreach ($connections as $connection) {
                $connectedExamId = (int) $connection->exam_group_class_batch_exams_id;
                $examStudent = DB::table('exam_group_class_batch_exam_students')
                    ->where('student_id', $studentId)
                    ->where('exam_group_class_batch_exam_id', $connectedExamId)
                    ->first();
                $exam = $this->examById($connectedExamId);
                if ($examStudent !== null) {
                    $result['exam_result']['exam_roll_no_'.$connectedExamId] = $student['roll_no'] ?? '';
                    $result['exam_result']['exam_result_'.$connectedExamId] = $this->studentResultByExam(
                        $connectedExamId,
                        (int) $examStudent->id
                    );
                }
                $result['exams']['exam_'.$connectedExamId] = $exam;
            }

            return $result;
        }

        $result['student']['exam_roll_no'] = $student['roll_no'] ?? '';
        $result['result'] = $this->studentResultByExam($examId, $examStudentId);

        return $result;
    }

    /**
     * CI examresult_model::getStudentResultByExam (inner join — subjects without marks omitted).
     *
     * @return list<object>
     */
    protected function studentResultByExam(int $examId, int $examStudentId): array
    {
        return DB::table('exam_group_class_batch_exam_subjects')
            ->join(
                'exam_group_exam_results',
                'exam_group_exam_results.exam_group_class_batch_exam_subject_id',
                '=',
                'exam_group_class_batch_exam_subjects.id'
            )
            ->join(
                'exam_group_class_batch_exam_students',
                'exam_group_exam_results.exam_group_class_batch_exam_student_id',
                '=',
                'exam_group_class_batch_exam_students.id'
            )
            ->where('exam_group_class_batch_exam_students.id', $examStudentId)
            ->join('subjects', 'subjects.id', '=', 'exam_group_class_batch_exam_subjects.subject_id')
            ->where('exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id', $examId)
            ->select([
                'exam_group_class_batch_exam_subjects.*',
                'exam_group_exam_results.id as exam_group_exam_results_id',
                'exam_group_exam_results.attendence',
                'exam_group_exam_results.get_marks',
                'exam_group_exam_results.note',
                'subjects.name',
                'subjects.code',
                'exam_group_class_batch_exam_students.rank',
            ])
            ->get()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function examStudentById(int $examStudentId): array
    {
        $row = DB::table('exam_group_class_batch_exam_students')
            ->join('student_session', 'student_session.id', '=', 'exam_group_class_batch_exam_students.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('exam_group_class_batch_exam_students.id', $examStudentId)
            ->select([
                'exam_group_class_batch_exam_students.*',
                'students.admission_no',
                'students.roll_no as student_roll_no',
                'students.id as student_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.roll_no',
                'classes.class',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
            ])
            ->first();

        return $row ? (array) $row : [];
    }

    protected function examById(int $examId): ?object
    {
        return DB::table('exam_group_class_batch_exams')
            ->join('exam_groups', 'exam_groups.id', '=', 'exam_group_class_batch_exams.exam_group_id')
            ->join('sessions', 'sessions.id', '=', 'exam_group_class_batch_exams.session_id')
            ->where('exam_group_class_batch_exams.id', $examId)
            ->select([
                'exam_group_class_batch_exams.*',
                'exam_groups.name as exam_group_name',
                'exam_groups.exam_type as exam_group_type',
                'exam_groups.id as exam_group_id',
                'sessions.session',
            ])
            ->first();
    }
}
