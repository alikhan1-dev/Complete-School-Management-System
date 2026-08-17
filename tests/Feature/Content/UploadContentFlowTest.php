<?php

namespace Tests\Feature\Content;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UploadContentFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupUploadIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        if ($this->cleanupUploadIds !== []) {
            $rows = DB::table('upload_contents')->whereIn('id', $this->cleanupUploadIds)->get();
            foreach ($rows as $row) {
                $this->forgetFile((string) $row->dir_path, (string) $row->img_name);
                $this->forgetFile((string) $row->thumb_path, (string) $row->thumb_name);
            }
            DB::table('upload_contents')->whereIn('id', $this->cleanupUploadIds)->delete();
        }
        $this->cleanupUploadIds = [];

        if ($this->cleanupTypeIds !== []) {
            DB::table('content_types')->whereIn('id', $this->cleanupTypeIds)->delete();
        }
        $this->cleanupTypeIds = [];

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
        $this->cleanupFiles[] = public_path(trim(str_replace('\\', '/', $dir), '/').'/'.$name);
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('uc', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'UC-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Upload',
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

        return $staffId;
    }

    private function makeContentType(): int
    {
        $id = DB::table('content_types')->insertGetId([
            'name' => 'Utype'.uniqid(),
            'description' => 'test',
            'is_active' => 1,
        ]);
        $this->cleanupTypeIds[] = $id;

        return $id;
    }

    public function test_upload_page_requires_staff_auth(): void
    {
        $this->get('/admin/content/upload')->assertRedirect();
        $this->post('/admin/content/ajaxupload')->assertRedirect();
        $this->post('/admin/content/getuploaddata')->assertRedirect();
    }

    public function test_superadmin_can_view_upload_page(): void
    {
        $this->actingAsSuperAdmin();
        $this->makeContentType();

        $this->get('/admin/content/upload')
            ->assertOk()
            ->assertSee('Content List', false)
            ->assertSee('name="content_type"', false)
            ->assertSee('name="upload[]"', false);
    }

    public function test_ajaxupload_requires_content_type_and_file_or_url(): void
    {
        $this->actingAsSuperAdmin();

        $this->post('/admin/content/ajaxupload', ['url' => ''])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertSee('The Content Type field is required.', false)
            ->assertSee('Please Choose A File Or Enter YouTube Video Link', false);
    }

    public function test_superadmin_can_upload_list_update_download_and_delete(): void
    {
        $this->actingAsSuperAdmin();
        $typeId = $this->makeContentType();
        $file = UploadedFile::fake()->image('campus.jpg', 20, 20);

        $this->post('/admin/content/ajaxupload', [
            'content_type' => (string) $typeId,
            'url' => '',
            'upload' => [$file],
        ])->assertOk()->assertJsonPath('status', 1);

        $row = DB::table('upload_contents')->where('real_name', 'campus.jpg')->orderByDesc('id')->first();
        $this->assertNotNull($row);
        $this->cleanupUploadIds[] = (int) $row->id;
        $this->assertSame($typeId, (int) $row->content_type_id);
        $this->assertSame('uploads/school_content/material/media/', $row->dir_path);
        $this->assertTrue(is_file(public_path('uploads/school_content/material/media/'.$row->img_name)));

        $this->get('/admin/content/upload')
            ->assertOk()
            ->assertSee('campus.jpg', false);

        $list = $this->post('/admin/content/getuploaddata', [
            'data' => [
                'page' => 1,
                'search' => 'campus.jpg',
                'grid' => 0,
            ],
        ])->assertOk()->json();
        $this->assertArrayHasKey('content', $list);
        $this->assertArrayHasKey('navigation', $list);
        $this->assertStringContainsString('campus.jpg', $list['content']);
        $this->assertStringContainsString("class='pagination'", $list['navigation']);

        $this->post('/admin/content/ajaxupdate', [
            'id' => $row->id,
            'name' => 'campus-renamed.jpg',
            'content_type' => (string) $typeId,
        ])->assertOk()->assertJsonPath('status', 1);
        $this->assertSame('campus-renamed.jpg', DB::table('upload_contents')->where('id', $row->id)->value('real_name'));

        $this->get('/admin/content/download_content/'.$row->id)->assertOk();

        $this->post('/admin/content/delete', ['id' => $row->id])
            ->assertOk()
            ->assertJsonPath('status', 1);
        $this->assertNull(DB::table('upload_contents')->where('id', $row->id)->first());
        $this->cleanupUploadIds = [];
    }

    public function test_html_form_upload_redirects(): void
    {
        $this->actingAsSuperAdmin();
        $typeId = $this->makeContentType();
        $file = UploadedFile::fake()->image('formupload.jpg', 16, 16);

        $this->post('/admin/content/upload', [
            'content_type' => (string) $typeId,
            'url' => '',
            'upload' => [$file],
        ])->assertRedirect('/admin/content/upload');

        $row = DB::table('upload_contents')->where('real_name', 'formupload.jpg')->orderByDesc('id')->first();
        $this->assertNotNull($row);
        $this->cleanupUploadIds[] = (int) $row->id;
    }

    public function test_youtube_url_persists_without_live_oembed(): void
    {
        $this->actingAsSuperAdmin();
        $typeId = $this->makeContentType();
        $url = 'https://www.youtube.com/watch?v=dQw4w9wgGcQ';

        $this->post('/admin/content/ajaxupload', [
            'content_type' => (string) $typeId,
            'url' => $url,
        ])->assertOk()->assertJsonPath('status', 1);

        $row = DB::table('upload_contents')->where('vid_url', $url)->orderByDesc('id')->first();
        $this->assertNotNull($row);
        $this->cleanupUploadIds[] = (int) $row->id;
        $this->assertSame('video', $row->file_type);
    }

    public function test_delete_array_removes_rows(): void
    {
        $this->actingAsSuperAdmin();
        $typeId = $this->makeContentType();
        $staffId = (int) $this->createdStaffIds[0];

        $id = DB::table('upload_contents')->insertGetId([
            'content_type_id' => $typeId,
            'real_name' => 'bulk.txt',
            'img_name' => '',
            'file_type' => 'txt',
            'mime_type' => 'text/plain',
            'file_size' => '1',
            'thumb_name' => '',
            'thumb_path' => 'uploads/school_content/material/media/thumb/',
            'dir_path' => 'uploads/school_content/material/media/',
            'vid_url' => '',
            'vid_title' => '',
            'upload_by' => $staffId,
        ]);
        $this->cleanupUploadIds[] = $id;

        $this->post('/admin/content/delete_array', ['id' => [$id]])
            ->assertOk()
            ->assertJsonPath('status', 1);
        $this->assertNull(DB::table('upload_contents')->where('id', $id)->first());
        $this->cleanupUploadIds = [];
    }
}
