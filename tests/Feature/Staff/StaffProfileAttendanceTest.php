<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffProfileAttendanceTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdAttendanceIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdAttendanceIds as $attendanceId) {
            DB::table('staff_attendance')->where('id', $attendanceId)->delete();
        }
        $this->createdAttendanceIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('sta', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Attendance',
            'surname' => 'Admin',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => $token.'@example.test',
            'dob' => '1990-01-01',
            'marital_status' => '',
            'local_address' => '',
            'permanent_address' => '',
            'note' => '',
            'image' => '',
            'password' => bcrypt('secret'),
            'gender' => 'Male',
            'account_title' => '',
            'bank_account_no' => '',
            'bank_name' => '',
            'ifsc_code' => '',
            'bank_branch' => '',
            'payscale' => '',
            'epf_no' => '',
            'contract_type' => '',
            'shift' => '',
            'location' => '',
            'facebook' => '',
            'twitter' => '',
            'linkedin' => '',
            'instagram' => '',
            'resume' => '',
            'joining_letter' => '',
            'resignation_letter' => '',
            'other_document_name' => '',
            'other_document_file' => '',
            'user_id' => 0,
            'is_active' => 1,
            'verification_code' => '',
        ]);
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    private function createTeacherStaff(string $suffix): Staff
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ATT-'.$suffix,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Attendance',
            'surname' => 'Target',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => 'attendance'.$suffix.'@example.test',
            'dob' => '1985-06-20',
            'marital_status' => '',
            'local_address' => '',
            'permanent_address' => '',
            'note' => '',
            'image' => '',
            'password' => bcrypt('secret'),
            'gender' => 'Male',
            'account_title' => '',
            'bank_account_no' => '',
            'bank_name' => '',
            'ifsc_code' => '',
            'bank_branch' => '',
            'payscale' => '',
            'epf_no' => '',
            'contract_type' => '',
            'shift' => '',
            'location' => '',
            'facebook' => '',
            'twitter' => '',
            'linkedin' => '',
            'instagram' => '',
            'resume' => '',
            'joining_letter' => '',
            'resignation_letter' => '',
            'other_document_name' => '',
            'other_document_file' => '',
            'user_id' => 0,
            'is_active' => 1,
            'verification_code' => '',
        ]);
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $teacherRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    public function test_staff_profile_ajax_attendance_returns_matrix_and_counts(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());
        $year = 2026;

        $presentTypeId = (int) (DB::table('staff_attendance_type')->where('key_value', 'P')->value('id')
            ?: DB::table('staff_attendance_type')->where('id', 1)->value('id'));
        $this->assertGreaterThan(0, $presentTypeId);

        $attendanceId = DB::table('staff_attendance')->insertGetId([
            'staff_id' => $target->id,
            'date' => '2026-01-15',
            'staff_attendance_type_id' => $presentTypeId,
            'remark' => 'On time',
            'in_time' => '08:30:00',
            'out_time' => '16:00:00',
            'is_active' => 0,
            'biometric_attendence' => 0,
            'qrcode_attendance' => 0,
            'created_at' => now(),
        ]);
        $this->createdAttendanceIds[] = $attendanceId;

        $this->postJson('/admin/staff/ajax_attendance', [
            'id' => $target->id,
            'year' => $year,
        ])
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('countAttendance.present', 1)
            ->assertJsonStructure(['page']);

        $page = (string) $this->postJson('/admin/staff/ajax_attendance', [
            'id' => $target->id,
            'year' => $year,
        ])->json('page');

        $this->assertStringContainsString('attendancetable', $page);
        $this->assertStringContainsString('On time', $page);
    }
}
