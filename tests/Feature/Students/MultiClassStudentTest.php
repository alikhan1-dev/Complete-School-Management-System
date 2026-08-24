<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiClassStudentTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdStudentIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdStudentIds as $studentId) {
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->createdStudentIds = [];

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

        $token = uniqid('mcs', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'MCS-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Multi',
            'surname' => 'Class',
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
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    /**
     * @return array{session:AcademicSession,classA:SchoolClass,sectionA:Section,classB:SchoolClass,sectionB:Section}
     */
    private function seedClasses(): array
    {
        $session = AcademicSession::query()->first();
        if (! $session) {
            $session = AcademicSession::query()->create(['session' => '2098-99']);
        }
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'student_form_multi_class' => 'enabled',
        ]);

        $sectionA = Section::query()->create(['section' => 'A-'.uniqid(), 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'CA-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'is_active' => 'yes',
        ]);

        $sectionB = Section::query()->create(['section' => 'B-'.uniqid(), 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'CB-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $classB->id,
            'section_id' => $sectionB->id,
            'is_active' => 'yes',
        ]);

        return compact('session', 'classA', 'sectionA', 'classB', 'sectionB');
    }

    private function createStudent(array $fixtures): Student
    {
        $admissionNo = 'ADM'.uniqid();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Multi',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-15',
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03001234567',
            'multiclass' => [
                ['class' => $fixtures['classB']->id, 'section' => $fixtures['sectionB']->id],
            ],
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->createdStudentIds[] = (int) $student->id;

        return $student;
    }

    public function test_multiclass_admin_search_and_save(): void
    {
        $this->actingAsSuperAdmin();
        $fixtures = $this->seedClasses();
        $student = $this->createStudent($fixtures);

        $this->assertSame(2, (int) DB::table('student_session')->where('student_id', $student->id)->count());

        $this->post('/student/multiclass', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])->assertOk()->assertSee($student->admission_no, false);

        $this->postJson('/student/savemulticlass', [
            'student_id' => $student->id,
            'row_count' => [1],
            'class_id_1' => $fixtures['classA']->id,
            'section_id_1' => $fixtures['sectionA']->id,
        ])->assertOk()->assertJson(['status' => 1]);

        $this->assertSame(1, (int) DB::table('student_session')->where('student_id', $student->id)->count());
    }

    public function test_savemulticlass_rejects_duplicates(): void
    {
        $this->actingAsSuperAdmin();
        $fixtures = $this->seedClasses();
        $student = $this->createStudent($fixtures);

        $this->postJson('/student/savemulticlass', [
            'student_id' => $student->id,
            'row_count' => [1, 2],
            'class_id_1' => $fixtures['classA']->id,
            'section_id_1' => $fixtures['sectionA']->id,
            'class_id_2' => $fixtures['classA']->id,
            'section_id_2' => $fixtures['sectionA']->id,
        ])->assertOk()->assertJson([
            'status' => 0,
            'message' => __('system.duplicate_entry'),
        ]);
    }

    public function test_delete_blocked_when_student_has_multiple_sessions(): void
    {
        $this->actingAsSuperAdmin();
        $fixtures = $this->seedClasses();
        $student = $this->createStudent($fixtures);

        $this->get('/student/delete/'.$student->id)
            ->assertRedirect(route('students.view', $student->id));

        $this->assertNotNull(Student::query()->find($student->id));
    }
}
