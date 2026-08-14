<?php

namespace Tests\Feature\FrontCms;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrontCmsBannersFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupMediaIds = [];

    /** @var list<int> */
    private array $cleanupProgramIds = [];

    private bool $createdBannerProgram = false;

    protected function tearDown(): void
    {
        if ($this->cleanupMediaIds !== []) {
            DB::table('front_cms_program_photos')->whereIn('media_gallery_id', $this->cleanupMediaIds)->delete();
            DB::table('front_cms_media_gallery')->whereIn('id', $this->cleanupMediaIds)->delete();
        }
        $this->cleanupMediaIds = [];

        if ($this->createdBannerProgram && $this->cleanupProgramIds !== []) {
            DB::table('front_cms_program_photos')->whereIn('program_id', $this->cleanupProgramIds)->delete();
            DB::table('front_cms_programs')->whereIn('id', $this->cleanupProgramIds)->delete();
        }
        $this->cleanupProgramIds = [];
        $this->createdBannerProgram = false;

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

        $token = uniqid('bn', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'BN-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Banner',
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

    public function test_banner_index_requires_staff_auth(): void
    {
        $this->get('/admin/front/banner')->assertRedirect();
    }

    public function test_add_requires_content_id(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/front/banner/add', [])
            ->assertOk()
            ->assertJson([
                'status' => '0',
                'error' => 'Something Went Wrong',
            ]);
    }

    public function test_superadmin_can_add_and_remove_banner_image(): void
    {
        $this->actingAsSuperAdmin();
        $hadBanner = DB::table('front_cms_programs')->where('type', 'banner')->exists();

        $imgName = 'banner-'.uniqid().'.jpg';
        $mediaId = DB::table('front_cms_media_gallery')->insertGetId([
            'image' => 'uploads/gallery/'.$imgName,
            'thumb_path' => 'uploads/gallery/media_gallery/small/',
            'dir_path' => 'uploads/gallery/media_gallery/',
            'img_name' => $imgName,
            'thumb_name' => $imgName,
            'file_type' => 'image/jpeg',
            'file_size' => '1',
            'vid_url' => '',
            'vid_title' => '',
        ]);
        $this->cleanupMediaIds[] = $mediaId;

        $this->get('/admin/front/banner')->assertOk()->assertSee('Banner Images', false);

        $this->post('/admin/front/banner/add', ['content_id' => (string) $mediaId])
            ->assertOk()
            ->assertJson([
                'status' => '1',
                'error' => '',
            ]);

        $programId = (int) DB::table('front_cms_programs')->where('type', 'banner')->orderByDesc('created_at')->value('id');
        $this->assertGreaterThan(0, $programId);
        if (! $hadBanner) {
            $this->createdBannerProgram = true;
            $this->cleanupProgramIds[] = $programId;
        }

        $this->assertNotNull(
            DB::table('front_cms_program_photos')
                ->where('program_id', $programId)
                ->where('media_gallery_id', $mediaId)
                ->first()
        );

        $this->get('/admin/front/banner')->assertOk()->assertSee($imgName, false);

        $this->post('/admin/front/banner/remove', ['content_id' => (string) $mediaId])
            ->assertOk()
            ->assertJson([
                'status' => '1',
                'error' => '',
            ]);

        $this->assertNull(
            DB::table('front_cms_program_photos')
                ->where('program_id', $programId)
                ->where('media_gallery_id', $mediaId)
                ->first()
        );
    }
}
