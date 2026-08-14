<?php

namespace Tests\Feature\Communication;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MailSmsGroupFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupMessageIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupMessageIds !== []) {
            DB::table('email_attachments')->whereIn('message_id', $this->cleanupMessageIds)->delete();
            DB::table('messages')->whereIn('id', $this->cleanupMessageIds)->delete();
        }
        $this->cleanupMessageIds = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];

        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
        }
        $this->cleanupSectionIds = [];

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

        $token = uniqid('ms', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'MS-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Mail',
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

        return $roleId;
    }

    public function test_mailsms_log_requires_staff_auth(): void
    {
        $this->get('/admin/mailsms')->assertRedirect();
    }

    public function test_superadmin_can_persist_group_email_without_live_send(): void
    {
        $roleId = $this->actingAsSuperAdmin();

        $this->get('/admin/mailsms')->assertOk();
        $this->get('/admin/mailsms/compose')->assertOk();

        $title = 'MS '.uniqid('', true);
        $this->post('/admin/mailsms/send_group', [
            'group_title' => $title,
            'group_message' => 'Group body, log only.',
            'group_send_by' => 'email',
            'send_type' => 'send_now',
            'user' => [(string) $roleId],
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(1, (int) $row->is_group);
        $this->assertSame(1, (int) $row->send_mail);
        $this->assertSame(0, (int) $row->send_sms);
        $this->assertSame(0, (int) $row->is_schedule);
        $this->assertNotEmpty($row->user_list);

        $this->get('/admin/mailsms')->assertOk()->assertSee($title, false);
    }

    public function test_superadmin_can_persist_group_sms_without_live_send(): void
    {
        $roleId = $this->actingAsSuperAdmin();

        $this->get('/admin/mailsms/compose_sms')->assertOk();

        $title = 'MSSMS '.uniqid('', true);
        $this->post('/admin/mailsms/send_group_sms', [
            'group_title' => $title,
            'group_message' => 'Group SMS body, log only.',
            'group_send_by' => ['sms'],
            'send_type' => 'send_now',
            'user' => [(string) $roleId],
            'group_template_id' => 'IND-1',
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(1, (int) $row->is_group);
        $this->assertSame(0, (int) $row->send_mail);
        $this->assertSame(1, (int) $row->send_sms);
        $this->assertSame('IND-1', $row->template_id);
        $this->assertStringContainsString('sms', (string) $row->send_through);
    }

    public function test_group_email_requires_title_and_recipients(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/compose')->post('/admin/mailsms/send_group', [
            'group_title' => '',
            'group_message' => 'x',
            'send_type' => 'send_now',
        ])->assertRedirect('/admin/mailsms/compose');
    }

    public function test_superadmin_can_search_staff_recipients(): void
    {
        $this->actingAsSuperAdmin();

        $this->post('/admin/mailsms/search', [
            'keyword' => 'Mail',
            'category' => 'staff',
        ])->assertOk()
            ->assertJsonFragment(['name' => 'Mail']);
    }

    public function test_superadmin_can_persist_individual_email_without_live_send(): void
    {
        $this->actingAsSuperAdmin();
        $staffId = (int) end($this->createdStaffIds);
        $staff = DB::table('staff')->where('id', $staffId)->first();

        $this->get('/admin/mailsms/compose')->assertOk()->assertSee('Individual', false);

        $title = 'MSI '.uniqid('', true);
        $userList = json_encode([
            'staff-'.$staffId => [[
                'category' => 'staff',
                'record_id' => (string) $staffId,
                'email' => (string) $staff->email,
                'guardianEmail' => '',
                'mobileno' => (string) ($staff->contact_no ?? ''),
            ]],
        ]);

        $this->post('/admin/mailsms/send_individual', [
            'individual_title' => $title,
            'individual_message' => 'Individual body, log only.',
            'individual_send_by' => 'email',
            'individual_send_type' => 'send_now',
            'user_list' => $userList,
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(0, (int) $row->is_group);
        $this->assertSame(1, (int) $row->is_individual);
        $this->assertSame(1, (int) $row->send_mail);
        $this->assertSame(0, (int) $row->send_sms);
        $this->assertSame(0, (int) $row->is_schedule);
        $this->assertStringContainsString((string) $staffId, (string) $row->user_list);

        $this->get('/admin/mailsms')->assertOk()->assertSee($title, false);
    }

    public function test_individual_email_requires_recipients(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/compose')->post('/admin/mailsms/send_individual', [
            'individual_title' => 'No recipients',
            'individual_message' => 'x',
            'individual_send_by' => 'email',
            'individual_send_type' => 'send_now',
            'user_list' => '',
        ])->assertRedirect('/admin/mailsms/compose');
    }

    public function test_superadmin_can_persist_individual_sms_without_live_send(): void
    {
        $this->actingAsSuperAdmin();
        $staffId = (int) end($this->createdStaffIds);
        $staff = DB::table('staff')->where('id', $staffId)->first();

        $this->get('/admin/mailsms/compose_sms')->assertOk()->assertSee('Individual', false);

        $title = 'MSISMS '.uniqid('', true);
        $userList = json_encode([
            'staff-'.$staffId => [[
                'category' => 'staff',
                'record_id' => (string) $staffId,
                'email' => (string) $staff->email,
                'guardianEmail' => '',
                'mobileno' => (string) ($staff->contact_no ?? ''),
                'app_key' => '',
            ]],
        ]);

        $this->post('/admin/mailsms/send_individual_sms', [
            'individual_title' => $title,
            'individual_message' => 'Individual SMS body, log only.',
            'individual_send_by' => ['sms', 'push'],
            'individual_send_type' => 'send_now',
            'individual_template_id' => 'IND-SMS-1',
            'user_list' => $userList,
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(0, (int) $row->is_group);
        $this->assertSame(1, (int) $row->is_individual);
        $this->assertSame(0, (int) $row->send_mail);
        $this->assertSame(1, (int) $row->send_sms);
        $this->assertSame(0, (int) $row->is_schedule);
        $this->assertSame('IND-SMS-1', $row->template_id);
        $this->assertStringContainsString('sms', (string) $row->send_through);
        $this->assertStringContainsString('push', (string) $row->send_through);
        $this->assertStringContainsString((string) $staffId, (string) $row->user_list);

        $this->get('/admin/mailsms')->assertOk()->assertSee($title, false);
    }

    public function test_individual_sms_requires_recipients_and_send_through(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/compose_sms')->post('/admin/mailsms/send_individual_sms', [
            'individual_title' => 'No recipients',
            'individual_message' => 'x',
            'individual_send_type' => 'send_now',
            'user_list' => '',
        ])->assertRedirect('/admin/mailsms/compose_sms');
    }

    public function test_superadmin_can_persist_class_email_without_live_send(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid('', true);
        $sessionId = (int) (DB::table('sch_settings')->value('session_id') ?: DB::table('sessions')->value('id'));
        $this->assertGreaterThan(0, $sessionId);

        $classId = (int) DB::table('classes')->insertGetId(['class' => 'MSCL-'.$suffix]);
        $this->cleanupClassIds[] = $classId;
        $sectionId = (int) DB::table('sections')->insertGetId(['section' => 'A'.$suffix]);
        $this->cleanupSectionIds[] = $sectionId;
        DB::table('class_sections')->insert([
            'class_id' => $classId,
            'section_id' => $sectionId,
        ]);

        $admissionNo = 'MSADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Class',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $classId,
            'section_id' => $sectionId,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
            'email' => 'class-'.$suffix.'@example.test',
            'guardian_email' => 'dad-'.$suffix.'@example.test',
        ])->assertRedirect();

        $studentId = (int) DB::table('students')->where('admission_no', $admissionNo)->value('id');
        $this->assertGreaterThan(0, $studentId);
        $this->cleanupStudentIds[] = $studentId;

        $this->get('/admin/mailsms/compose')->assertOk()->assertSee('Class', false);

        $title = 'MSCL '.uniqid('', true);
        $this->post('/admin/mailsms/send_class', [
            'class_title' => $title,
            'class_message' => 'Class body, log only.',
            'class_id' => (string) $classId,
            'user' => [(string) $sectionId],
            'send_to' => ['student', 'parent'],
            'class_send_by' => 'email',
            'class_send_type' => 'send_now',
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(0, (int) $row->is_group);
        $this->assertSame(0, (int) $row->is_individual);
        $this->assertSame(1, (int) $row->is_class);
        $this->assertSame(1, (int) $row->send_mail);
        $this->assertSame(0, (int) $row->send_sms);
        $this->assertSame($classId, (int) $row->schedule_class);
        $this->assertStringContainsString((string) $sectionId, (string) $row->schedule_section);
        $this->assertStringContainsString('student', (string) $row->send_to);
        $this->assertStringContainsString((string) $studentId, (string) $row->user_list);

        $this->get('/admin/mailsms')->assertOk()->assertSee($title, false);
    }

    public function test_class_email_requires_class_section_and_send_to(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/compose')->post('/admin/mailsms/send_class', [
            'class_title' => 'No class',
            'class_message' => 'x',
            'class_send_by' => 'email',
            'class_send_type' => 'send_now',
        ])->assertRedirect('/admin/mailsms/compose');
    }

    public function test_superadmin_can_persist_class_sms_without_live_send(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid('', true);

        $classId = (int) DB::table('classes')->insertGetId(['class' => 'MSCLS-'.$suffix]);
        $this->cleanupClassIds[] = $classId;
        $sectionId = (int) DB::table('sections')->insertGetId(['section' => 'B'.$suffix]);
        $this->cleanupSectionIds[] = $sectionId;
        DB::table('class_sections')->insert([
            'class_id' => $classId,
            'section_id' => $sectionId,
        ]);

        $admissionNo = 'MSADMS'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'ClassSms',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $classId,
            'section_id' => $sectionId,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
            'mobileno' => '03004445566',
        ])->assertRedirect();

        $studentId = (int) DB::table('students')->where('admission_no', $admissionNo)->value('id');
        $this->assertGreaterThan(0, $studentId);
        $this->cleanupStudentIds[] = $studentId;

        $this->get('/admin/mailsms/compose_sms')->assertOk()->assertSee('Class', false);

        $title = 'MSCLSMS '.uniqid('', true);
        $this->post('/admin/mailsms/send_class_sms', [
            'class_title' => $title,
            'class_message' => 'Class SMS body, log only.',
            'class_id' => (string) $classId,
            'user' => [(string) $sectionId],
            'send_to' => ['student'],
            'class_send_by' => ['sms', 'push'],
            'class_send_type' => 'send_now',
            'class_template_id' => 'CLS-SMS-1',
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(1, (int) $row->is_class);
        $this->assertSame(0, (int) $row->send_mail);
        $this->assertSame(1, (int) $row->send_sms);
        $this->assertSame('CLS-SMS-1', $row->template_id);
        $this->assertStringContainsString('sms', (string) $row->send_through);
        $this->assertStringContainsString('push', (string) $row->send_through);
        $this->assertStringContainsString('app_key', (string) $row->user_list);
        $this->assertStringContainsString((string) $studentId, (string) $row->user_list);

        $this->get('/admin/mailsms')->assertOk()->assertSee($title, false);
    }

    public function test_class_sms_requires_send_through_and_class(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/compose_sms')->post('/admin/mailsms/send_class_sms', [
            'class_title' => 'No class',
            'class_message' => 'x',
            'class_send_type' => 'send_now',
        ])->assertRedirect('/admin/mailsms/compose_sms');
    }

    public function test_superadmin_can_persist_birthday_email_without_live_send(): void
    {
        $this->actingAsSuperAdmin();
        $staffId = (int) end($this->createdStaffIds);
        $staff = DB::table('staff')->where('id', $staffId)->first();
        DB::table('staff')->where('id', $staffId)->update(['dob' => date('Y-m-d')]);

        $this->get('/admin/mailsms/compose')
            ->assertOk()
            ->assertSee("Today's Birthday", false)
            ->assertSee('Mail', false)
            ->assertSee((string) $staff->employee_id, false);

        $title = 'MSBD '.uniqid('', true);
        $this->post('/admin/mailsms/send_birthday', [
            'birthday_title' => $title,
            'birthday_message' => 'Happy birthday, log only.',
            'birthday_send_by' => 'email',
            'user' => [(string) $staff->email],
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(1, (int) $row->is_group);
        $this->assertSame(0, (int) $row->is_class);
        $this->assertSame(1, (int) $row->send_mail);
        $this->assertSame(0, (int) $row->send_sms);
        $this->assertSame('[]', $row->group_list);
        $this->assertStringContainsString((string) $staff->email, (string) $row->user_list);

        $this->get('/admin/mailsms')->assertOk()->assertSee($title, false);
    }

    public function test_birthday_email_requires_recipients(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/compose')->post('/admin/mailsms/send_birthday', [
            'birthday_title' => 'No recipients',
            'birthday_message' => 'x',
            'birthday_send_by' => 'email',
        ])->assertRedirect('/admin/mailsms/compose');
    }

    public function test_superadmin_can_persist_birthday_sms_without_live_send(): void
    {
        $this->actingAsSuperAdmin();
        $staffId = (int) end($this->createdStaffIds);
        $phone = '0300'.substr(preg_replace('/\D/', '', uniqid('', true)), 0, 7);
        DB::table('staff')->where('id', $staffId)->update([
            'dob' => date('Y-m-d'),
            'contact_no' => $phone,
        ]);
        $staff = DB::table('staff')->where('id', $staffId)->first();

        $this->get('/admin/mailsms/compose_sms')
            ->assertOk()
            ->assertSee("Today's Birthday", false)
            ->assertSee('Mail', false)
            ->assertSee((string) $staff->employee_id, false);

        $title = 'MSBDSMS '.uniqid('', true);
        $this->post('/admin/mailsms/send_birthday_sms', [
            'birthday_title' => $title,
            'birthday_message' => 'Happy birthday SMS, log only.',
            'birthday_send_by' => ['sms', 'push'],
            'birthday_template_id' => 'BD-SMS-1',
            'user' => [$phone],
            'app-key' => [''],
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(1, (int) $row->is_group);
        $this->assertSame(0, (int) $row->send_mail);
        $this->assertSame(1, (int) $row->send_sms);
        $this->assertSame('[]', $row->group_list);
        $this->assertSame('BD-SMS-1', $row->template_id);
        $this->assertStringContainsString('sms', (string) $row->send_through);
        $this->assertStringContainsString($phone, (string) $row->user_list);

        $this->get('/admin/mailsms')->assertOk()->assertSee($title, false);
    }

    public function test_birthday_sms_requires_recipients_and_send_through(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/admin/mailsms/compose_sms')->post('/admin/mailsms/send_birthday_sms', [
            'birthday_title' => 'No recipients',
            'birthday_message' => 'x',
        ])->assertRedirect('/admin/mailsms/compose_sms');
    }

    public function test_schedule_log_requires_staff_auth(): void
    {
        $this->get('/admin/mailsms/schedule')->assertRedirect();
    }

    public function test_superadmin_can_list_edit_and_delete_scheduled_message(): void
    {
        $roleId = $this->actingAsSuperAdmin();

        $title = 'MSSCH '.uniqid('', true);
        $this->post('/admin/mailsms/send_group', [
            'group_title' => $title,
            'group_message' => 'Scheduled body, log only.',
            'group_send_by' => 'email',
            'send_type' => 'schedule',
            'schedule_date_time' => '2099-12-31 10:00',
            'user' => [(string) $roleId],
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;
        $this->assertSame(1, (int) $row->is_schedule);

        $this->get('/admin/mailsms/schedule')
            ->assertOk()
            ->assertSee('Schedule Email SMS Log', false)
            ->assertSee($title, false);

        $this->get('/admin/mailsms/edit_schedule/'.$row->id.'/schedule')
            ->assertOk()
            ->assertSee($title, false);

        $newTitle = $title.' edited';
        $this->post('/admin/mailsms/edit_schedule/'.$row->id.'/schedule', [
            'title' => $newTitle,
            'message' => 'Updated schedule body.',
            'schedule_date_time' => '2099-12-31 15:30',
        ])->assertRedirect(route('communication.mailsms.schedule'));

        $updated = DB::table('messages')->where('id', $row->id)->first();
        $this->assertSame($newTitle, $updated->title);
        $this->assertSame(1, (int) $updated->is_schedule);
        $this->assertSame(0, (int) $updated->sent);
        $this->assertStringContainsString('15:30', (string) $updated->schedule_date_time);

        $this->post('/admin/mailsms/delete_schedule', [
            'message_id' => (string) $row->id,
        ])->assertRedirect(route('communication.mailsms.schedule'));

        $this->assertNull(DB::table('messages')->where('id', $row->id)->first());
        $this->cleanupMessageIds = array_values(array_filter(
            $this->cleanupMessageIds,
            fn (int $id) => $id !== (int) $row->id
        ));
    }

    public function test_superadmin_can_update_group_email_schedule_recipients(): void
    {
        $roleId = $this->actingAsSuperAdmin();

        $title = 'MSGEU '.uniqid('', true);
        $this->post('/admin/mailsms/send_group', [
            'group_title' => $title,
            'group_message' => 'Scheduled group email.',
            'group_send_by' => 'email',
            'send_type' => 'schedule',
            'schedule_date_time' => '2099-12-31 10:00',
            'user' => [(string) $roleId],
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;

        $this->get('/admin/mailsms/edit_schedule/'.$row->id.'/schedule')
            ->assertOk()
            ->assertSee('update_group_schedule', false)
            ->assertSee($title, false);

        $newTitle = $title.' group-updated';
        $this->post('/admin/mailsms/update_group_schedule', [
            'message_id' => (string) $row->id,
            'group_title' => $newTitle,
            'group_message' => 'Updated group recipients.',
            'user' => [(string) $roleId],
            'schedule_date_time' => '2099-12-31 16:00',
        ])->assertRedirect(route('communication.mailsms.schedule'));

        $updated = DB::table('messages')->where('id', $row->id)->first();
        $this->assertSame($newTitle, $updated->title);
        $this->assertSame(1, (int) $updated->is_schedule);
        $this->assertSame(0, (int) $updated->sent);
        $this->assertStringContainsString((string) $roleId, (string) $updated->group_list);
        $this->assertNotEmpty($updated->user_list);
        $this->assertStringContainsString('16:00', (string) $updated->schedule_date_time);
    }

    public function test_superadmin_can_update_group_sms_schedule(): void
    {
        $roleId = $this->actingAsSuperAdmin();

        $title = 'MSGSU '.uniqid('', true);
        $this->post('/admin/mailsms/send_group_sms', [
            'group_title' => $title,
            'group_message' => 'Scheduled group SMS.',
            'group_send_by' => ['sms'],
            'send_type' => 'schedule',
            'schedule_date_time' => '2099-12-31 10:00',
            'user' => [(string) $roleId],
            'group_template_id' => 'OLD-1',
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;

        $this->get('/admin/mailsms/edit_schedule/'.$row->id.'/schedule')
            ->assertOk()
            ->assertSee('update_group_sms_schedule', false);

        $newTitle = $title.' sms-updated';
        $this->post('/admin/mailsms/update_group_sms_schedule', [
            'message_id' => (string) $row->id,
            'group_title' => $newTitle,
            'group_message' => 'Updated SMS group.',
            'user' => [(string) $roleId],
            'group_send_by' => ['sms', 'push'],
            'group_template_id' => 'NEW-1',
            'schedule_date_time' => '2099-12-31 17:00',
        ])->assertRedirect(route('communication.mailsms.schedule'));

        $updated = DB::table('messages')->where('id', $row->id)->first();
        $this->assertSame($newTitle, $updated->title);
        $this->assertSame(1, (int) $updated->is_schedule);
        $this->assertSame(0, (int) $updated->sent);
        $this->assertSame('NEW-1', $updated->template_id);
        $this->assertStringContainsString('sms', (string) $updated->send_through);
        $this->assertStringContainsString('push', (string) $updated->send_through);
        $this->assertStringContainsString((string) $roleId, (string) $updated->group_list);
    }

    public function test_superadmin_can_update_individual_email_schedule(): void
    {
        $this->actingAsSuperAdmin();
        $staffId = (int) end($this->createdStaffIds);
        $staff = DB::table('staff')->where('id', $staffId)->first();

        $userList = json_encode([
            'staff-'.$staffId => [[
                'category' => 'staff',
                'record_id' => (string) $staffId,
                'email' => (string) $staff->email,
                'guardianEmail' => '',
                'mobileno' => (string) ($staff->contact_no ?? ''),
            ]],
        ]);

        $title = 'MSIEU '.uniqid('', true);
        $this->post('/admin/mailsms/send_individual', [
            'individual_title' => $title,
            'individual_message' => 'Scheduled individual email.',
            'individual_send_by' => 'email',
            'individual_send_type' => 'schedule',
            'schedule_date_time' => '2099-12-31 10:00',
            'user_list' => $userList,
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;

        $this->get('/admin/mailsms/edit_schedule/'.$row->id.'/schedule')
            ->assertOk()
            ->assertSee('update_individual_schedule', false);

        $newTitle = $title.' ind-updated';
        $this->post('/admin/mailsms/update_individual_schedule', [
            'message_id' => (string) $row->id,
            'individual_title' => $newTitle,
            'individual_message' => 'Updated individual email.',
            'user_list' => $userList,
            'schedule_date_time' => '2099-12-31 18:00',
        ])->assertRedirect(route('communication.mailsms.schedule'));

        $updated = DB::table('messages')->where('id', $row->id)->first();
        $this->assertSame($newTitle, $updated->title);
        $this->assertSame(1, (int) $updated->is_schedule);
        $this->assertSame(0, (int) $updated->sent);
        $this->assertStringContainsString((string) $staffId, (string) $updated->user_list);
    }

    public function test_superadmin_can_update_class_email_schedule(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid('', true);

        $classId = (int) DB::table('classes')->insertGetId(['class' => 'MSCLU-'.$suffix]);
        $this->cleanupClassIds[] = $classId;
        $sectionId = (int) DB::table('sections')->insertGetId(['section' => 'U'.$suffix]);
        $this->cleanupSectionIds[] = $sectionId;
        DB::table('class_sections')->insert([
            'class_id' => $classId,
            'section_id' => $sectionId,
        ]);

        $admissionNo = 'MSADMU'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Sched',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $classId,
            'section_id' => $sectionId,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
            'email' => 'sched-'.$suffix.'@example.test',
            'guardian_email' => 'dads-'.$suffix.'@example.test',
        ])->assertRedirect();

        $studentId = (int) DB::table('students')->where('admission_no', $admissionNo)->value('id');
        $this->assertGreaterThan(0, $studentId);
        $this->cleanupStudentIds[] = $studentId;

        $title = 'MSCLU '.uniqid('', true);
        $this->post('/admin/mailsms/send_class', [
            'class_title' => $title,
            'class_message' => 'Scheduled class email.',
            'class_id' => (string) $classId,
            'user' => [(string) $sectionId],
            'send_to' => ['student'],
            'class_send_by' => 'email',
            'class_send_type' => 'schedule',
            'schedule_date_time' => '2099-12-31 10:00',
        ])->assertRedirect(route('communication.mailsms.index'));

        $row = DB::table('messages')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupMessageIds[] = (int) $row->id;

        $this->get('/admin/mailsms/edit_schedule/'.$row->id.'/schedule')
            ->assertOk()
            ->assertSee('update_class_schedule', false);

        $newTitle = $title.' class-updated';
        $this->post('/admin/mailsms/update_class_schedule', [
            'message_id' => (string) $row->id,
            'class_title' => $newTitle,
            'class_message' => 'Updated class email.',
            'class_id' => (string) $classId,
            'user' => [(string) $sectionId],
            'send_to' => ['student', 'parent'],
            'schedule_date_time' => '2099-12-31 19:00',
        ])->assertRedirect(route('communication.mailsms.schedule'));

        $updated = DB::table('messages')->where('id', $row->id)->first();
        $this->assertSame($newTitle, $updated->title);
        $this->assertSame(1, (int) $updated->is_schedule);
        $this->assertSame(0, (int) $updated->sent);
        $this->assertSame($classId, (int) $updated->schedule_class);
        $this->assertStringContainsString('parent', (string) $updated->send_to);
        $this->assertStringContainsString((string) $studentId, (string) $updated->user_list);
    }
}
