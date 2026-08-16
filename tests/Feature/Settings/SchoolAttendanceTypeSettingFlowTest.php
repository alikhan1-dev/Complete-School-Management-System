<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolAttendanceTypeSettingFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $settingsSnapshot = null;

    /** @var array<string, int> */
    private array $submenuSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->settingsSnapshot = (array) $row;

        foreach (['period_attendance_by_date', 'period_attendance', 'student_attendance', 'attendance_by_date'] as $key) {
            $active = DB::table('sidebar_sub_menus')->where('key', $key)->value('is_active');
            if ($active !== null) {
                $this->submenuSnapshot[$key] = (int) $active;
            }
        }
    }

    protected function tearDown(): void
    {
        if ($this->settingsSnapshot !== null) {
            $id = $this->settingsSnapshot['id'];
            $payload = $this->settingsSnapshot;
            unset($payload['id']);
            DB::table('sch_settings')->where('id', $id)->update($payload);
            app(SchoolContext::class)->clearCache();
            $this->settingsSnapshot = null;
        }

        foreach ($this->submenuSnapshot as $key => $active) {
            DB::table('sidebar_sub_menus')->where('key', $key)->update(['is_active' => $active]);
        }
        $this->submenuSnapshot = [];

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

        $token = uniqid('schattn', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'AT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Attn',
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
            'basic_salary' => 0,
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

    public function test_attendancetype_requires_staff_auth(): void
    {
        $this->get('/schsettings/attendancetype')->assertRedirect();
    }

    public function test_superadmin_can_view_attendance_type_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/attendancetype')
            ->assertOk()
            ->assertSee('Attendance Type', false)
            ->assertSee('name="attendence_type"', false)
            ->assertSee('name="low_attendance_limit"', false);
    }

    public function test_saveattendancetype_json_validation_matches_ci_shape(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/schsettings/saveattendancetype', ['sch_id' => 1])
            ->assertOk()
            ->assertJsonPath('status', 'fail')
            ->assertJsonStructure(['status', 'error' => ['attendence_type']]);
    }

    public function test_period_wise_persists_and_toggles_sidebar_keys(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/saveattendancetype', [
            'sch_id' => $id,
            'attendence_type' => '1',
            'biometric' => '1',
            'biometric_device' => 'dev-a,dev-b',
            'low_attendance_limit' => '75.5',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('sch_settings', [
            'id' => $id,
            'attendence_type' => 1,
            'biometric' => 1,
            'biometric_device' => 'dev-a,dev-b',
        ]);

        $limit = (float) DB::table('sch_settings')->where('id', $id)->value('low_attendance_limit');
        $this->assertEqualsWithDelta(75.5, $limit, 0.01);

        if ($this->submenuSnapshot !== []) {
            $this->assertSame(1, (int) DB::table('sidebar_sub_menus')->where('key', 'period_attendance')->value('is_active'));
            $this->assertSame(1, (int) DB::table('sidebar_sub_menus')->where('key', 'period_attendance_by_date')->value('is_active'));
            $this->assertSame(0, (int) DB::table('sidebar_sub_menus')->where('key', 'student_attendance')->value('is_active'));
            $this->assertSame(0, (int) DB::table('sidebar_sub_menus')->where('key', 'attendance_by_date')->value('is_active'));
        }
    }

    public function test_day_wise_toggles_sidebar_keys_opposite(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/saveattendancetype', [
            'sch_id' => $id,
            'attendence_type' => '0',
            'biometric_device' => '',
            'low_attendance_limit' => '60',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('sch_settings')->where('id', $id)->first();
        $this->assertSame(0, (int) $row->attendence_type);
        $this->assertSame(0, (int) $row->biometric);

        if ($this->submenuSnapshot !== []) {
            $this->assertSame(0, (int) DB::table('sidebar_sub_menus')->where('key', 'period_attendance')->value('is_active'));
            $this->assertSame(1, (int) DB::table('sidebar_sub_menus')->where('key', 'student_attendance')->value('is_active'));
            $this->assertSame(1, (int) DB::table('sidebar_sub_menus')->where('key', 'attendance_by_date')->value('is_active'));
        }
    }
}
