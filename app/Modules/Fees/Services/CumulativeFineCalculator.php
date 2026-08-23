<?php

namespace App\Modules\Fees\Services;

use Illuminate\Support\Facades\DB;

/**
 * CI Customlib::get_cumulative_fine_amount + Studentfeemaster_model lookup.
 *
 * Note: CI iterates slabs in query/id order. Laravel orders by overdue_day then id so
 * out-of-order inserts (e.g. re-adding a lower day slab) cannot produce negative totals.
 */
class CumulativeFineCalculator
{
    /**
     * @return float|false false when no slabs exist (CI parity)
     */
    public function amountFor(int $feeGroupsFeetypeId, int $dueDays): float|false
    {
        if ($feeGroupsFeetypeId <= 0 || $dueDays <= 0) {
            return false;
        }

        $slabs = DB::table('cumulative_fine')
            ->leftJoin('fee_groups_feetype', 'fee_groups_feetype.id', '=', 'cumulative_fine.fee_groups_feetype_id')
            ->where('cumulative_fine.fee_groups_feetype_id', $feeGroupsFeetypeId)
            ->orderBy('cumulative_fine.overdue_day')
            ->orderBy('cumulative_fine.id')
            ->select([
                'cumulative_fine.overdue_day',
                'cumulative_fine.fine_amount',
                'fee_groups_feetype.fine_per_day',
            ])
            ->get()
            ->all();

        if ($slabs === []) {
            return false;
        }

        $dueFineAmount = 0.0;

        foreach ($slabs as $key => $value) {
            $overdueDay = (int) $value->overdue_day;
            $fineAmount = (float) $value->fine_amount;
            $finePerDay = (int) ($value->fine_per_day ?? 0) === 1;

            if ($finePerDay) {
                if ($dueDays > $overdueDay) {
                    $next = $slabs[$key + 1] ?? null;
                    if ($next !== null && ! empty($next->overdue_day)) {
                        $nextOverdue = (int) $next->overdue_day;
                        if ($nextOverdue < $dueDays) {
                            $day = $nextOverdue - $overdueDay;
                            $dueFineAmount += $fineAmount * $day;
                        } else {
                            $overduedays = $dueDays - $overdueDay;
                            $dueFineAmount += $fineAmount * $overduedays;
                        }
                    } else {
                        $overduedays = $dueDays - $overdueDay;
                        $dueFineAmount += $fineAmount * $overduedays;
                    }
                }
            } else {
                // CI overwrites (last matching slab wins)
                if ($dueDays > $overdueDay) {
                    $dueFineAmount = $fineAmount;
                }
            }
        }

        return $dueFineAmount;
    }
}
