<?php

namespace Tests\Feature\FrontCms;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FrontCmsMediaFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupMediaIds = [];

    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        if ($this->cleanupMediaIds !== []) {
            $rows = DB::table('front_cms_media_gallery')->whereIn('id', $this->cleanupMediaIds)->get();
            foreach ($rows as $row) {
                $this->forgetFile((string) $row->dir_path, (string) $row->img_name);
                $this->forgetFile((string) $row->thumb_path, (string) $row->thumb_name);
            }
            DB::table('front_cms_program_photos')->whereIn('media_gallery_id', $this->cleanupMediaIds)->delete();
            DB::table('front_cms_media_gallery')->whereIn('id', $this->cleanupMediaIds)->delete();
        }
        $this->cleanupMediaIds = [];

        foreach ($this->cleanupFiles as $path) {
            if (is_file($path)) {
                File::delete($path);
            }
        }
        $this->cleanupFiles = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function forgetFile(string $dir, string $name): void
    {
        $name = basename(str_replace('\\', '/', $name));
        if ($name === '') {
            return;
        }
        $path = public_path(trim(str_replace('\\', '/', $dir), '/').'/'.$name);
        $this->cleanupFiles[] = $path;
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('md', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'MD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Media',
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

    public function test_media_index_requires_staff_auth(): void
    {
        $this->get('/admin/front/media')->assertRedirect();
    }

    public function test_add_video_requires_url_or_file(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/front/media/addVideo', [])
            ->assertOk()
            ->assertJson([
                'status' => 0,
            ]);
    }

    public function test_superadmin_can_upload_list_and_delete_media(): void
    {
        $this->actingAsSuperAdmin();
        $this->get('/admin/front/media')->assertOk()->assertSee('Media Manager', false);
        $this->get('/admin/front/media/getMedia')->assertOk()->assertSee('Search By File Name', false);

        $file = UploadedFile::fake()->image('campus.jpg', 20, 20);
        $this->post('/admin/front/media/addImage', [
            'files' => [$file],
        ])->assertOk()->assertJson([
            'status' => 0,
            'msg' => 'Record saved successfully.',
        ]);

        $row = DB::table('front_cms_media_gallery')->where('image', 'campus.jpg')->orderByDesc('id')->first();
        $this->assertNotNull($row);
        $this->cleanupMediaIds[] = (int) $row->id;
        $this->assertSame('uploads/gallery/media/', $row->dir_path);
        $this->assertTrue(is_file(public_path('uploads/gallery/media/'.$row->img_name)));

        $this->get('/admin/front/media/getPage/1?keyword=campus.jpg')
            ->assertOk()
            ->assertJson(['result_status' => 1]);

        $this->post('/admin/front/media/addVideo', [
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9wgGcQ',
        ])->assertOk()->assertJson(['status' => 1]);

        $video = DB::table('front_cms_media_gallery')->where('file_type', 'video')->where('vid_url', 'https://www.youtube.com/watch?v=dQw4w9wgGcQ')->orderByDesc('id')->first();
        $this->assertNotNull($video);
        $this->cleanupMediaIds[] = (int) $video->id;

        $this->post('/admin/front/media/deleteItem', ['record_id' => (string) $row->id])
            ->assertOk()
            ->assertJson(['status' => 1]);
        $this->assertNull(DB::table('front_cms_media_gallery')->where('id', $row->id)->first());
        $this->cleanupMediaIds = array_values(array_filter($this->cleanupMediaIds, fn ($id) => $id !== (int) $row->id));
    }
}
