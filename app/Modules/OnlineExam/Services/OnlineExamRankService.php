<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Onlineexam::rankgenerate + saverank (+ _rankgenerate score-bucket ranking).
 * Dense ranks by (int) net score groups; ties share the same rank.
 */
class OnlineExamRankService
{
    public function __construct(
        protected OnlineExamService $exams,
        protected OnlineExamResultService $results,
        protected SchoolContext $school,
    ) {
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    public function exam(int $examId): OnlineExam
    {
        return $this->exams->find($examId);
    }

    /**
     * CI: generate_rank button only when result published (or auto_publish_date reached).
     */
    public function canGenerateRank(object $exam): bool
    {
        if ((int) ($exam->publish_result ?? 0) === 1) {
            return true;
        }

        $auto = $exam->auto_publish_date ?? null;
        if ($auto === null || $auto === '' || $auto === '0000-00-00' || $auto === '0000-00-00 00:00:00') {
            return false;
        }

        return strtotime((string) $auto) !== false
            && strtotime((string) $auto) <= time();
    }

    /**
     * Attempted assignees for rank preview (CI searchAllOnlineExamStudents(..., 1)).
     *
     * @return list<object>
     */
    public function attemptedStudents(int $examId): array
    {
        return DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'categories.id', '=', 'students.category_id')
            ->join('onlineexam_students', function ($join) use ($examId) {
                $join->on('onlineexam_students.student_session_id', '=', 'student_session.id')
                    ->where('onlineexam_students.onlineexam_id', '=', $examId);
            })
            ->where('student_session.session_id', $this->exams->currentSessionId())
            ->where('students.is_active', 'yes')
            ->where('onlineexam_students.is_attempted', 1)
            ->orderBy('onlineexam_students.rank')
            ->orderByDesc('onlineexam_students.is_attempted')
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
                DB::raw('IFNULL(categories.category, "") as category'),
                'onlineexam_students.id as onlineexam_student_id',
                'onlineexam_students.is_attempted',
                DB::raw('IFNULL(onlineexam_students.rank, 0) as exam_rank'),
            ])
            ->get()
            ->all();
    }

    /**
     * Proposed ranks keyed by onlineexam_student_id (CI _rankgenerate algorithm).
     *
     * @return array<int, int>
     */
    public function proposedRanks(OnlineExam $exam): array
    {
        $buckets = [];
        foreach ($this->attemptedStudents((int) $exam->id) as $student) {
            $onlineexamStudentId = (int) $student->onlineexam_student_id;
            $summary = $this->results->scoreSummary(
                $exam,
                $this->results->resultRows($onlineexamStudentId, (int) $exam->id)
            );
            // CI casts net score to int before grouping.
            $net = (int) $summary['exam_total_scored'];
            $buckets[$net][] = $onlineexamStudentId;
        }

        krsort($buckets, SORT_NUMERIC);

        $ranks = [];
        $rank = 1;
        foreach ($buckets as $ids) {
            foreach ($ids as $onlineexamStudentId) {
                $ranks[$onlineexamStudentId] = $rank;
            }
            $rank++;
        }

        return $ranks;
    }

    /**
     * Persist proposed ranks and set onlineexam.is_rank_generated = 1.
     */
    public function saveRanks(OnlineExam $exam): void
    {
        if (! $this->canGenerateRank($exam)) {
            throw ValidationException::withMessages([
                'exam' => 'Result is not published for this exam yet.',
            ]);
        }

        $ranks = $this->proposedRanks($exam);
        if ($ranks === []) {
            throw ValidationException::withMessages([
                'exam' => (string) __('system.no_record_found'),
            ]);
        }

        $rows = [];
        foreach ($ranks as $onlineexamStudentId => $rank) {
            $rows[] = [
                'id' => $onlineexamStudentId,
                'rank' => $rank,
            ];
        }

        DB::transaction(function () use ($exam, $rows) {
            DB::table('onlineexam')
                ->where('id', $exam->id)
                ->update(['is_rank_generated' => 1]);

            foreach ($rows as $row) {
                DB::table('onlineexam_students')
                    ->where('id', $row['id'])
                    ->where('onlineexam_id', $exam->id)
                    ->update(['rank' => $row['rank']]);
            }
        });
    }

    public function studentDisplayName(object $student): string
    {
        $first = trim((string) ($student->firstname ?? ''));
        $middle = trim((string) ($student->middlename ?? ''));
        $last = trim((string) ($student->lastname ?? ''));

        $name = $this->settingOn('middlename') && $middle !== ''
            ? trim($first.' '.$middle)
            : $first;
        if ($this->settingOn('lastname') && $last !== '') {
            $name = trim($name.' '.$last);
        }

        return $name !== '' ? $name : $first;
    }
}
