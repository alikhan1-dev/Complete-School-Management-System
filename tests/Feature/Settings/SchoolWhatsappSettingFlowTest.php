<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolWhatsappSettingFlowTest extends TestCase
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

        $token = uniqid('schwa', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'WA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'WhatsApp',
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

    public function test_whatsappsettings_requires_staff_auth(): void
    {
        $this->get('/schsettings/whatsappsettings')->assertRedirect();
    }

    public function test_superadmin_can_view_whatsapp_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/whatsappsettings')
            ->assertOk()
            ->assertSee('Whatsapp Settings', false)
            ->assertSee('name="front_side_whatsapp"', false)
            ->assertSee('name="admin_panel_whatsapp_mobile"', false);
    }

    public function test_savewhatsappsettings_requires_mobile_when_enabled(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savewhatsappsettings', [
            'sch_id' => $id,
            'front_side_whatsapp' => '1',
            'front_side_whatsapp_mobile' => '',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'fail')
            ->assertJsonStructure(['status', 'error' => ['front_side_whatsapp_mobile', 'time_to']]);
    }

    public function test_savewhatsappsettings_rejects_from_after_to(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savewhatsappsettings', [
            'sch_id' => $id,
            'front_side_whatsapp' => '1',
            'front_side_whatsapp_mobile' => '9999999999',
            'front_side_whatsapp_from' => '18:00:00',
            'front_side_whatsapp_to' => '09:00:00',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'fail');
    }

    public function test_savewhatsappsettings_persists_and_updates_admin_session(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savewhatsappsettings', [
            'sch_id' => $id,
            'front_side_whatsapp' => '1',
            'front_side_whatsapp_mobile' => '1111111111',
            'front_side_whatsapp_from' => '09:00:00',
            'front_side_whatsapp_to' => '17:00:00',
            'admin_panel_whatsapp' => '1',
            'admin_panel_whatsapp_mobile' => '2222222222',
            'admin_panel_whatsapp_from' => '08:00:00',
            'admin_panel_whatsapp_to' => '18:00:00',
            'student_panel_whatsapp' => '0',
            'student_panel_whatsapp_mobile' => '',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('sch_settings', [
            'id' => $id,
            'front_side_whatsapp' => 1,
            'front_side_whatsapp_mobile' => '1111111111',
            'admin_panel_whatsapp' => 1,
            'admin_panel_whatsapp_mobile' => '2222222222',
            'student_panel_whatsapp' => 0,
        ]);

        $this->assertSame('1', (string) session('admin.admin_panel_whatsapp'));
        $this->assertSame('2222222222', session('admin.admin_panel_whatsapp_mobile'));
        $this->assertSame('08:00:00', session('admin.admin_panel_whatsapp_from'));
    }
}
