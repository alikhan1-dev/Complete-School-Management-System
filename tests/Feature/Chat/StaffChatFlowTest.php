<?php

namespace Tests\Feature\Chat;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffChatFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $connectionIds = [];

    /** @var list<int> */
    private array $chatUserIds = [];

    protected function tearDown(): void
    {
        if ($this->connectionIds !== []) {
            DB::table('chat_messages')->whereIn('chat_connection_id', $this->connectionIds)->delete();
            DB::table('chat_connections')->whereIn('id', $this->connectionIds)->delete();
        }
        $this->connectionIds = [];

        if ($this->chatUserIds !== []) {
            DB::table('chat_messages')->whereIn('chat_user_id', $this->chatUserIds)->delete();
            DB::table('chat_connections')->whereIn('chat_user_one', $this->chatUserIds)->orWhereIn('chat_user_two', $this->chatUserIds)->delete();
            DB::table('chat_users')->whereIn('id', $this->chatUserIds)->delete();
        }
        $this->chatUserIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function createStaff(string $name, int $roleId): int
    {
        $token = uniqid('chat', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CH-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => $name,
            'surname' => 'Chat',
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

        return $staffId;
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);
        $staffId = $this->createStaff('ChatAdmin', $roleId);
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');

        return $staffId;
    }

    public function test_chat_index_requires_staff_auth(): void
    {
        $this->get('/admin/chat')->assertRedirect();
    }

    public function test_superadmin_can_open_chat_and_search_staff(): void
    {
        $this->actingAsSuperAdmin();
        $this->get('/admin/chat')->assertOk()->assertSee('Chat System', false);

        $this->post('/admin/chat/searchuser', [
            'keyword' => 'ChatAdmin',
        ])->assertOk()->assertJsonPath('status', '1');
    }

    public function test_superadmin_can_add_contact_send_and_delete_message(): void
    {
        $adminId = $this->actingAsSuperAdmin();
        $roleId = (int) DB::table('staff_roles')->where('staff_id', $adminId)->value('role_id');
        $otherId = $this->createStaff('ChatPeer', $roleId);

        $add = $this->post('/admin/chat/adduser', [
            'user_id' => (string) $otherId,
            'user_type' => 'Staff',
        ])->assertOk()->assertJsonPath('status', '1');

        $payload = $add->json();
        $connectionId = (int) $payload['chat_connection_id'];
        $chatToUser = (int) $payload['new_user']['chat_user_id'];
        $this->assertGreaterThan(0, $connectionId);
        $this->connectionIds[] = $connectionId;

        $mine = DB::table('chat_users')->where('staff_id', $adminId)->where('user_type', 'staff')->first();
        $other = DB::table('chat_users')->where('staff_id', $otherId)->where('user_type', 'staff')->first();
        $this->assertNotNull($mine);
        $this->assertNotNull($other);
        $this->chatUserIds[] = (int) $mine->id;
        $this->chatUserIds[] = (int) $other->id;

        $this->post('/admin/chat/myuser')->assertOk()->assertJsonPath('status', '1');

        $record = $this->post('/admin/chat/getChatRecord', [
            'chat_connection_id' => (string) $connectionId,
        ])->assertOk()->assertJsonPath('status', '1');
        $this->assertSame($chatToUser, (int) $record->json('chat_to_user'));

        $send = $this->post('/admin/chat/newMessage', [
            'chat_connection_id' => (string) $connectionId,
            'chat_to_user' => (string) $chatToUser,
            'message' => 'Hello from staff chat.',
            'time' => date('Y-m-d H:i:s'),
        ])->assertOk()->assertJsonPath('status', '1');

        $msgId = (int) $send->json('last_insert_id');
        $this->assertGreaterThan(0, $msgId);
        $this->assertNotNull(DB::table('chat_messages')->where('id', $msgId)->first());

        $this->post('/admin/chat/delete_msg', [
            'msg_id' => (string) $msgId,
        ])->assertOk();
        $this->assertNull(DB::table('chat_messages')->where('id', $msgId)->first());
    }

    public function test_adduser_requires_contact(): void
    {
        $this->actingAsSuperAdmin();

        $this->post('/admin/chat/adduser', [])
            ->assertOk()
            ->assertJsonPath('status', 0);
    }
}
