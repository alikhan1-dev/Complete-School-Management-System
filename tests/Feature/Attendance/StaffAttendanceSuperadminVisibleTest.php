<?php

namespace Tests\Feature\Attendance;

use App\Modules\Attendance\Services\StaffAttendanceService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffAttendanceSuperadminVisibleTest extends TestCase
{
    private ?string $savedRestriction = null;

    /** @var list<int> */
    private array $createdStaffIds = [];

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

        if ($this->createdStaffIds !== []) {
            DB::table('staff_roles')->whereIn('staff_id', $this->createdStaffIds)->delete();
            DB::table('staff')->whereIn('id', $this->createdStaffIds)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function setSuperadminRestriction(string $value): void
    {
        if ($this->savedRestriction === null) {
            $this->savedRestriction = (string) DB::table('sch_settings')->value('superadmin_restriction');
        }
        DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $value]);
        app(SchoolContext::class)->clearCache();
    }

    private function createStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => ucfirst($prefix),
            'surname' => 'Attendance',
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

        return Staff::query()->findOrFail($staffId);
    }

    public function test_staff_attendance_search_excludes_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');
        $this->assertGreaterThan(0, $superRoleId);
        $this->assertGreaterThan(0, $teacherRoleId);

        $superStaff = $this->createStaff($superRoleId, 'sa');
        $teacherStaff = $this->createStaff($teacherRoleId, 'tch');
        $viewer = $this->createStaff($teacherRoleId, 'view');
        $this->actingAs($viewer, 'staff');

        $date = '2026-08-20';
        $employeeIds = app(StaffAttendanceService::class)
            ->searchByRole('select', $date)
            ->pluck('employee_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->assertContains((string) $teacherStaff->employee_id, $employeeIds);
        $this->assertNotContains((string) $superStaff->employee_id, $employeeIds);
    }

    public function test_staff_attendance_search_shows_superadmin_staff_to_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $superRoleId);

        $superStaff = $this->createStaff($superRoleId, 'sa2');
        $viewer = $this->createStaff($superRoleId, 'saView');
        $this->actingAs($viewer, 'staff');

        $date = '2026-08-21';

        $this->post('/admin/staffattendance', [
            'search' => 'search',
            'user_id' => 'select',
            'date' => $date,
        ])->assertOk()
            ->assertSee($superStaff->employee_id, false);
    }

    public function test_staff_daywise_report_excludes_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');

        $superStaff = $this->createStaff($superRoleId, 'sa3');
        $teacherStaff = $this->createStaff($teacherRoleId, 'tch3');
        $viewer = $this->createStaff($teacherRoleId, 'view3');
        $this->actingAs($viewer, 'staff');

        $response = $this->post('/attendencereports/staffdaywiseattendancereport', [
            'role' => 'select',
            'date' => '20-08-2026',
        ]);

        $response->assertOk()
            ->assertSee($teacherStaff->employee_id, false)
            ->assertDontSee($superStaff->employee_id, false);
    }
}
