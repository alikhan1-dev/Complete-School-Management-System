<?php

namespace App\Modules\Staff\Services;

use Illuminate\Support\Facades\DB;

/**
 * CI Staff_model rating helpers — profile summary/reviews and admin/Staff::rating list.
 */
class StaffRatingService
{
    /**
     * CI Staff_model::getrat — teachers rating admin list.
     *
     * @return list<array<string, mixed>>
     */
    public function adminList(): array
    {
        return DB::table('staff')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->join('staff_rating', 'staff_rating.staff_id', '=', 'staff.id')
            ->leftJoin('users', 'users.id', '=', 'staff_rating.user_id')
            ->leftJoin('students', 'students.id', '=', 'users.user_id')
            ->where('staff.is_active', 1)
            ->whereNotIn('roles.id', [7])
            ->orderBy('staff.id')
            ->select([
                'staff.id',
                'staff.employee_id',
                DB::raw('CONCAT_WS(" ", staff.name, staff.surname, CONCAT("(", staff.employee_id, ")")) as name'),
                'roles.name as user_type',
                'roles.id as role_id',
                'staff_rating.rate',
                'staff_rating.status',
                'staff_rating.comment',
                'staff_rating.id as rate_id',
                DB::raw('CONCAT_WS(" ", students.firstname, students.middlename, students.lastname, CONCAT("(", students.admission_no, ")")) as student_name'),
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function find(int $id): ?object
    {
        return DB::table('staff_rating')->where('id', $id)->first();
    }

    /** CI Staff_model::ratingapr — sets status to approved (1). */
    public function approve(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return DB::table('staff_rating')->where('id', $id)->update(['status' => '1']) > 0;
        });
    }

    /** CI Staff_model::rating_remove. */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return DB::table('staff_rating')->where('id', $id)->delete() > 0;
        });
    }

    public function isTeacherProfile(object $staffProfile): bool
    {
        return strcasecmp((string) ($staffProfile->role_name ?? ''), 'Teacher') === 0;
    }

    /**
     * CI staff_ratingById + profile average when total >= 3 (skipped for staff id 1).
     *
     * @return array{total: int, rate_sum: float, average: float|null, can_view_average: bool}
     */
    public function summaryForProfile(int $staffId): array
    {
        if ($staffId <= 0 || $staffId === 1) {
            return [
                'total' => 0,
                'rate_sum' => 0.0,
                'average' => null,
                'can_view_average' => false,
            ];
        }

        $row = DB::table('staff_rating')
            ->where('staff_id', $staffId)
            ->where('status', 1)
            ->selectRaw('COALESCE(SUM(`rate`), 0) as rate_sum, COUNT(*) as total')
            ->first();

        $total = (int) ($row->total ?? 0);
        $rateSum = (float) ($row->rate_sum ?? 0);
        $canViewAverage = $total >= 3;
        $average = $canViewAverage && $total > 0 ? $rateSum / $total : null;

        return [
            'total' => $total,
            'rate_sum' => $rateSum,
            'average' => $average,
            'can_view_average' => $canViewAverage,
        ];
    }

    /**
     * CI Staff_model::user_reviewlist — approved ratings for profile reviews tab.
     *
     * @return list<array<string, mixed>>
     */
    public function approvedReviews(int $staffId): array
    {
        return DB::table('staff_rating')
            ->join('users', 'users.id', '=', 'staff_rating.user_id')
            ->join('staff', 'staff_rating.staff_id', '=', 'staff.id')
            ->leftJoin('students', 'students.id', '=', 'users.user_id')
            ->where('staff.is_active', 1)
            ->where('staff_rating.staff_id', $staffId)
            ->where('staff_rating.status', 1)
            ->orderByDesc('staff_rating.id')
            ->select([
                'staff_rating.rate',
                'staff_rating.comment',
                'staff_rating.role',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.guardian_name',
            ])
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                $role = strtolower((string) ($data['role'] ?? ''));
                if ($role === 'student') {
                    $data['reviewer_name'] = trim(
                        ($data['firstname'] ?? '').' '.($data['lastname'] ?? '')
                    );
                } else {
                    $data['reviewer_name'] = (string) ($data['guardian_name'] ?? '');
                }

                return $data;
            })
            ->all();
    }

    /**
     * CI substr($rate, 0, 3) display for profile header average.
     */
    public static function formatAverageDisplay(?float $average): string
    {
        if ($average === null) {
            return '';
        }

        return substr((string) $average, 0, 3);
    }
}
