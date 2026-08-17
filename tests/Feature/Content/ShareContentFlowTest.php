<?php

namespace Tests\Feature\Content;

use App\Modules\Content\Support\EncLib;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShareContentFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupShareIds = [];

    /** @var list<int> */
    private array $cleanupUploadIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupShareIds !== []) {
            DB::table('share_contents')->whereIn('id', $this->cleanupShareIds)->delete();
        }
        $this->cleanupShareIds = [];

        if ($this->cleanupUploadIds !== []) {
            DB::table('upload_contents')->whereIn('id', $this->cleanupUploadIds)->delete();
        }
        $this->cleanupUploadIds = [];

        if ($this->cleanupTypeIds !== []) {
            DB::table('content_types')->whereIn('id', $this->cleanupTypeIds)->delete();
        }
        $this->cleanupTypeIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('sh', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SH-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Share',
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

    private function makeUpload(int $staffId): int
    {
        $typeId = DB::table('content_types')->insertGetId([
            'name' => 'Stype'.uniqid(),
            'description' => '',
            'is_active' => 1,
        ]);
        $this->cleanupTypeIds[] = $typeId;

        $uploadId = DB::table('upload_contents')->insertGetId([
            'content_type_id' => $typeId,
            'real_name' => 'shared.txt',
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
        $this->cleanupUploadIds[] = $uploadId;

        return $uploadId;
    }

    public function test_share_list_requires_staff_auth(): void
    {
        $this->get('/admin/content/list')->assertRedirect();
        $this->post('/admin/content/share')->assertRedirect();
        $this->post('/admin/content/getsharelist')->assertRedirect();
    }

    public function test_superadmin_can_view_share_list(): void
    {
        $this->actingAsSuperAdmin();
        $this->get('/admin/content/list')
            ->assertOk()
            ->assertSee('Content Share List', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="send_to"', false);
    }

    public function test_share_requires_title_date_contents_and_group(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/content/share', [
            'send_to' => 'group',
        ])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertSee('The Title field is required.', false)
            ->assertSee('The Share Date field is required.', false)
            ->assertSee('The Contents field is required.', false)
            ->assertSee('The Group field is required.', false);
    }

    public function test_superadmin_can_share_to_group_list_details_and_delete(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $uploadId = $this->makeUpload($staffId);
        $title = 'Share'.uniqid();

        $this->post('/admin/content/share', [
            'title' => $title,
            'share_date' => date('Y-m-d'),
            'valid_upto' => '',
            'description' => 'Group pack',
            'send_to' => 'group',
            'user' => ['student'],
            'selected_contents' => [$uploadId],
        ])->assertOk()->assertJsonPath('status', 1);

        $row = DB::table('share_contents')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupShareIds[] = (int) $row->id;
        $this->assertSame('group', $row->send_to);
        $this->assertSame('student', DB::table('share_content_for')->where('share_content_id', $row->id)->value('group_id'));
        $this->assertSame($uploadId, (int) DB::table('share_upload_contents')->where('share_content_id', $row->id)->value('upload_content_id'));

        $this->get('/admin/content/list')->assertOk()->assertSee($title, false);

        $json = $this->post('/admin/content/getsharelist', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'search' => ['value' => $title],
        ])->assertOk()->assertJsonPath('draw', 1)->json();
        $this->assertNotEmpty($json['data']);
        $this->assertSame($title, $json['data'][0][0]);
        $this->assertSame('Group', $json['data'][0][1]);

        $details = $this->post('/admin/content/getsharedcontents', [
            'share_content_id' => $row->id,
        ])->assertOk()->assertJsonPath('status', '1')->json();
        $this->assertStringContainsString($title, $details['page']);
        $this->assertStringContainsString('Student', $details['page']);
        $this->assertStringContainsString('shared.txt', $details['page']);

        $this->get('/admin/content/delete_content/'.$row->id)->assertRedirect('/admin/content/list');
        $this->assertNull(DB::table('share_contents')->where('id', $row->id)->first());
        $this->cleanupShareIds = [];
    }

    public function test_generate_url_creates_public_share_and_site_page(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $uploadId = $this->makeUpload($staffId);
        $title = 'Pub'.uniqid();

        $response = $this->post('/admin/content/generate_url', [
            'title' => $title,
            'share_date' => date('Y-m-d'),
            'valid_upto' => '',
            'description' => '',
            'selected_contents' => [$uploadId],
        ])->assertOk()->assertJsonPath('status', 1);

        $url = (string) $response->json('shared_url');
        $this->assertStringContainsString('site/share/', $url);

        $row = DB::table('share_contents')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupShareIds[] = (int) $row->id;
        $this->assertSame('public', $row->send_to);
        $this->assertSame($url, rtrim((string) config('app.url'), '/').'/site/share/'.EncLib::encrypt((string) $row->id));

        $this->get('/site/share/'.EncLib::encrypt((string) $row->id))
            ->assertOk()
            ->assertSee($title, false);
    }

    public function test_individual_staff_share_persists_staff_id(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $uploadId = $this->makeUpload($staffId);
        $title = 'Ind'.uniqid();
        $userList = json_encode([[
            ['category' => 'staff', 'record_id' => $staffId],
        ]]);

        $this->post('/admin/content/share', [
            'title' => $title,
            'share_date' => date('Y-m-d'),
            'send_to' => 'individual',
            'user_list' => $userList,
            'selected_contents' => [$uploadId],
        ])->assertOk()->assertJsonPath('status', 1);

        $row = DB::table('share_contents')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupShareIds[] = (int) $row->id;
        $this->assertSame($staffId, (int) DB::table('share_content_for')->where('share_content_id', $row->id)->value('staff_id'));
    }
}
