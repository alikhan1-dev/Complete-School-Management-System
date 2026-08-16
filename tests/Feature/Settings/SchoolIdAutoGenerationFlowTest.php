<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolIdAutoGenerationFlowTest extends TestCase
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

        $token = uniqid('schidauto', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'IA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'IdAuto',
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

    public function test_idautogeneration_requires_staff_auth(): void
    {
        $this->get('/schsettings/idautogeneration')->assertRedirect();
    }

    public function test_superadmin_can_view_id_auto_generation_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/idautogeneration')
            ->assertOk()
            ->assertSee('ID Auto Generation', false)
            ->assertSee('name="adm_prefix"', false)
            ->assertSee('name="staffid_prefix"', false);
    }

    public function test_saveidautogeneration_validates_when_auto_insert_enabled(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/saveidautogeneration', [
            'sch_id' => $id,
            'adm_auto_insert' => '1',
            'adm_prefix' => '',
            'adm_start_from' => '12',
            'adm_no_digit' => '4',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'fail')
            ->assertJsonStructure([
                'status',
                'error' => [
                    'adm_start_from',
                    'adm_prefix',
                    'adm_no_digit',
                    'staffid_start_from',
                    'staffid_prefix',
                    'staffid_no_digit',
                ],
            ]);
    }

    public function test_saveidautogeneration_persists_and_resets_update_status_on_change(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        DB::table('sch_settings')->where('id', $id)->update([
            'adm_auto_insert' => 1,
            'adm_prefix' => 'OLD/',
            'adm_start_from' => '0001',
            'adm_no_digit' => 4,
            'adm_update_status' => 1,
            'staffid_auto_insert' => 1,
            'staffid_prefix' => 'ST/',
            'staffid_start_from' => '0001',
            'staffid_no_digit' => 4,
            'staffid_update_status' => 1,
        ]);

        $this->postJson('/schsettings/saveidautogeneration', [
            'sch_id' => $id,
            'adm_auto_insert' => '1',
            'adm_prefix' => 'NEW/',
            'adm_start_from' => '0100',
            'adm_no_digit' => '4',
            'staffid_auto_insert' => '1',
            'staffid_prefix' => 'ST/',
            'staffid_start_from' => '0001',
            'staffid_no_digit' => '4',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('sch_settings')->where('id', $id)->first();
        $this->assertSame('NEW/', $row->adm_prefix);
        $this->assertSame('0100', $row->adm_start_from);
        $this->assertSame(4, (int) $row->adm_no_digit);
        $this->assertSame(0, (int) $row->adm_update_status);
        $this->assertSame(1, (int) $row->staffid_update_status);
    }

    public function test_save_without_auto_insert_skips_conditional_rules(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/saveidautogeneration', [
            'sch_id' => $id,
            'adm_prefix' => 'X/',
            'adm_start_from' => '1',
            'adm_no_digit' => '1',
            'staffid_prefix' => 'Y/',
            'staffid_start_from' => '1',
            'staffid_no_digit' => '1',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('sch_settings')->where('id', $id)->first();
        $this->assertSame(0, (int) $row->adm_auto_insert);
        $this->assertSame(0, (int) $row->staffid_auto_insert);
        $this->assertSame(1, (int) $row->adm_update_status);
        $this->assertSame(1, (int) $row->staffid_update_status);
    }
}
