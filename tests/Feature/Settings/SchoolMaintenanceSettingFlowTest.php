<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolMaintenanceSettingFlowTest extends TestCase
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

        $token = uniqid('schmaint', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'MN-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Maint',
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

    public function test_maintenance_requires_staff_auth(): void
    {
        $this->get('/schsettings/maintenance')->assertRedirect();
    }

    public function test_superadmin_can_view_maintenance_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/maintenance')
            ->assertOk()
            ->assertSee('Maintenance', false)
            ->assertSee('name="maintenance_mode"', false);
    }

    public function test_save_maintenance_enables_mode_with_ci_status_shape(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/save_maintenance', [
            'sch_id' => $id,
            'maintenance_mode' => '1',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('error', '');

        $this->assertDatabaseHas('sch_settings', [
            'id' => $id,
            'maintenance_mode' => 1,
        ]);
    }

    public function test_save_maintenance_clears_mode_when_unchecked(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        DB::table('sch_settings')->where('id', $id)->update(['maintenance_mode' => 1]);

        $this->postJson('/schsettings/save_maintenance', [
            'sch_id' => $id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertSame(0, (int) DB::table('sch_settings')->where('id', $id)->value('maintenance_mode'));
    }
}
