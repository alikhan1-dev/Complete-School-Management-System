<?php

namespace App\Modules\Homework\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Homework\Models\DailyAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI homework/dailyassignment — admin list + remark evaluation.
 * Deferred: reports.
 */
class AdminDailyAssignmentService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected HomeworkDocumentService $documents,
    ) {
    }

    /**
     * @param  array{class_id?:mixed,section_id?:mixed,subject_group_id?:mixed,subject_id?:mixed,date?:mixed}  $filters
     * @return Collection<int, object>
     */
    public function search(array $filters): Collection
    {
        $classId = (int) ($filters['class_id'] ?? 0);
        $sectionId = (int) ($filters['section_id'] ?? 0);
        $subjectGroupId = (int) ($filters['subject_group_id'] ?? 0);
        $subjectGroupSubjectId = (int) ($filters['subject_id'] ?? 0);
        $date = (string) ($filters['date'] ?? '');

        if ($classId <= 0 || $sectionId <= 0 || $subjectGroupId <= 0 || $subjectGroupSubjectId <= 0 || $date === '') {
            return collect();
        }

        $sessionId = (int) $this->currentSession->id();

        return DB::table('daily_assignment')
            ->join('student_session', 'student_session.id', '=', 'daily_assignment.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'daily_assignment.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->leftJoin('staff', 'staff.id', '=', 'daily_assignment.evaluated_by')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $sessionId)
            ->where('daily_assignment.date', $date)
            ->where('subject_group_subjects.subject_group_id', $subjectGroupId)
            ->where('subject_group_subjects.id', $subjectGroupSubjectId)
            ->orderByDesc('daily_assignment.id')
            ->select([
                'daily_assignment.*',
                'classes.class',
                'sections.section',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id as staff_employee_id',
            ])
            ->get();
    }

    public function find(int $id): DailyAssignment
    {
        return DailyAssignment::query()->findOrFail($id);
    }

    public function findDetailed(int $id): object
    {
        $row = DB::table('daily_assignment')
            ->join('student_session', 'student_session.id', '=', 'daily_assignment.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'daily_assignment.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->leftJoin('staff', 'staff.id', '=', 'daily_assignment.evaluated_by')
            ->where('daily_assignment.id', $id)
            ->select([
                'daily_assignment.*',
                'classes.class',
                'sections.section',
                'student_session.class_id',
                'student_session.section_id',
                'subject_group_subjects.subject_group_id',
                'subject_group_subjects.id as subject_group_subject_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id as staff_employee_id',
            ])
            ->first();

        abort_unless($row !== null, 404);

        return $row;
    }

    public function saveRemark(int $id, string $evaluationDate, ?string $remark): void
    {
        $staffId = (int) (Auth::guard('staff')->id() ?? 0);
        abort_unless($staffId > 0, 403);

        $updated = DailyAssignment::query()->where('id', $id)->update([
            'evaluation_date' => $evaluationDate,
            'remark' => (string) ($remark ?? ''),
            'evaluated_by' => $staffId,
            'updated_at' => now(),
        ]);

        if ($updated < 1) {
            throw ValidationException::withMessages([
                'assigment_id' => 'Daily assignment not found.',
            ]);
        }
    }

    public function download(int $id): BinaryFileResponse
    {
        $row = $this->find($id);
        $attachment = (string) ($row->attachment ?? '');
        abort_unless($attachment !== '', 404);

        return $this->documents->downloadDaily($attachment);
    }
}
