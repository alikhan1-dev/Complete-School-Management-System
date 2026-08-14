<?php

namespace Tests\Feature\Chat;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserChatFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $connectionIds = [];

    /** @var list<int> */
    private array $chatUserIds = [];

    private ?int $chatStudentPerm = null;

    private ?int $chatParentPerm = null;

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

        if ($this->chatStudentPerm !== null) {
            DB::table('permission_student')->where('short_code', 'chat')->update([
                'student' => $this->chatStudentPerm,
                'parent' => $this->chatParentPerm,
            ]);
            $this->chatStudentPerm = null;
            $this->chatParentPerm = null;
        }

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('users')->where('childs', (string) $studentId)->where('role', 'parent')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupUserIds !== []) {
            DB::table('users')->whereIn('id', $this->cleanupUserIds)->delete();
        }
        $this->cleanupUserIds = [];

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

    private function enableChatPortalPermission(): void
    {
        $row = DB::table('permission_student')->where('short_code', 'chat')->first();
        $this->assertNotNull($row);
        $this->chatStudentPerm = (int) $row->student;
        $this->chatParentPerm = (int) $row->parent;
        DB::table('permission_student')->where('short_code', 'chat')->update([
            'student' => 1,
            'parent' => 1,
        ]);
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('uchat', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'UCH-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Portal',
            'surname' => 'ChatAdmin',
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

    /**
     * @return array{student: Student, sessionId: int, staffId: int, parent: PortalUser}
     */
    private function seedStudentPortalContext(): array
    {
        $this->enableChatPortalPermission();
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-uchat']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'UCHS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'UCHC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'UCHADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Chat',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $user = PortalUser::query()
            ->where('user_id', $student->id)
            ->where('role', 'student')
            ->firstOrFail();
        $user->login_token = 'tok'.$suffix;
        $user->save();
        $this->cleanupUserIds[] = (int) $user->id;

        $parent = PortalUser::query()
            ->where('role', 'parent')
            ->where('childs', (string) $student->id)
            ->firstOrFail();
        $this->cleanupUserIds[] = (int) $parent->id;

        $peerToken = uniqid('uchpeer', true);
        $peerStaffId = DB::table('staff')->insertGetId([
            'employee_id' => 'UCHP-'.$peerToken,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'ChatPeerStaff',
            'surname' => 'User',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => $peerToken.'@example.test',
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
        $this->createdStaffIds[] = $peerStaffId;

        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => $studentSessionId]]);

        return [
            'student' => $student,
            'sessionId' => $studentSessionId,
            'staffId' => $peerStaffId,
            'parent' => $parent,
        ];
    }

    public function test_user_chat_index_requires_portal_auth(): void
    {
        $this->get('/user/chat')->assertRedirect();
    }

    public function test_student_can_open_chat_and_search_staff_only(): void
    {
        $ctx = $this->seedStudentPortalContext();

        $this->get('/user/chat')
            ->assertOk()
            ->assertSee('Chat System', false)
            ->assertSee('user/chat/adduser', false);

        $search = $this->post('/user/chat/searchuser', [
            'keyword' => 'ChatPeerStaff',
        ])->assertOk()->assertJsonPath('status', '1');

        $page = (string) $search->json('page');
        $this->assertStringContainsString('ChatPeerStaff', $page);
        $this->assertStringContainsString('data-user-type="Staff"', $page);
        $this->assertStringNotContainsString('data-user-type="Student"', $page);
        $this->assertSame($ctx['staffId'], (int) $ctx['staffId']);
    }

    public function test_student_can_add_staff_send_and_delete_message(): void
    {
        $ctx = $this->seedStudentPortalContext();

        $add = $this->post('/user/chat/adduser', [
            'user_id' => (string) $ctx['staffId'],
            'user_type' => 'Staff',
        ])->assertOk()->assertJsonPath('status', '1');

        $payload = $add->json();
        $connectionId = (int) $payload['chat_connection_id'];
        $chatToUser = (int) ($payload['new_user']['chat_user_id'] ?? 0);
        $this->assertGreaterThan(0, $connectionId);
        $this->connectionIds[] = $connectionId;

        $mine = DB::table('chat_users')
            ->where('student_id', $ctx['student']->id)
            ->where('user_type', 'student')
            ->first();
        $other = DB::table('chat_users')
            ->where('staff_id', $ctx['staffId'])
            ->where('user_type', 'staff')
            ->first();
        $this->assertNotNull($mine);
        $this->assertNotNull($other);
        $this->chatUserIds[] = (int) $mine->id;
        $this->chatUserIds[] = (int) $other->id;
        $this->assertSame((int) $other->id, $chatToUser);

        $this->post('/user/chat/myuser')->assertOk()->assertJsonPath('status', '1');

        $record = $this->post('/user/chat/getChatRecord', [
            'chat_connection_id' => (string) $connectionId,
        ])->assertOk()->assertJsonPath('status', '1');
        $this->assertSame($chatToUser, (int) $record->json('chat_to_user'));

        $send = $this->post('/user/chat/newMessage', [
            'chat_connection_id' => (string) $connectionId,
            'chat_to_user' => (string) $chatToUser,
            'message' => 'Hello from student chat.',
            'time' => date('Y-m-d H:i:s'),
        ])->assertOk()->assertJsonPath('status', '1');

        $msgId = (int) $send->json('last_insert_id');
        $this->assertGreaterThan(0, $msgId);
        $this->assertNotNull(DB::table('chat_messages')->where('id', $msgId)->first());

        $this->post('/user/chat/delete_msg', [
            'msg_id' => (string) $msgId,
        ])->assertOk();
        $this->assertNull(DB::table('chat_messages')->where('id', $msgId)->first());

        $this->post('/user/chat/get_student_parent_chat_msg_count')
            ->assertOk()
            ->assertJsonPath('status', '1');
    }

    public function test_parent_can_add_staff_contact(): void
    {
        $ctx = $this->seedStudentPortalContext();
        $this->actingAs($ctx['parent'], 'student_parent');
        session(['current_class' => ['student_session_id' => $ctx['sessionId']]]);

        $this->get('/user/chat')->assertOk()->assertSee('Chat System', false);

        $add = $this->post('/user/chat/adduser', [
            'user_id' => (string) $ctx['staffId'],
            'user_type' => 'Staff',
        ])->assertOk()->assertJsonPath('status', '1');

        $connectionId = (int) $add->json('chat_connection_id');
        $this->connectionIds[] = $connectionId;

        $mine = DB::table('chat_users')
            ->where('student_id', $ctx['student']->id)
            ->where('user_type', 'parent')
            ->first();
        $other = DB::table('chat_users')
            ->where('staff_id', $ctx['staffId'])
            ->where('user_type', 'staff')
            ->first();
        $this->assertNotNull($mine);
        $this->assertNotNull($other);
        $this->chatUserIds[] = (int) $mine->id;
        $this->chatUserIds[] = (int) $other->id;
    }

    public function test_adduser_requires_contact(): void
    {
        $this->seedStudentPortalContext();

        $this->post('/user/chat/adduser', [])
            ->assertOk()
            ->assertJsonPath('status', 0);
    }
}
