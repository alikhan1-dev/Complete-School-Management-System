<?php

namespace Tests\Feature\Communication;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MailSmsTemplateFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $emailIds = [];

    /** @var list<int> */
    private array $smsIds = [];

    protected function tearDown(): void
    {
        if ($this->emailIds !== []) {
            $files = DB::table('email_template_attachment')
                ->whereIn('email_template_id', $this->emailIds)
                ->pluck('attachment');
            foreach ($files as $file) {
                $path = public_path('uploads/communicate/email_template_images/'.basename((string) $file));
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            DB::table('email_template_attachment')->whereIn('email_template_id', $this->emailIds)->delete();
            DB::table('email_template')->whereIn('id', $this->emailIds)->delete();
        }
        $this->emailIds = [];

        if ($this->smsIds !== []) {
            DB::table('sms_template')->whereIn('id', $this->smsIds)->delete();
        }
        $this->smsIds = [];

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

        $token = uniqid('tpl', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TPL-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Tpl',
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

    public function test_email_template_list_requires_staff_auth(): void
    {
        $this->get('/admin/mailsms/email_template')->assertRedirect();
    }

    public function test_superadmin_can_crud_email_template_with_attachment(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/admin/mailsms/email_template')->assertOk()->assertSee('Email Template List', false);
        $this->get('/admin/mailsms/add_email_template')->assertOk();

        $title = 'ETPL '.uniqid('', true);
        $file = UploadedFile::fake()->create('note.txt', 1, 'text/plain');

        $this->post('/admin/mailsms/add_email_template', [
            'title' => $title,
            'message' => 'Hello from email template.',
            'files' => [$file],
        ])->assertRedirect(route('communication.mailsms.email_template'));

        $row = DB::table('email_template')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->emailIds[] = (int) $row->id;
        $this->assertSame('Hello from email template.', $row->message);

        $attachment = DB::table('email_template_attachment')->where('email_template_id', $row->id)->first();
        $this->assertNotNull($attachment);
        $this->assertSame('note.txt', $attachment->attachment_name);

        $this->get('/admin/mailsms/edit_email_template/'.$row->id)
            ->assertOk()
            ->assertSee($title, false);

        $newTitle = $title.' edited';
        $this->post('/admin/mailsms/update_email_template', [
            'id' => (string) $row->id,
            'title' => $newTitle,
            'message' => 'Updated template body.',
            'template_attachment' => [(int) $attachment->id => $attachment->attachment],
        ])->assertRedirect(route('communication.mailsms.email_template'));

        $updated = DB::table('email_template')->where('id', $row->id)->first();
        $this->assertSame($newTitle, $updated->title);
        $this->assertSame(1, (int) DB::table('email_template_attachment')->where('email_template_id', $row->id)->count());

        $this->post('/admin/mailsms/templatedata', [
            'template_id' => (string) $row->id,
        ])->assertOk()
            ->assertJsonPath('data.title', $newTitle)
            ->assertJsonPath('data.message', 'Updated template body.');

        $this->get('/admin/mailsms/delete_email_template/'.$row->id)
            ->assertRedirect(route('communication.mailsms.email_template'));

        $this->assertNull(DB::table('email_template')->where('id', $row->id)->first());
        $this->assertSame(0, (int) DB::table('email_template_attachment')->where('email_template_id', $row->id)->count());
        $this->emailIds = [];
    }

    public function test_email_template_requires_title_and_message(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/add_email_template')->post('/admin/mailsms/add_email_template', [
            'title' => '',
            'message' => '',
        ])->assertRedirect('/admin/mailsms/add_email_template');
    }

    public function test_sms_template_list_requires_staff_auth(): void
    {
        $this->get('/admin/mailsms/sms_template')->assertRedirect();
    }

    public function test_superadmin_can_crud_sms_template(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/admin/mailsms/sms_template')->assertOk()->assertSee('SMS Template List', false);
        $this->get('/admin/mailsms/sms_template/sms_template')->assertOk();
        $this->get('/admin/mailsms/add_sms_template')->assertOk();

        $title = 'STPL '.uniqid('', true);
        $this->post('/admin/mailsms/add_sms_template', [
            'title' => $title,
            'message' => 'Hello from SMS template.',
        ])->assertRedirect(route('communication.mailsms.sms_template'));

        $row = DB::table('sms_template')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->smsIds[] = (int) $row->id;

        $this->post('/admin/mailsms/edit_sms_template', [
            'id' => (string) $row->id,
        ])->assertOk()->assertJsonPath('status', 1);

        $newTitle = $title.' edited';
        $this->post('/admin/mailsms/update_sms_template', [
            'id' => (string) $row->id,
            'title' => $newTitle,
            'message' => 'Updated SMS body.',
        ])->assertRedirect(route('communication.mailsms.sms_template'));

        $updated = DB::table('sms_template')->where('id', $row->id)->first();
        $this->assertSame($newTitle, $updated->title);

        $this->post('/admin/mailsms/smstemplatedata', [
            'template_id' => (string) $row->id,
        ])->assertOk()
            ->assertJsonPath('data.title', $newTitle);

        $this->get('/admin/mailsms/delete_sms_template/'.$row->id)
            ->assertRedirect(route('communication.mailsms.sms_template'));

        $this->assertNull(DB::table('sms_template')->where('id', $row->id)->first());
        $this->smsIds = [];
    }

    public function test_sms_template_requires_title_and_message(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/add_sms_template')->post('/admin/mailsms/add_sms_template', [
            'title' => '',
            'message' => 'x',
        ])->assertRedirect('/admin/mailsms/add_sms_template');
    }
}
