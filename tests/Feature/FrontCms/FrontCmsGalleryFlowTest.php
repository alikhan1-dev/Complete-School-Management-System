<?php

namespace Tests\Feature\FrontCms;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrontCmsGalleryFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupProgramIds = [];

    /** @var list<int> */
    private array $cleanupMediaIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupProgramIds !== []) {
            DB::table('front_cms_program_photos')->whereIn('program_id', $this->cleanupProgramIds)->delete();
            DB::table('front_cms_programs')->whereIn('id', $this->cleanupProgramIds)->delete();
        }
        $this->cleanupProgramIds = [];

        if ($this->cleanupMediaIds !== []) {
            DB::table('front_cms_program_photos')->whereIn('media_gallery_id', $this->cleanupMediaIds)->delete();
            DB::table('front_cms_media_gallery')->whereIn('id', $this->cleanupMediaIds)->delete();
        }
        $this->cleanupMediaIds = [];

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

        $token = uniqid('gl', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'GL-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Gallery',
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

    private function insertMedia(string $name): int
    {
        $id = DB::table('front_cms_media_gallery')->insertGetId([
            'image' => 'uploads/gallery/'.$name,
            'thumb_path' => 'uploads/gallery/media_gallery/small/',
            'dir_path' => 'uploads/gallery/media_gallery/',
            'img_name' => $name,
            'thumb_name' => $name,
            'file_type' => 'image/jpeg',
            'file_size' => '1',
            'vid_url' => '',
            'vid_title' => '',
        ]);
        $this->cleanupMediaIds[] = $id;

        return $id;
    }

    public function test_gallery_index_requires_staff_auth(): void
    {
        $this->get('/admin/front/gallery')->assertRedirect();
    }

    public function test_create_requires_title_and_description(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/front/gallery/create', [])
            ->assertOk()
            ->assertSee('The Title field is required.', false)
            ->assertSee('The Description field is required.', false);
    }

    public function test_superadmin_can_create_edit_and_delete_gallery(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $title = 'Campus '.$suffix;
        $firstMedia = $this->insertMedia('g1-'.$suffix.'.jpg');
        $secondMedia = $this->insertMedia('g2-'.$suffix.'.jpg');

        $this->get('/admin/front/gallery')->assertOk()->assertSee('Gallery List', false);
        $this->get('/admin/front/gallery/create')->assertOk()->assertSee('Add Gallery', false);

        $this->post('/admin/front/gallery/create', [
            'title' => $title,
            'description' => '<p>Campus photos</p>',
            'meta_title' => 'Meta '.$suffix,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'sidebar' => '1',
            'image' => 'uploads/gallery/feature.jpg',
            'gallery_images' => [(string) $firstMedia, (string) $secondMedia],
        ])->assertRedirect('/admin/front/gallery');

        $row = DB::table('front_cms_programs')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupProgramIds[] = (int) $row->id;
        $this->assertSame('gallery', $row->type);
        $this->assertSame('read/'.$row->slug, $row->url);
        $this->assertSame(1, (int) $row->sidebar);
        $this->assertSame('uploads/gallery/feature.jpg', $row->feature_image);
        $photoIds = DB::table('front_cms_program_photos')->where('program_id', $row->id)->pluck('media_gallery_id')->map(fn ($id) => (int) $id)->all();
        $this->assertEqualsCanonicalizing([$firstMedia, $secondMedia], $photoIds);

        $this->get('/admin/front/gallery')->assertOk()->assertSee($title, false);
        $this->get('/admin/front/gallery/edit/'.$row->slug)->assertOk()->assertSee($title, false);

        $this->post('/admin/front/gallery/edit/'.$row->slug, [
            'id' => (string) $row->id,
            'title' => $title.' Edited',
            'description' => '<p>Updated</p>',
            'meta_title' => 'Meta '.$suffix,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'image' => '',
            'gallery_images' => [(string) $secondMedia],
        ])->assertRedirect('/admin/front/gallery');

        $updated = DB::table('front_cms_programs')->where('id', $row->id)->first();
        $this->assertSame($title.' Edited', $updated->title);
        $this->assertSame(0, (int) $updated->sidebar);
        $this->assertSame(
            [$secondMedia],
            DB::table('front_cms_program_photos')->where('program_id', $row->id)->pluck('media_gallery_id')->map(fn ($id) => (int) $id)->all()
        );

        $this->get('/admin/front/gallery/delete/'.$updated->slug)->assertRedirect('/admin/front/gallery');
        $this->assertNull(DB::table('front_cms_programs')->where('id', $row->id)->first());
        $this->cleanupProgramIds = [];
    }
}
