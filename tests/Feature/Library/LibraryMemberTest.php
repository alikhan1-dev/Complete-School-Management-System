<?php

namespace Tests\Feature\Library;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryMemberTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupMemberIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupMemberIds !== []) {
            DB::table('book_issues')->whereIn('member_id', $this->cleanupMemberIds)->delete();
            DB::table('libarary_members')->whereIn('id', $this->cleanupMemberIds)->delete();
        }
        $this->cleanupMemberIds = [];

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

        foreach ($this->cleanupSectionIds as $sectionId) {
            DB::table('sections')->where('id', $sectionId)->delete();
        }
        $this->cleanupSectionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('libarary_members')->where('member_type', 'teacher')->where('member_id', $staffId)->delete();
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): Staff
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('libmem', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Member',
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
        $staff = Staff::query()->findOrFail($staffId);
        $this->actingAs($staff, 'staff');

        return $staff;
    }

    public function test_enroll_student_staff_list_and_surrender(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-lib']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'LMS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'LMC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'LIBADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Lib',
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

        $this->get('/admin/member')->assertOk()->assertSee('Members', false);

        $this->get('/admin/member/student?search=1&class_id='.$class->id.'&section_id='.$section->id)
            ->assertOk()
            ->assertSee('Lib Pupil', false);

        $cardStudent = 'SCARD-'.$suffix;
        $this->post('/admin/member/add', [
            'member_id' => $student->id,
            'library_card_no' => $cardStudent,
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertRedirect();

        $studentMember = LibraryMember::query()
            ->where('member_type', 'student')
            ->where('member_id', $student->id)
            ->firstOrFail();
        $this->cleanupMemberIds[] = $studentMember->id;
        $this->assertSame($cardStudent, (string) $studentMember->library_card_no);

        $this->get('/admin/member/teacher')
            ->assertOk()
            ->assertSee('Add Staff Member', false)
            ->assertSee((string) $admin->employee_id, false);

        $cardStaff = 'TCARD-'.$suffix;
        $this->post('/admin/member/addteacher', [
            'member_id' => $admin->id,
            'library_card_no' => $cardStaff,
        ])->assertRedirect('/admin/member/teacher');

        $staffMember = LibraryMember::query()
            ->where('member_type', 'teacher')
            ->where('member_id', $admin->id)
            ->firstOrFail();
        $this->cleanupMemberIds[] = $staffMember->id;

        $this->get('/admin/member')
            ->assertOk()
            ->assertSee($cardStudent, false)
            ->assertSee($cardStaff, false);

        $this->get('/admin/member/surrender/'.$studentMember->id)->assertRedirect('/admin/member');
        $this->assertNull(LibraryMember::query()->find($studentMember->id));
        $this->cleanupMemberIds = array_values(array_filter(
            $this->cleanupMemberIds,
            fn ($id) => (int) $id !== (int) $studentMember->id
        ));
    }
}
