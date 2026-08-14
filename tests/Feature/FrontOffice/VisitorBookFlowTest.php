<?php

namespace Tests\Feature\FrontOffice;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VisitorBookFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupVisitorIds = [];

    /** @var list<int> */
    private array $cleanupPurposeIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    private ?int $visitorStudentPerm = null;

    private ?int $visitorParentPerm = null;

    protected function tearDown(): void
    {
        if ($this->cleanupVisitorIds !== []) {
            DB::table('visitors_book')->whereIn('id', $this->cleanupVisitorIds)->delete();
        }
        $this->cleanupVisitorIds = [];

        if ($this->cleanupPurposeIds !== []) {
            DB::table('visitors_purpose')->whereIn('id', $this->cleanupPurposeIds)->delete();
        }
        $this->cleanupPurposeIds = [];

        if ($this->visitorStudentPerm !== null) {
            DB::table('permission_student')->where('short_code', 'visitor_book')->update([
                'student' => $this->visitorStudentPerm,
                'parent' => $this->visitorParentPerm,
            ]);
            $this->visitorStudentPerm = null;
            $this->visitorParentPerm = null;
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

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);
        $token = uniqid('vis', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'VIS-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Visitor',
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

    public function test_visitor_index_requires_staff_auth(): void
    {
        $this->get('/admin/visitors')->assertRedirect();
    }

    public function test_superadmin_can_add_view_edit_and_delete_staff_visitor(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $purpose = 'Meet'.$suffix;
        $purposeId = DB::table('visitors_purpose')->insertGetId([
            'visitors_purpose' => $purpose,
            'description' => 'test',
        ]);
        $this->cleanupPurposeIds[] = $purposeId;
        $name = 'Guest '.$suffix;
        $today = date('Y-m-d');

        $this->get('/admin/visitors')->assertOk()->assertSee('Visitor List', false);

        $this->post('/admin/visitors/add', [
            'purpose' => $purpose,
            'meeting_with' => 'staff',
            'staff_id' => (string) $staffId,
            'name' => $name,
            'contact' => '03001112233',
            'id_proof' => 'CNIC',
            'pepples' => '2',
            'date' => $today,
            'time' => '09:00 AM',
            'out_time' => '10:00 AM',
            'note' => 'Office visit',
        ])->assertOk()->assertJsonPath('status', 'success');

        $row = DB::table('visitors_book')->where('name', $name)->first();
        $this->assertNotNull($row);
        $this->cleanupVisitorIds[] = (int) $row->id;
        $this->assertSame($staffId, (int) $row->staff_id);
        $this->assertSame('staff', $row->meeting_with);

        $this->get('/admin/visitors')->assertOk()->assertSee($name, false);
        $this->get('/admin/visitors/details/'.$row->id)->assertOk()->assertSee($name, false);
        $this->get('/admin/visitors/staffvisitor')->assertOk()->assertSee($name, false);

        $this->post('/admin/visitors/editvisitor', [
            'visitorid' => (string) $row->id,
        ])->assertOk()->assertJsonStructure(['page']);

        $this->post('/admin/visitors/edit', [
            'visitor_id' => (string) $row->id,
            'purpose' => $purpose,
            'edit_meeting_with' => 'staff',
            'edit_staff_id' => (string) $staffId,
            'name' => $name.' Edited',
            'contact' => '03001112233',
            'id_proof' => 'CNIC',
            'pepples' => '3',
            'date' => $today,
            'time' => '09:00 AM',
            'out_time' => '10:00 AM',
            'note' => 'Updated',
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertSame($name.' Edited', DB::table('visitors_book')->where('id', $row->id)->value('name'));

        $this->post('/admin/visitors/delete', [
            'id' => (string) $row->id,
        ])->assertOk();
        $this->assertNull(DB::table('visitors_book')->where('id', $row->id)->first());
    }

    public function test_add_visitor_requires_purpose_meeting_name_and_date(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/visitors/add', [])
            ->assertOk()
            ->assertJsonPath('status', 'fail');
    }

    public function test_student_portal_lists_own_visitors(): void
    {
        $this->actingAsSuperAdmin();
        $row = DB::table('permission_student')->where('short_code', 'visitor_book')->first();
        $this->assertNotNull($row);
        $this->visitorStudentPerm = (int) $row->student;
        $this->visitorParentPerm = (int) $row->parent;
        DB::table('permission_student')->where('short_code', 'visitor_book')->update([
            'student' => 1,
            'parent' => 1,
        ]);

        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-vis']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        $section = Section::query()->create(['section' => 'VISS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'VISC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'VISADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Vis',
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

        $purpose = 'Stu'.$suffix;
        $this->cleanupPurposeIds[] = DB::table('visitors_purpose')->insertGetId([
            'visitors_purpose' => $purpose,
            'description' => 'test',
        ]);
        $visitorName = 'Parent Guest '.$suffix;
        $visitorId = DB::table('visitors_book')->insertGetId([
            'staff_id' => null,
            'student_session_id' => $studentSessionId,
            'purpose' => $purpose,
            'name' => $visitorName,
            'contact' => '03009998877',
            'id_proof' => '',
            'no_of_people' => 1,
            'date' => date('Y-m-d'),
            'in_time' => '',
            'out_time' => '',
            'note' => 'Portal visible',
            'image' => '',
            'meeting_with' => 'student',
        ]);
        $this->cleanupVisitorIds[] = $visitorId;

        $user = PortalUser::query()
            ->where('user_id', $student->id)
            ->where('role', 'student')
            ->firstOrFail();
        $user->login_token = 'tok'.$suffix;
        $user->save();
        $this->cleanupUserIds[] = (int) $user->id;
        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => $studentSessionId]]);

        $this->get('/user/visitors')
            ->assertOk()
            ->assertSee($visitorName, false);
    }
}
