<?php

namespace App\Modules\Settings\Services;

use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Attendance\Models\AttendenceType;
use App\Modules\Attendance\Models\StaffAttendanceType;
use Illuminate\Support\Facades\DB;

/**
 * CI Schsettings::savestaffsetting + Stuattendence::saveclasstime / savestudentsetting.
 */
class SchoolAttendanceScheduleService
{
    /**
     * CI Attendencetype_model::getScheduleTypeStaffAttendance.
     *
     * @return list<object>
     */
    public function staffScheduleTypes(): array
    {
        return StaffAttendanceType::query()
            ->where('for_schedule', 1)
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * CI Attendencetype_model::getScheduleTypeAttendance.
     *
     * @return list<object>
     */
    public function studentScheduleTypes(): array
    {
        return AttendenceType::query()
            ->where('for_schedule', 1)
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * CI class_model::get() else-branch (all classes). Teacher-restricted list is deferred with Attendance.
     *
     * @return list<object>
     */
    public function classes(): array
    {
        return SchoolClass::query()->orderBy('id')->get()->all();
    }

    /**
     * CI Class_section_time_model::allClassSections.
     *
     * @return list<array{id:int,class:string,sections:list<object>}>
     */
    public function classListWithTimes(): array
    {
        $classes = [];
        foreach ($this->classes() as $class) {
            $classes[] = [
                'id' => (int) $class->id,
                'class' => (string) $class->class,
                'sections' => $this->sectionTimesForClass((int) $class->id),
            ];
        }

        return $classes;
    }

    /**
     * CI StaffAttendaceSetting_model::getRoleAttendanceSetting grouped as in Schsettings::attendancetype.
     *
     * @return array<int, array{role_id:int,role:string,schedule:list<object>}>
     */
    public function groupedStaffSchedules(): array
    {
        $rows = DB::table('roles')
            ->leftJoin('staff_attendence_schedules', 'staff_attendence_schedules.role_id', '=', 'roles.id')
            ->select([
                'roles.id',
                'roles.name as role_name',
                'staff_attendence_schedules.role_id',
                'staff_attendence_schedules.staff_attendence_type_id',
                'staff_attendence_schedules.id as staff_attendence_schedules',
                'staff_attendence_schedules.entry_time_from',
                'staff_attendence_schedules.entry_time_to',
                'staff_attendence_schedules.total_institute_hour',
            ])
            ->get();

        $grouped = [];
        foreach ($rows as $value) {
            $roleId = (int) $value->id;
            if (array_key_exists($roleId, $grouped)) {
                $grouped[$roleId]['schedule'][] = $value;
            } else {
                $grouped[$roleId] = [
                    'role_id' => $roleId,
                    'role' => (string) $value->role_name,
                    'schedule' => [$value],
                ];
            }
        }

        return $grouped;
    }

    /**
     * CI StudentAttendaceSetting_model::getClassWiseAttendanceSetting grouped as in Schsettings::attendancetype.
     *
     * @return array<int, array{class_id:int,class:string,sections:array<int, array{class_section_id:int,section_id:int,section:string,student_schedule:list<object>}>}>
     */
    public function groupedStudentSchedules(?int $classId): array
    {
        $query = DB::table('class_sections')
            ->join('classes', 'classes.id', '=', 'class_sections.class_id')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->leftJoin('student_attendence_schedules', 'student_attendence_schedules.class_section_id', '=', 'class_sections.id')
            ->select([
                'class_sections.id',
                'class_sections.class_id',
                'class_sections.section_id',
                'classes.class',
                'sections.section',
                'student_attendence_schedules.class_section_id',
                'student_attendence_schedules.attendence_type_id',
                'student_attendence_schedules.id as student_attendence_schedule_id',
                'student_attendence_schedules.entry_time_from',
                'student_attendence_schedules.entry_time_to',
                'student_attendence_schedules.total_institute_hour',
            ]);

        if ($classId !== null && $classId > 0) {
            $query->where('class_sections.class_id', $classId);
        }

        $grouped = [];
        foreach ($query->get() as $row) {
            $classKey = (int) $row->class_id;
            $sectionKey = (int) $row->section_id;

            if (array_key_exists($classKey, $grouped)) {
                if (array_key_exists($sectionKey, $grouped[$classKey]['sections'])) {
                    $grouped[$classKey]['sections'][$sectionKey]['student_schedule'][] = $row;
                } else {
                    $grouped[$classKey]['sections'][$sectionKey] = [
                        'class_section_id' => (int) $row->id,
                        'section_id' => $sectionKey,
                        'section' => (string) $row->section,
                        'student_schedule' => [$row],
                    ];
                }
            } else {
                $grouped[$classKey] = [
                    'class_id' => $classKey,
                    'class' => (string) $row->class,
                    'sections' => [
                        $sectionKey => [
                            'class_section_id' => (int) $row->id,
                            'section_id' => $sectionKey,
                            'section' => (string) $row->section,
                            'student_schedule' => [$row],
                        ],
                    ],
                ];
            }
        }

        return $grouped;
    }

    /**
     * CI get_input_value.
     *
     * @param  list<object>  $schedule
     * @return array{entry_time_from:string,entry_time_to:string,total_institute_hour:string}
     */
    public function staffInputValue(array $schedule, int $typeId): array
    {
        foreach ($schedule as $row) {
            if ((int) ($row->staff_attendence_type_id ?? 0) === $typeId) {
                return [
                    'entry_time_from' => (string) ($row->entry_time_from ?? ''),
                    'entry_time_to' => (string) ($row->entry_time_to ?? ''),
                    'total_institute_hour' => (string) ($row->total_institute_hour ?? ''),
                ];
            }
        }

        return [
            'entry_time_from' => '',
            'entry_time_to' => '',
            'total_institute_hour' => '',
        ];
    }

    /**
     * CI get_student_input_value.
     *
     * @param  list<object>  $schedule
     * @return array{entry_time_from:string,entry_time_to:string,total_institute_hour:string}
     */
    public function studentInputValue(array $schedule, int $typeId): array
    {
        foreach ($schedule as $row) {
            if ((int) ($row->attendence_type_id ?? 0) === $typeId) {
                return [
                    'entry_time_from' => (string) ($row->entry_time_from ?? ''),
                    'entry_time_to' => (string) ($row->entry_time_to ?? ''),
                    'total_institute_hour' => (string) ($row->total_institute_hour ?? ''),
                ];
            }
        }

        return [
            'entry_time_from' => '',
            'entry_time_to' => '',
            'total_institute_hour' => '',
        ];
    }

    /**
     * CI StaffAttendaceSetting_model::add.
     *
     * @param  list<array<string, mixed>>  $insertArray
     * @param  list<int|string>  $roleIds
     */
    public function replaceStaffSchedules(array $insertArray, array $roleIds): void
    {
        $roleIds = array_values(array_unique($roleIds));
        if ($roleIds !== []) {
            DB::table('staff_attendence_schedules')->whereIn('role_id', $roleIds)->delete();
        }
        if ($insertArray !== []) {
            DB::table('staff_attendence_schedules')->insert($insertArray);
        }
    }

    /**
     * CI StudentAttendaceSetting_model::add.
     *
     * @param  list<array<string, mixed>>  $insertArray
     * @param  list<int|string>  $classSectionIds
     */
    public function replaceStudentSchedules(array $insertArray, array $classSectionIds): void
    {
        $classSectionIds = array_values(array_unique($classSectionIds));
        if ($classSectionIds !== []) {
            DB::table('student_attendence_schedules')->whereIn('class_section_id', $classSectionIds)->delete();
        }
        if ($insertArray !== []) {
            DB::table('student_attendence_schedules')->insert($insertArray);
        }
    }

    /**
     * CI Class_section_time_model::add.
     *
     * @param  list<array<string, mixed>>  $insertData
     * @param  list<array<string, mixed>>  $updateData
     */
    public function saveClassTimes(array $insertData, array $updateData): void
    {
        DB::transaction(function () use ($insertData, $updateData) {
            if ($insertData !== []) {
                DB::table('class_section_times')->insert($insertData);
            }
            foreach ($updateData as $row) {
                $id = (int) ($row['id'] ?? 0);
                unset($row['id']);
                if ($id > 0) {
                    DB::table('class_section_times')->where('id', $id)->update($row);
                }
            }
        });
    }

    /**
     * CI Customlib::timeFormat($time, true).
     */
    public function timeFormat24(mixed $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        $parsed = strtotime((string) $time);
        if ($parsed === false) {
            return (string) $time;
        }

        return date('H:i', $parsed);
    }

    /**
     * @return list<object>
     */
    protected function sectionTimesForClass(int $classId): array
    {
        return DB::table('class_sections')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->leftJoin('class_section_times', 'class_section_times.class_section_id', '=', 'class_sections.id')
            ->where('class_sections.class_id', $classId)
            ->orderBy('sections.section')
            ->select([
                'class_sections.*',
                'sections.section',
                DB::raw('IFNULL(class_section_times.id, 0) as class_section_times_id'),
                DB::raw('IFNULL(class_section_times.time, 0) as time'),
            ])
            ->get()
            ->all();
    }
}
