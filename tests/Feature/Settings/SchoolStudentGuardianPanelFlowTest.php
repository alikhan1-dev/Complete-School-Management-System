<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolStudentGuardianPanelFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $settingsSnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->settingsSnapshot = (array) $row;
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

        $token = uniqid('schsgp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SG-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'SGP',
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

    public function test_studentguardianpanel_requires_staff_auth(): void
    {
        $this->get('/schsettings/studentguardianpanel')->assertRedirect();
    }

    public function test_superadmin_can_view_student_guardian_panel_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/studentguardianpanel')
            ->assertOk()
            ->assertSee('Student / Guardian Panel', false)
            ->assertSee('name="student_panel_login"', false)
            ->assertSee('name="student_login[]"', false)
            ->assertSee('name="parent_login[]"', false);
    }

    public function test_studentguardian_persists_flags_and_json_options(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/studentguardian', [
            'sch_id' => $id,
            'student_panel_login' => '1',
            'parent_panel_login' => '1',
            'student_login' => ['admission_no', 'email'],
            'parent_login' => ['mobile_number'],
            'student_timeline' => 'enabled',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('sch_settings')->where('id', $id)->first();
        $this->assertSame(1, (int) $row->student_panel_login);
        $this->assertSame(1, (int) $row->parent_panel_login);
        $this->assertSame('enabled', $row->student_timeline);
        $this->assertSame(['admission_no', 'email'], json_decode((string) $row->student_login, true));
        $this->assertSame(['mobile_number'], json_decode((string) $row->parent_login, true));
    }

    public function test_unchecked_options_match_ci_encoding(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/studentguardian', [
            'sch_id' => $id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('sch_settings')->where('id', $id)->first();
        $this->assertSame(0, (int) $row->student_panel_login);
        $this->assertSame(0, (int) $row->parent_panel_login);
        $this->assertSame('disabled', $row->student_timeline);
        $this->assertSame('false', $row->student_login);
        $this->assertSame('false', $row->parent_login);
    }
}
