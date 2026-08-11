<?php

namespace App\Modules\Fees\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\StudentFeesMaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Studentfeemaster_model::searchAssignFeeByClassSection + add + delete (assign UI).
 */
class FeeAssignService
{
    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    public function findSessionGroup(int $feeSessionGroupId): ?FeeSessionGroup
    {
        return FeeSessionGroup::query()
            ->with(['feeGroup', 'feeTypes.feeType'])
            ->where('id', $feeSessionGroupId)
            ->where('session_id', $this->currentSession->id())
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    public function searchStudents(
        int $feeSessionGroupId,
        ?int $classId,
        ?int $sectionId,
        ?int $categoryId = null,
        ?string $gender = null,
        ?string $rte = null
    ): Collection {
        $sessionId = $this->currentSession->id();

        $query = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->leftJoin('student_fees_master', function ($join) use ($feeSessionGroupId) {
                $join->on('student_fees_master.student_session_id', '=', 'student_session.id')
                    ->where('student_fees_master.fee_session_group_id', '=', $feeSessionGroupId);
            })
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->select([
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.gender',
                'students.rte',
                'student_session.id as student_session_id',
                'classes.id as class_id',
                'classes.class',
                'sections.id as section_id',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
                DB::raw('IFNULL(student_fees_master.id, 0) as student_fees_master_id'),
            ])
            ->orderBy('students.id');

        if ($classId) {
            $query->where('student_session.class_id', $classId);
        }
        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }
        if ($categoryId) {
            $query->where('students.category_id', $categoryId);
        }
        if ($gender) {
            $query->where('students.gender', $gender);
        }
        if ($rte !== null && $rte !== '') {
            $query->where('students.rte', $rte);
        }

        return $query->get();
    }

    /**
     * @param  list<int|string>  $checkedStudentSessionIds  currently checked
     * @param  list<int|string>  $allStudentSessionIds       all rows shown
     */
    public function syncAssignments(int $feeSessionGroupId, array $checkedStudentSessionIds, array $allStudentSessionIds): void
    {
        $checked = array_values(array_unique(array_map('intval', $checkedStudentSessionIds)));
        $all = array_values(array_unique(array_map('intval', $allStudentSessionIds)));
        $toDelete = array_values(array_diff($all, $checked));

        DB::transaction(function () use ($feeSessionGroupId, $checked, $toDelete) {
            foreach ($checked as $studentSessionId) {
                if ($studentSessionId <= 0) {
                    continue;
                }
                $exists = StudentFeesMaster::query()
                    ->where('student_session_id', $studentSessionId)
                    ->where('fee_session_group_id', $feeSessionGroupId)
                    ->exists();

                if (! $exists) {
                    StudentFeesMaster::query()->create([
                        'is_system' => 0,
                        'student_session_id' => $studentSessionId,
                        'fee_session_group_id' => $feeSessionGroupId,
                        'amount' => 0,
                        'is_active' => 'no',
                    ]);
                }
            }

            if ($toDelete !== []) {
                StudentFeesMaster::query()
                    ->where('fee_session_group_id', $feeSessionGroupId)
                    ->whereIn('student_session_id', $toDelete)
                    ->delete();
            }
        });
    }
}
