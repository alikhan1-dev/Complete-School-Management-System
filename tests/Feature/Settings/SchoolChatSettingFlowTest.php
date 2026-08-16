<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolChatSettingFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $settingsSnapshot = null;

    private ?int $chatModulePrevious = null;

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->settingsSnapshot = (array) $row;

        $chat = DB::table('permission_group')->where('short_code', 'chat')->first();
        $this->assertNotNull($chat, 'permission_group chat row is required for chatsetting parity');
        $this->chatModulePrevious = (int) $chat->is_active;
        DB::table('permission_group')->where('short_code', 'chat')->update(['is_active' => 1]);
    }

    protected function tearDown(): void
    {
        if ($this->chatModulePrevious !== null) {
            DB::table('permission_group')->where('short_code', 'chat')->update([
                'is_active' => $this->chatModulePrevious,
            ]);
            $this->chatModulePrevious = null;
        }

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

        $token = uniqid('schchat', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CH-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Chat',
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

    public function test_chatsetting_requires_staff_auth(): void
    {
        $this->get('/schsettings/chatsetting')->assertRedirect();
    }

    public function test_chatsetting_forbidden_when_chat_module_disabled(): void
    {
        $this->actingAsSuperAdmin();
        DB::table('permission_group')->where('short_code', 'chat')->update(['is_active' => 0]);

        $this->get('/schsettings/chatsetting')->assertForbidden();
    }

    public function test_superadmin_can_view_chat_setting_form_when_module_active(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/chatsetting')
            ->assertOk()
            ->assertSee('name="student_delete_chat"', false)
            ->assertSee('name="guardian_delete_chat"', false)
            ->assertSee('name="staff_delete_chat"', false);
    }

    public function test_savechatsetting_persists_delete_flags(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savechatsetting', [
            'sch_id' => $id,
            'student_delete_chat' => '1',
            'guardian_delete_chat' => '1',
            'staff_delete_chat' => '1',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('sch_settings', [
            'id' => $id,
            'student_delete_chat' => 1,
            'guardian_delete_chat' => 1,
            'staff_delete_chat' => 1,
        ]);
    }

    public function test_savechatsetting_clears_unchecked_flags(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        DB::table('sch_settings')->where('id', $id)->update([
            'student_delete_chat' => 1,
            'guardian_delete_chat' => 1,
            'staff_delete_chat' => 1,
        ]);

        $this->postJson('/schsettings/savechatsetting', [
            'sch_id' => $id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('sch_settings')->where('id', $id)->first();
        $this->assertSame(0, (int) $row->student_delete_chat);
        $this->assertSame(0, (int) $row->guardian_delete_chat);
        $this->assertSame(0, (int) $row->staff_delete_chat);
    }
}
