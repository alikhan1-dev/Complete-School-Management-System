<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SchoolLogoFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $settingsSnapshot = null;

    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->settingsSnapshot = (array) $row;
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->cleanupFiles = [];

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

        $token = uniqid('schlogo', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LOGO-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Logo',
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

    public function test_logo_page_requires_staff_auth(): void
    {
        $this->get('/schsettings/logo')->assertRedirect();
    }

    public function test_superadmin_can_view_logo_page(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/logo')
            ->assertOk()
            ->assertSee('Print Logo', false)
            ->assertSee('Admin Logo', false)
            ->assertSee('App Logo', false);
    }

    public function test_invalid_extension_is_rejected_with_ci_json_shape(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/schsettings/ajax_editlogo', [
                'id' => $id,
                'file' => UploadedFile::fake()->create('logo.gif', 10, 'image/gif'),
            ])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'error' => ['file', 'validate_storage']]);
    }

    public function test_print_logo_upload_persists_filename(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post('/schsettings/ajax_editlogo', [
                'id' => $id,
                'file' => UploadedFile::fake()->image('print-logo.jpg', 100, 80),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $saved = (string) DB::table('sch_settings')->where('id', $id)->value('image');
        $this->assertNotSame('', $saved);
        $this->assertStringContainsString('!', $saved);

        $path = public_path('uploads/school_content/logo/'.$saved);
        $this->assertTrue(is_file($path));
        $this->cleanupFiles[] = $path;
        $response->assertJsonFragment(['message' => 'Record Saved Successfully']);
    }

    public function test_admin_and_app_logo_uploads(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/schsettings/ajax_editadmin_adminlogo', [
                'id' => $id,
                'file' => UploadedFile::fake()->image('admin.png', 120, 40),
            ])->assertOk()->assertJsonPath('success', true);

        $admin = (string) DB::table('sch_settings')->where('id', $id)->value('admin_logo');
        $this->assertNotSame('', $admin);
        $this->cleanupFiles[] = public_path('uploads/school_content/admin_logo/'.$admin);

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/schsettings/ajax_editadmin_smalllogo', [
                'id' => $id,
                'file' => UploadedFile::fake()->image('small.jpeg', 32, 32),
            ])->assertOk()->assertJsonPath('success', true);

        $small = (string) DB::table('sch_settings')->where('id', $id)->value('admin_small_logo');
        $this->assertNotSame('', $small);
        $this->cleanupFiles[] = public_path('uploads/school_content/admin_small_logo/'.$small);

        File::ensureDirectoryExists(public_path('uploads/school_content/logo/app_logo'));

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/schsettings/ajax_applogo', [
                'id' => $id,
                'file' => UploadedFile::fake()->image('app.jpg', 200, 50),
            ])->assertOk()->assertJsonPath('success', true);

        $app = (string) DB::table('sch_settings')->where('id', $id)->value('app_logo');
        $this->assertNotSame('', $app);
        $this->cleanupFiles[] = public_path('uploads/school_content/logo/app_logo/'.$app);

        File::ensureDirectoryExists(public_path('uploads/school_content/logo/app_logo'));
        $this->assertTrue(is_file(public_path('uploads/school_content/logo/app_logo/'.$app)));
    }
}
