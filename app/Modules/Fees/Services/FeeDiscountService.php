<?php

namespace App\Modules\Fees\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Models\FeesDiscount;
use App\Modules\Fees\Models\StudentFeesDiscount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Feediscount_model (define + allotdiscount / deletedisstd / searchAssign).
 */
class FeeDiscountService
{
    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * @return Collection<int, FeesDiscount>
     */
    public function listAll(): Collection
    {
        return FeesDiscount::query()->orderByDesc('id')->get();
    }

    public function find(int $id): ?FeesDiscount
    {
        return FeesDiscount::query()->find($id);
    }

    /**
     * @param  array{name:string,code:string,type:string,amount?:float|string,percentage?:float|string|null,discount_limit:int|string,expire_date?:string|null,description?:string|null}  $data
     */
    public function create(array $data): FeesDiscount
    {
        $type = $data['type'] ?: 'fix';

        return FeesDiscount::query()->create([
            'session_id' => $this->currentSession->id(),
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $type,
            'amount' => $type === 'percentage' ? 0 : (float) ($data['amount'] ?? 0),
            'percentage' => $type === 'percentage' ? (float) ($data['percentage'] ?? 0) : null,
            'discount_limit' => (int) $data['discount_limit'],
            'expire_date' => $data['expire_date'] ?: null,
            'description' => $data['description'] ?? '',
            'is_active' => 'no',
        ]);
    }

    /**
     * @param  array{name:string,code:string,type:string,amount?:float|string,percentage?:float|string|null,discount_limit:int|string,expire_date?:string|null,description?:string|null}  $data
     */
    public function update(FeesDiscount $row, array $data): FeesDiscount
    {
        $type = $data['type'] ?: 'fix';
        $row->name = $data['name'];
        $row->code = $data['code'];
        $row->type = $type;
        $row->amount = $type === 'percentage' ? 0 : (float) ($data['amount'] ?? 0);
        $row->percentage = $type === 'percentage' ? (float) ($data['percentage'] ?? 0) : null;
        $row->discount_limit = (int) $data['discount_limit'];
        $row->expire_date = $data['expire_date'] ?: null;
        $row->description = $data['description'] ?? '';
        $row->save();

        return $row;
    }

    public function delete(int $id): void
    {
        FeesDiscount::query()->where('id', $id)->delete();
    }

    /**
     * @return Collection<int, object>
     */
    public function searchStudentsForAssign(
        int $feesDiscountId,
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
            ->leftJoin('student_fees_discounts', function ($join) use ($feesDiscountId) {
                $join->on('student_fees_discounts.student_session_id', '=', 'student_session.id')
                    ->where('student_fees_discounts.fees_discount_id', '=', $feesDiscountId);
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
                'student_session.id as student_session_id',
                'classes.class',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
                DB::raw('IFNULL(student_fees_discounts.id, 0) as student_fees_discount_id'),
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
     * @param  list<int|string>  $checkedStudentSessionIds
     * @param  list<int|string>  $allStudentSessionIds
     */
    public function syncAssignments(int $feesDiscountId, array $checkedStudentSessionIds, array $allStudentSessionIds): void
    {
        $checked = array_values(array_unique(array_map('intval', $checkedStudentSessionIds)));
        $all = array_values(array_unique(array_map('intval', $allStudentSessionIds)));
        $toDelete = array_values(array_diff($all, $checked));

        DB::transaction(function () use ($feesDiscountId, $checked, $toDelete) {
            foreach ($checked as $studentSessionId) {
                if ($studentSessionId <= 0) {
                    continue;
                }
                $exists = StudentFeesDiscount::query()
                    ->where('student_session_id', $studentSessionId)
                    ->where('fees_discount_id', $feesDiscountId)
                    ->exists();

                if (! $exists) {
                    StudentFeesDiscount::query()->create([
                        'student_session_id' => $studentSessionId,
                        'fees_discount_id' => $feesDiscountId,
                        'status' => 'assigned',
                        'payment_id' => null,
                        'description' => null,
                        'is_active' => 'no',
                    ]);
                }
            }

            if ($toDelete !== []) {
                StudentFeesDiscount::query()
                    ->where('fees_discount_id', $feesDiscountId)
                    ->whereIn('student_session_id', $toDelete)
                    ->delete();
            }
        });
    }
}
