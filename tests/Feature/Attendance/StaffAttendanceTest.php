<?php

namespace Tests\Feature\Attendance;

use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Attendance\Services\StaffAttendanceService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    protected function tearDown(): void
    {
        if ($this->createdStaffIds !== []) {
            DB::table('staff_attendance')->whereIn('staff_id', $this->createdStaffIds)->delete();
            DB::table('staff_roles')->whereIn('staff_id', $this->createdStaffIds)->delete();
            DB::table('staff')->whereIn('id', $this->createdStaffIds)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function createStaff(string $prefix, int $roleId, bool $actAs = false): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Staff',
            'surname' => $prefix,
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
        $staff = Staff::query()->findOrFail($staffId);
        if ($actAs) {
            $this->actingAs($staff, 'staff');
        }

        return $staff;
    }

    public function test_staff_attendance_search_save_and_update_round_trip(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $superRoleId);

        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->createStaff('adm', $superRoleId, true);
        $teacher = $this->createStaff('tch', $teacherRoleId);

        $date = '2026-08-12';

        $this->get('/admin/staffattendance')->assertOk()->assertSee('Staff Attendance', false);

        $this->post('/admin/staffattendance', [
            'search' => 'search',
            'user_id' => 'Teacher',
            'date' => $date,
        ])->assertOk()
            ->assertSee($teacher->employee_id, false)
            ->assertSee('Present', false);

        $this->post('/admin/staffattendance', [
            'search' => 'saveattendence',
            'user_id' => 'Teacher',
            'date' => $date,
            'student_session' => [$teacher->id],
            'attendencetype'.$teacher->id => StaffAttendanceService::TYPE_PRESENT,
            'remark'.$teacher->id => 'On duty',
            'in_time_'.$teacher->id => '08:30',
            'out_time_'.$teacher->id => '14:30',
        ])->assertRedirect('/admin/staffattendance');

        $row = StaffAttendance::query()
            ->where('staff_id', $teacher->id)
            ->where('date', $date)
            ->firstOrFail();

        $this->assertSame(StaffAttendanceService::TYPE_PRESENT, (int) $row->staff_attendance_type_id);
        $this->assertSame('On duty', $row->remark);
        $this->assertSame('08:30:00', (string) $row->in_time);
        $this->assertSame('14:30:00', (string) $row->out_time);

        $this->post('/admin/staffattendance', [
            'search' => 'saveattendence',
            'user_id' => 'Teacher',
            'date' => $date,
            'student_session' => [$teacher->id],
            'attendencetype'.$teacher->id => StaffAttendanceService::TYPE_ABSENT,
            'remark'.$teacher->id => 'Leave',
            'in_time_'.$teacher->id => '08:30',
            'out_time_'.$teacher->id => '14:30',
        ])->assertRedirect('/admin/staffattendance');

        $row->refresh();
        $this->assertSame(StaffAttendanceService::TYPE_ABSENT, (int) $row->staff_attendance_type_id);
        $this->assertSame('Leave', $row->remark);
        $this->assertNull($row->in_time);
        $this->assertNull($row->out_time);

        $this->assertSame(1, StaffAttendance::query()
            ->where('staff_id', $teacher->id)
            ->where('date', $date)
            ->count());
    }
}
