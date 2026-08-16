<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolMobileAppSettingFlowTest extends TestCase
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

        $token = uniqid('schmobile', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'MA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Mobile',
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

    public function test_mobileapp_requires_staff_auth(): void
    {
        $this->get('/schsettings/mobileapp')->assertRedirect();
    }

    public function test_superadmin_can_view_mobile_app_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/mobileapp')
            ->assertOk()
            ->assertSee('Mobile App', false)
            ->assertSee('name="mobile_api_url"', false)
            ->assertSee('name="app_primary_color_code"', false);
    }

    public function test_savemobileapp_persists_user_and_admin_fields(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savemobileapp', [
            'sch_id' => $id,
            'mobile_api_url' => 'https://user-api.example.test/v1',
            'app_primary_color_code' => '#111111',
            'app_secondary_color_code' => '#222222',
            'admin_mobile_api_url' => 'https://admin-api.example.test/v1',
            'admin_app_primary_color_code' => '#333333',
            'admin_app_secondary_color_code' => '#444444',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('sch_settings', [
            'id' => $id,
            'mobile_api_url' => 'https://user-api.example.test/v1',
            'app_primary_color_code' => '#111111',
            'app_secondary_color_code' => '#222222',
            'admin_mobile_api_url' => 'https://admin-api.example.test/v1',
            'admin_app_primary_color_code' => '#333333',
            'admin_app_secondary_color_code' => '#444444',
        ]);
    }
}
