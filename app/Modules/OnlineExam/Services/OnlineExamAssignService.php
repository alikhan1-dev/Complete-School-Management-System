<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\OnlineExam\Models\OnlineExam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Onlineexam_model::searchOnlineExamStudents / addStudents.
 * Deferred: teacher class-section scoping.
 */
class OnlineExamAssignService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected OnlineExamService $exams
    ) {
    }

    public function exam(int $examId): OnlineExam
    {
        return $this->exams->find($examId);
    }

    /**
     * Roster for assign UI. Section optional (CI parity: empty = all sections in class).
     *
     * @return Collection<int, object>
     */
    public function searchStudents(int $examId, int $classId, ?int $sectionId = null): Collection
    {
        $query = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->leftJoin('onlineexam_students', function ($join) use ($examId) {
                $join->on('onlineexam_students.student_session_id', '=', 'student_session.id')
                    ->where('onlineexam_students.onlineexam_id', '=', $examId);
            })
            ->where('student_session.session_id', $this->currentSession->id())
            ->where('student_session.class_id', $classId)
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
                DB::raw('IFNULL(onlineexam_students.id, 0) as onlineexam_student_id'),
                DB::raw('IFNULL(onlineexam_students.student_session_id, 0) as onlineexam_student_session_id'),
            ]);

        if ($sectionId !== null && $sectionId > 0) {
            $query->where('student_session.section_id', $sectionId);
        }

        return $query->get();
    }

    /**
     * Sync checked student_session rows for the current class/section search set.
     *
     * @param  list<int|string>  $checkedStudentSessionIds
     */
    public function syncStudents(
        int $examId,
        int $classId,
        ?int $sectionId,
        array $checkedStudentSessionIds
    ): void {
        $this->exam($examId);

        $roster = $this->searchStudents($examId, $classId, $sectionId);
        $rosterSessionIds = $roster->pluck('student_session_id')->map(fn ($id) => (int) $id)->all();

        $currentlyAssigned = $roster
            ->filter(fn ($row) => (int) $row->onlineexam_student_session_id !== 0)
            ->map(fn ($row) => (int) $row->onlineexam_student_session_id)
            ->values()
            ->all();

        $checked = array_values(array_unique(array_map(
            'intval',
            array_filter($checkedStudentSessionIds, fn ($id) => in_array((int) $id, $rosterSessionIds, true))
        )));

        $toInsert = array_values(array_diff($checked, $currentlyAssigned));
        $toDelete = array_values(array_diff($currentlyAssigned, $checked));

        DB::transaction(function () use ($examId, $toInsert, $toDelete) {
            if ($toInsert !== []) {
                $rows = [];
                foreach ($toInsert as $studentSessionId) {
                    $rows[] = [
                        'onlineexam_id' => $examId,
                        'student_session_id' => $studentSessionId,
                        'is_attempted' => 0,
                        'rank' => 0,
                        'quiz_attempted' => 0,
                    ];
                }
                DB::table('onlineexam_students')->insert($rows);
            }

            if ($toDelete !== []) {
                DB::table('onlineexam_students')
                    ->where('onlineexam_id', $examId)
                    ->whereIn('student_session_id', $toDelete)
                    ->delete();
            }
        });
    }
}
