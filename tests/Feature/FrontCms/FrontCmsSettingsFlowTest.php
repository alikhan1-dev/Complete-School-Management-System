<?php

namespace Tests\Feature\FrontCms;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrontCmsSettingsFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $cmsSnapshot = null;

    private ?int $langSnapshot = null;

    protected function tearDown(): void
    {
        if ($this->cmsSnapshot !== null) {
            $id = $this->cmsSnapshot['id'] ?? null;
            $payload = $this->cmsSnapshot;
            unset($payload['id']);
            if ($id) {
                DB::table('front_cms_settings')->where('id', $id)->update($payload);
            }
            $this->cmsSnapshot = null;
        }

        if ($this->langSnapshot !== null) {
            DB::table('sch_settings')->where('id', 1)->update(['lang_id' => $this->langSnapshot]);
            $this->langSnapshot = null;
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

        $token = uniqid('cms', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CMS-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Cms',
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

    public function test_frontcms_settings_require_staff_auth(): void
    {
        $this->get('/admin/frontcms')->assertRedirect();
    }

    public function test_invalid_logo_extension_is_rejected(): void
    {
        $this->actingAsSuperAdmin();
        $row = DB::table('front_cms_settings')->orderBy('id')->first();
        $this->assertNotNull($row);

        $this->post('/admin/frontcms', [
            'id' => (string) $row->id,
            'theme' => 'default',
            'logo' => UploadedFile::fake()->create('logo.gif', 10, 'image/gif'),
        ])->assertOk()->assertSee('Extension not allowed.', false);
    }

    public function test_superadmin_can_save_frontcms_settings(): void
    {
        $this->actingAsSuperAdmin();
        $row = DB::table('front_cms_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->cmsSnapshot = (array) $row;
        $this->langSnapshot = (int) DB::table('sch_settings')->where('id', 1)->value('lang_id');

        $this->get('/admin/frontcms')->assertOk()->assertSee('Front CMS Setting', false);

        $this->post('/admin/frontcms', [
            'id' => (string) $row->id,
            'is_active_front_cms' => '1',
            'is_active_sidebar' => '1',
            'sidebar_options' => ['news', 'complain'],
            'sch_lang_id' => '4',
            'footer_text' => 'Footer persist test',
            'cookie_consent' => 'Cookie text',
            'google_analytics' => 'UA-TEST',
            'whatsapp_url' => 'https://wa.me/1',
            'fb_url' => 'https://facebook.com/school',
            'twitter_url' => '',
            'youtube_url' => '',
            'google_plus' => '',
            'linkedin_url' => '',
            'instagram_url' => '',
            'pinterest_url' => '',
            'contact_us_email' => '',
            'complain_form_email' => '',
            'theme' => 'yellow',
        ])->assertRedirect('/admin/frontcms');

        $saved = DB::table('front_cms_settings')->where('id', $row->id)->first();
        $this->assertSame(1, (int) $saved->is_active_front_cms);
        $this->assertSame(1, (int) $saved->is_active_sidebar);
        $this->assertSame(0, (int) $saved->is_active_rtl);
        $this->assertSame('yellow', $saved->theme);
        $this->assertSame('Footer persist test', $saved->footer_text);
        $this->assertSame(['news', 'complain'], json_decode((string) $saved->sidebar_options, true));
        $this->assertSame(4, (int) DB::table('sch_settings')->where('id', 1)->value('lang_id'));
    }
}
