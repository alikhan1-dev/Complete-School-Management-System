<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiClassClassTeacherScopeTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupClassTeacherIds = [];

    private string $previousClassTeacherSetting = 'no';

    private string $previousMultiClassSetting = 'disabled';

    protected function tearDown(): void
    {
        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
            $this->cleanupClassTeacherIds = [];
        }
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
        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];
        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
            $this->cleanupSectionIds = [];
        }
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        DB::table('sch_settings')->limit(1)->update([
            'class_teacher' => $this->previousClassTeacherSetting,
            'student_form_multi_class' => $this->previousMultiClassSetting,
        ]);
        app(SchoolContext::class)->clearCache();

        parent::tearDown();
    }

    /**
     * @return array{session:AcademicSession,classA:SchoolClass,sectionA:Section,classB:SchoolClass,sectionB:Section}
     */
    private function seedClasses(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2096-mc']);

        $settings = DB::table('sch_settings')->first();
        $this->previousClassTeacherSetting = (string) ($settings->class_teacher ?? 'no');
        $this->previousMultiClassSetting = (string) ($settings->student_form_multi_class ?? 'disabled');

        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
            'student_form_multi_class' => 'enabled',
        ]);
        app(SchoolContext::class)->clearCache();

        $sectionA = Section::query()->create(['section' => 'MCA-'.uniqid(), 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'MCA-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'is_active' => 'yes',
        ]);

        $sectionB = Section::query()->create(['section' => 'MCB-'.uniqid(), 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'MCB-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $classB->id,
            'section_id' => $sectionB->id,
            'is_active' => 'yes',
        ]);

        $this->cleanupSectionIds[] = $sectionA->id;
        $this->cleanupSectionIds[] = $sectionB->id;
        $this->cleanupClassIds[] = $classA->id;
        $this->cleanupClassIds[] = $classB->id;

        return compact('session', 'classA', 'sectionA', 'classB', 'sectionB');
    }

    private function insertStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Multi',
            'surname' => 'Teacher',
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

        return Staff::query()->findOrFail($staffId);
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);
        $this->actingAs($this->insertStaff($roleId, 'mcsa'), 'staff');
    }

    private function actingAsTeacherWithAssignment(array $fixtures): void
    {
        $roleId = (int) (DB::table('roles')->where('id', 2)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $this->assertSame(2, $roleId);

        $staff = $this->insertStaff($roleId, 'mct');
        $ctId = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $staff->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->cleanupClassTeacherIds[] = $ctId;
        $this->actingAs($staff, 'staff');
    }

    private function createStudentIn(array $fixtures, SchoolClass $class, Section $section, string $admissionNo): Student
    {
        $this->actingAsSuperAdmin();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Matrix',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-15',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03001234567',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->createdStudentIds[] = (int) $student->id;

        return $student;
    }

    public function test_class_teacher_multiclass_search_uses_matrix_filter(): void
    {
        $fixtures = $this->seedClasses();
        $suffix = uniqid();

        $inScope = $this->createStudentIn(
            $fixtures,
            $fixtures['classA'],
            $fixtures['sectionA'],
            'MCIN'.$suffix
        );
        $outOfScope = $this->createStudentIn(
            $fixtures,
            $fixtures['classB'],
            $fixtures['sectionB'],
            'MCOUT'.$suffix
        );

        $this->actingAsTeacherWithAssignment($fixtures);

        $page = $this->get('/student/multiclass')->assertOk();
        $page->assertSee($fixtures['classA']->class, false);
        $page->assertDontSee($fixtures['classB']->class, false);

        $this->post('/student/multiclass', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])->assertOk()
            ->assertSee($inScope->admission_no, false);

        $this->post('/student/multiclass', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertOk()
            ->assertDontSee($outOfScope->admission_no, false);
    }

    public function test_class_teacher_with_no_assignments_sees_empty_search(): void
    {
        $fixtures = $this->seedClasses();
        $suffix = uniqid();
        $student = $this->createStudentIn(
            $fixtures,
            $fixtures['classA'],
            $fixtures['sectionA'],
            'MCEMP'.$suffix
        );

        $roleId = 2;
        $staff = $this->insertStaff($roleId, 'mcempty');
        $this->actingAs($staff, 'staff');

        $this->post('/student/multiclass', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])->assertOk()
            ->assertDontSee($student->admission_no, false);
    }
}
