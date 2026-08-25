<?php

namespace App\Modules\Hostel\Services;

use App\Modules\Hostel\Models\Hostel;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/hostelroom/studenthosteldetails — student hostel report.
 * Form POST search replaces CI DataTables AJAX (searchvalidation/dthostellist).
 */
class StudentHostelReportService
{
    public function __construct(
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    /**
     * CI Class_model::get() teacher-restricted class list for report filters.
     *
     * @return Collection<int, object>
     */
    public function classes(): Collection
    {
        return $this->classTeacherScope->classesForDropdown();
    }

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
        $classId = (int) ($filters['class_id'] ?? 0);
        $sectionId = (int) ($filters['section_id'] ?? 0);

        if ($this->classTeacherScope->isRestricted()) {
            $allowedClasses = $this->classTeacherScope->restrictedClassIds();
            if ($allowedClasses === [] || ! in_array($classId, $allowedClasses, true)) {
                return collect();
            }
            if (! $this->classTeacherScope->allowsClassSection($classId, $sectionId, 'union')) {
                return collect();
            }
        }

        $query = DB::table('students')
            ->join('student_session', 'students.id', '=', 'student_session.student_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('hostel_rooms', 'hostel_rooms.id', '=', 'students.hostel_room_id')
            ->join('hostel', 'hostel.id', '=', 'hostel_rooms.hostel_id')
            ->join('room_types', 'room_types.id', '=', 'hostel_rooms.room_type_id')
            ->where('students.is_active', 'yes')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
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
