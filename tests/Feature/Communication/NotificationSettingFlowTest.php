<?php

namespace Tests\Feature\Communication;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationSettingFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $settingSnapshot = null;

    protected function tearDown(): void
    {
        if ($this->settingSnapshot !== null) {
            $id = $this->settingSnapshot['id'] ?? null;
            unset($this->settingSnapshot['id']);
            if ($id) {
                DB::table('notification_setting')->where('id', $id)->update($this->settingSnapshot);
            }
            $this->settingSnapshot = null;
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

        $token = uniqid('ns', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'NS-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Notify',
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

    public function test_notification_setting_requires_staff_auth(): void
    {
        $this->get('/admin/notification/setting')->assertRedirect();
    }

    public function test_superadmin_can_toggle_mail_flag(): void
    {
        $this->actingAsSuperAdmin();
        $row = DB::table('notification_setting')->orderBy('notification_order')->first();
        $this->assertNotNull($row);
        $this->settingSnapshot = (array) $row;

        $this->get('/admin/notification/setting')->assertOk();

        $id = (int) $row->id;
        $this->post('/admin/notification/setting', [
            'ids' => [$id],
            'mail_'.$id => '1',
        ])->assertRedirect(route('communication.notification_setting.index'));

        $fresh = DB::table('notification_setting')->where('id', $id)->first();
        $this->assertSame(1, (int) $fresh->is_mail);
        $this->assertSame(0, (int) $fresh->is_sms);
        $this->assertSame(0, (int) $fresh->is_notification);
    }

    public function test_superadmin_can_update_template_copy(): void
    {
        $this->actingAsSuperAdmin();
        $row = DB::table('notification_setting')->orderBy('notification_order')->first();
        $this->assertNotNull($row);
        $this->settingSnapshot = (array) $row;
        $id = (int) $row->id;

        $this->get('/admin/notification/template/'.$id)->assertOk();
        $this->get('/admin/notification/view_template/'.$id)->assertOk();

        $this->post('/admin/notification/savetemplate', [
            'temp_id' => $id,
            'template_subject' => 'Parity subject',
            'template_message' => 'Parity body {{student_name}}',
            'template_id' => 'T-PARITY',
        ])->assertRedirect(route('communication.notification_setting.index'));

        $fresh = DB::table('notification_setting')->where('id', $id)->first();
        $this->assertSame('Parity subject', $fresh->subject);
        $this->assertSame('Parity body {{student_name}}', $fresh->template);
        $this->assertSame('T-PARITY', $fresh->template_id);
    }
}
