<?php

namespace App\Modules\Hostel\Services;

use App\Modules\Hostel\Models\Hostel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/hostelroom/studenthosteldetails — student hostel report.
 * Form POST search replaces CI DataTables AJAX (searchvalidation/dthostellist).
 * Deferred: class-teacher class_section scope filtering.
 */
class StudentHostelReportService
{
    /**
     * @return Collection<int, Hostel>
     */
    public function listHostels(): Collection
    {
        return Hostel::query()->orderBy('hostel_name')->get();
    }

    /**
     * CI Hostelroom_model::searchHostelDetails (non-DataTables).
     * Joins hostel via students.hostel_room_id (CI parity).
     *
     * @param  array{class_id:mixed,section_id:mixed,hostel_name?:mixed}  $filters
     * @return Collection<int, object>
     */
    public function search(array $filters): Collection
    {
        $query = DB::table('students')
            ->join('student_session', 'students.id', '=', 'student_session.student_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('hostel_rooms', 'hostel_rooms.id', '=', 'students.hostel_room_id')
            ->join('hostel', 'hostel.id', '=', 'hostel_rooms.hostel_id')
            ->join('room_types', 'room_types.id', '=', 'hostel_rooms.room_type_id')
            ->where('students.is_active', 'yes')
            ->where('student_session.class_id', (int) $filters['class_id'])
            ->where('student_session.section_id', (int) $filters['section_id'])
            ->select([
                'students.id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'students.mobileno',
                'students.guardian_phone',
                'classes.class',
                'sections.section',
                'hostel.hostel_name',
                'hostel_rooms.room_no',
                'room_types.room_type',
                'hostel_rooms.cost_per_bed',
            ])
            ->orderBy('students.firstname');

        $hostelId = (int) ($filters['hostel_name'] ?? 0);
        if ($hostelId > 0) {
            $query->where('hostel.id', $hostelId);
        }

        return $query->get();
    }
}
