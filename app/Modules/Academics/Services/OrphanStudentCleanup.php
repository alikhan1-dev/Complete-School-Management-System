<?php

namespace App\Modules\Academics\Services;

use Illuminate\Support\Facades\DB;

/**
 * Mirrors CI Student_model::getUndefinedStudent + bulkdelete after class/section deletion.
 */
class OrphanStudentCleanup
{
    /**
     * @return int Number of students removed
     */
    public function removeStudentsWithoutSession(): int
    {
        $orphanIds = DB::table('students')
            ->leftJoin('student_session', 'student_session.student_id', '=', 'students.id')
            ->whereNull('student_session.id')
            ->pluck('students.id')
            ->unique()
            ->values()
            ->all();

        if ($orphanIds === []) {
            return 0;
        }

        DB::transaction(function () use ($orphanIds) {
            DB::table('students')->whereIn('id', $orphanIds)->delete();

            DB::table('users')
                ->where('role', 'student')
                ->whereIn('user_id', $orphanIds)
                ->delete();

            // Match CI bulkdelete custom field cleanup for students.
            $customValueIds = DB::table('custom_fields')
                ->join('custom_field_values as t2', 't2.custom_field_id', '=', 'custom_fields.id')
                ->where('custom_fields.belong_to', 'students')
                ->whereIn('t2.belong_table_id', $orphanIds)
                ->pluck('t2.id')
                ->all();

            if ($customValueIds !== []) {
                DB::table('custom_field_values')->whereIn('id', $customValueIds)->delete();
            }

            // Delete parent users that no longer have any linked students.
            $orphanParentIds = DB::table('users')
                ->leftJoin('students', 'users.id', '=', 'students.parent_id')
                ->where('users.role', 'parent')
                ->whereNull('students.id')
                ->pluck('users.id')
                ->all();

            if ($orphanParentIds !== []) {
                DB::table('users')->whereIn('id', $orphanParentIds)->delete();
            }
        });

        return count($orphanIds);
    }
}
