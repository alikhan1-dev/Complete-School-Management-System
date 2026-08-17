<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\DB;

/**
 * CI Schsettings::attendancetype + saveattendancetype.
 */
class SchoolAttendanceTypeSettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * Columns written by CI Schsettings::saveattendancetype + sidebar_sub_menus toggles.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $row = SchSetting::query()->where('id', $id)->first()
            ?? SchSetting::query()->orderBy('id')->first();

        if ($row === null) {
            throw new \RuntimeException('School settings row was not found.');
        }

        $attendenceType = (int) ($data['attendence_type'] ?? 0);

        $row->attendence_type = $attendenceType;
        $row->biometric_device = (string) ($data['biometric_device'] ?? '');
        $row->biometric = ! empty($data['biometric']) ? 1 : 0;
        $row->low_attendance_limit = $data['low_attendance_limit'] ?? 0;
        $row->save();

        // CI: truthy attendence_type → period menus on, day menus off.
        $periodAttendance = $attendenceType ? 1 : 0;
        $studentAttendance = $attendenceType ? 0 : 1;

        $this->updateSubmenuByKey([
            ['key' => 'period_attendance_by_date', 'is_active' => $periodAttendance],
            ['key' => 'period_attendance', 'is_active' => $periodAttendance],
            ['key' => 'student_attendance', 'is_active' => $studentAttendance],
            ['key' => 'attendance_by_date', 'is_active' => $studentAttendance],
        ]);

        $this->school->clearCache();
    }

    /**
     * CI Sidebarmenu_model::update_submenu_by_key.
     *
     * @param  list<array{key:string,is_active:int}>  $rows
     */
    protected function updateSubmenuByKey(array $rows): void
    {
        foreach ($rows as $row) {
            DB::table('sidebar_sub_menus')
                ->where('key', $row['key'])
                ->update(['is_active' => (int) $row['is_active']]);
        }
    }
}
