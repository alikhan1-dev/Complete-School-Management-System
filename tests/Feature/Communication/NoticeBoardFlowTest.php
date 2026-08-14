<?php

namespace Tests\Feature\Communication;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NoticeBoardFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupNoticeIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupNoticeIds !== []) {
            DB::table('notification_roles')->whereIn('send_notification_id', $this->cleanupNoticeIds)->delete();
            DB::table('read_notification')->whereIn('notification_id', $this->cleanupNoticeIds)->delete();
            DB::table('send_notification')->whereIn('id', $this->cleanupNoticeIds)->delete();
        }
        $this->cleanupNoticeIds = [];

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

        $token = uniqid('nb', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'NB-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Notice',
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

    public function test_notice_board_requires_staff_auth(): void
    {
        $this->get('/admin/notification')->assertRedirect();
    }

    public function test_superadmin_can_create_and_list_a_notice(): void
    {
        $staffId = $this->actingAsSuperAdmin();

        $this->get('/admin/notification')->assertOk();
        $this->get('/admin/notification/add')->assertOk();

        $title = 'NB '.uniqid('', true);
        $this->post('/admin/notification/add', [
            'title' => $title,
            'message' => 'Board only, no send.',
            'date' => '2026-08-14',
            'publish_date' => '2026-08-14',
            'visible' => ['student', 'parent'],
        ])->assertRedirect(route('communication.notice.index'));

        $row = DB::table('send_notification')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupNoticeIds[] = (int) $row->id;
        $this->assertSame('Yes', $row->visible_student);
        $this->assertSame('Yes', $row->visible_parent);
        $this->assertSame('Yes', $row->visible_staff);
        $this->assertSame($staffId, (int) $row->created_id);
        $this->assertSame('', (string) $row->attachment);

        $roleIds = DB::table('notification_roles')
            ->where('send_notification_id', $row->id)
            ->pluck('role_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->assertContains(7, $roleIds);

        $this->get('/admin/notification')->assertOk()->assertSee($title, false);
    }

    public function test_create_requires_title_and_visible(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/notification/add')->post('/admin/notification/add', [
            'title' => '',
            'message' => 'x',
            'date' => '2026-08-14',
            'publish_date' => '2026-08-14',
        ])->assertRedirect('/admin/notification/add');
    }

    public function test_creator_can_delete_notice(): void
    {
        $this->actingAsSuperAdmin();

        $title = 'NBDEL '.uniqid('', true);
        $this->post('/admin/notification/add', [
            'title' => $title,
            'message' => 'to delete',
            'date' => '2026-08-14',
            'publish_date' => '2026-08-14',
            'visible' => ['student'],
        ])->assertRedirect();

        $row = DB::table('send_notification')->where('title', $title)->first();
        $this->assertNotNull($row);
        $id = (int) $row->id;

        $this->get('/admin/notification/delete/'.$id)->assertRedirect(route('communication.notice.index'));
        $this->assertNull(DB::table('send_notification')->where('id', $id)->first());
    }
}
