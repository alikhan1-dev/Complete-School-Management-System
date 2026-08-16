<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolBackendThemeFlowTest extends TestCase
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

        $token = uniqid('schtheme', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TH-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Theme',
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

    public function test_backendtheme_requires_staff_auth(): void
    {
        $this->get('/schsettings/backendtheme')->assertRedirect();
    }

    public function test_superadmin_can_view_backend_theme_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/backendtheme')
            ->assertOk()
            ->assertSee('Backend Theme', false)
            ->assertSee('name="theme_color"', false)
            ->assertSee('name="theme_background"', false);
    }

    public function test_savebackendtheme_json_validation_matches_ci_shape(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/schsettings/savebackendtheme', [])
            ->assertOk()
            ->assertJsonPath('status', 'fail')
            ->assertJsonStructure(['status', 'error' => ['theme']]);
    }

    public function test_savebackendtheme_persists_theme_columns(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savebackendtheme', [
            'sch_id' => $id,
            'theme_color' => '#2092EC',
            'theme_shadow' => 'shadow-applied',
            'theme_background' => 'dark',
            'theme_content' => 'container-xxl',
            'theme_type' => 'default',
            'theme_navigation' => 'collapsed',
            'theme_font_color' => '#fff',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('sch_settings', [
            'id' => $id,
            'theme_color' => '#2092EC',
            'theme_shadow' => 'shadow-applied',
            'theme_background' => 'dark',
            'theme_content' => 'container-xxl',
            'theme_type' => 'default',
            'theme_navigation' => 'collapsed',
            'theme_font_color' => '#fff',
        ]);

        $this->assertSame('#2092EC', session('admin.theme.theme_color'));
    }

    public function test_empty_theme_shadow_is_cleared(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savebackendtheme', [
            'sch_id' => $id,
            'theme_color' => '#7367f0',
            'theme_shadow' => '',
            'theme_background' => 'light-mode',
            'theme_content' => 'container-fluid',
            'theme_type' => 'default',
            'theme_navigation' => 'expanded',
            'theme_font_color' => '#fff',
        ])->assertOk()->assertJsonPath('status', 'success');

        $shadow = DB::table('sch_settings')->where('id', $id)->value('theme_shadow');
        $this->assertTrue($shadow === null || $shadow === '');
    }
}
