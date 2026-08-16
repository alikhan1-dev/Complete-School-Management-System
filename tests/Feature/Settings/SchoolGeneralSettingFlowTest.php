<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolGeneralSettingFlowTest extends TestCase
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

        $token = uniqid('schset', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SCH-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'School',
            'surname' => 'Settings',
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

    public function test_schsettings_requires_staff_auth(): void
    {
        $this->get('/schsettings')->assertRedirect();
    }

    public function test_superadmin_can_view_general_setting_form(): void
    {
        $this->actingAsSuperAdmin();
        $name = (string) DB::table('sch_settings')->orderBy('id')->value('name');

        $this->get('/schsettings')
            ->assertOk()
            ->assertSee('name="sch_name"', false)
            ->assertSee($name, false);
    }

    public function test_generalsetting_json_validation_matches_ci_shape(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/schsettings/generalsetting', [])
            ->assertOk()
            ->assertJsonPath('status', 'fail')
            ->assertJsonStructure(['status', 'error' => [
                'sch_session_id', 'sch_name', 'sch_phone', 'sch_start_month', 'sch_start_week',
                'sch_address', 'sch_email', 'sch_timezone', 'currency_place', 'currency_format',
                'sch_date_format', 'base_url', 'folder_path',
            ]]);
    }

    public function test_generalsetting_json_saves_and_clears_school_context(): void
    {
        $this->actingAsSuperAdmin();
        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($row);

        $newName = 'SchSet '.$row->id;
        $payload = [
            'sch_id' => $row->id,
            'sch_name' => $newName,
            'sch_dise_code' => (string) $row->dise_code,
            'sch_address' => (string) $row->address,
            'sch_phone' => (string) $row->phone,
            'sch_email' => (string) $row->email,
            'sch_session_id' => (string) $row->session_id,
            'sch_start_month' => (string) $row->start_month,
            'sch_date_format' => (string) $row->date_format,
            'sch_timezone' => (string) $row->timezone,
            'sch_start_week' => (string) $row->start_week,
            'currency_format' => (string) ($row->currency_format ?: '####.##'),
            'currency_place' => (string) ($row->currency_place ?: 'before_number'),
            'base_url' => (string) ($row->base_url ?: 'http://example.test'),
            'folder_path' => (string) ($row->folder_path ?: '/tmp'),
        ];

        $this->postJson('/schsettings/generalsetting', $payload)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('sch_settings', [
            'id' => $row->id,
            'name' => $newName,
        ]);
        $this->assertSame($newName, app(SchoolContext::class)->schoolName());
    }

    public function test_getSchsetting_returns_json_row(): void
    {
        $this->actingAsSuperAdmin();
        $name = (string) DB::table('sch_settings')->orderBy('id')->value('name');

        $this->getJson('/schsettings/getSchsetting')
            ->assertOk()
            ->assertJsonFragment(['name' => $name]);
    }
}
