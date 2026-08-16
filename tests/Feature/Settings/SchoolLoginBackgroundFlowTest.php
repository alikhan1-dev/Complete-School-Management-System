<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SchoolLoginBackgroundFlowTest extends TestCase
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
        File::ensureDirectoryExists(public_path('uploads/school_content/login_image'));
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

        $token = uniqid('schbg', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'BG-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Login',
            'surname' => 'Background',
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

    public function test_login_background_page_requires_staff_auth(): void
    {
        $this->get('/schsettings/login_page_background')->assertRedirect();
    }

    public function test_superadmin_can_view_login_background_page(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/login_page_background')
            ->assertOk()
            ->assertSee('Admin Panel', false)
            ->assertSee('User Panel', false);
    }

    public function test_invalid_extension_rejected_with_ci_json_shape(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/schsettings/add_admin_login_background', [
                'id' => $id,
                'logo_type' => 'admin_logo',
                'file' => UploadedFile::fake()->create('bg.gif', 10, 'image/gif'),
            ])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'error' => ['file']]);
    }

    public function test_admin_and_user_login_background_uploads(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/schsettings/add_admin_login_background', [
                'id' => $id,
                'logo_type' => 'admin_logo',
                'file' => UploadedFile::fake()->image('admin-bg.jpg', 200, 150),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $admin = (string) DB::table('sch_settings')->where('id', $id)->value('admin_login_page_background');
        $this->assertNotSame('', $admin);
        $this->assertStringContainsString('!', $admin);
        $adminPath = public_path('uploads/school_content/login_image/'.$admin);
        $this->assertTrue(is_file($adminPath));
        $this->cleanupFiles[] = $adminPath;

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/schsettings/add_admin_login_background', [
                'id' => $id,
                'logo_type' => 'user_login',
                'file' => UploadedFile::fake()->image('user-bg.png', 200, 150),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $user = (string) DB::table('sch_settings')->where('id', $id)->value('user_login_page_background');
        $this->assertNotSame('', $user);
        $userPath = public_path('uploads/school_content/login_image/'.$user);
        $this->assertTrue(is_file($userPath));
        $this->cleanupFiles[] = $userPath;
    }
}
