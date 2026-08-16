<?php

namespace Tests\Feature\Settings;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolGoogleDriveSettingFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $driveSnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('google_drive_setting')->where('id', 1)->first()
            ?? DB::table('google_drive_setting')->orderBy('id')->first();
        $this->assertNotNull($row, 'google_drive_setting row is required');
        $this->driveSnapshot = (array) $row;
    }

    protected function tearDown(): void
    {
        if ($this->driveSnapshot !== null) {
            $id = $this->driveSnapshot['id'];
            $payload = $this->driveSnapshot;
            unset($payload['id']);
            DB::table('google_drive_setting')->where('id', $id)->update($payload);
            $this->driveSnapshot = null;
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

        $token = uniqid('schgdrive', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'GD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Drive',
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

    public function test_googledrivesetting_requires_staff_auth(): void
    {
        $this->get('/schsettings/googledrivesetting')->assertRedirect();
    }

    public function test_superadmin_can_view_google_drive_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/googledrivesetting')
            ->assertOk()
            ->assertSee('Google Drive Setting', false)
            ->assertSee('name="client_id"', false)
            ->assertSee('name="api_key"', false);
    }

    public function test_savegoogledrive_validation_matches_ci_shape(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/schsettings/savegoogledrive', [
            'id' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('st', 1)
            ->assertJsonStructure([
                'st',
                'msg' => ['client_id', 'api_key', 'project_number', 'is_enable'],
            ]);
    }

    public function test_savegoogledrive_persists_enabled_flags(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) ($this->driveSnapshot['id'] ?? 1);

        $this->postJson('/schsettings/savegoogledrive', [
            'id' => $id,
            'client_id' => 'client-test-123',
            'api_key' => 'api-test-456',
            'project_number' => 'proj-789',
            'is_enable' => 'enabled',
            'is_student' => 'enabled',
            'is_parent' => 'disabled',
            'is_staff' => 'enabled',
        ])
            ->assertOk()
            ->assertJsonPath('st', 0);

        $this->assertDatabaseHas('google_drive_setting', [
            'id' => $id,
            'client_id' => 'client-test-123',
            'api_key' => 'api-test-456',
            'project_number' => 'proj-789',
            'is_enable' => 'enabled',
            'is_student' => 'enabled',
            'is_parent' => 'disabled',
            'is_staff' => 'enabled',
        ]);
    }

    public function test_savegoogledrive_can_disable_all(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) ($this->driveSnapshot['id'] ?? 1);

        $this->postJson('/schsettings/savegoogledrive', [
            'id' => $id,
            'client_id' => 'client-off',
            'api_key' => 'api-off',
            'project_number' => 'proj-off',
            'is_enable' => 'disabled',
            'is_student' => 'disabled',
            'is_parent' => 'disabled',
            'is_staff' => 'disabled',
        ])
            ->assertOk()
            ->assertJsonPath('st', 0);

        $this->assertDatabaseHas('google_drive_setting', [
            'id' => $id,
            'is_enable' => 'disabled',
            'is_student' => 'disabled',
            'is_parent' => 'disabled',
            'is_staff' => 'disabled',
        ]);
    }
}
