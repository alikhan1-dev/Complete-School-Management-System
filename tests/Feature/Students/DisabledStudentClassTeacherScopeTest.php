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

class DisabledStudentClassTeacherScopeTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupClassTeacherIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
            $this->cleanupClassTeacherIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
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
            $this->cleanupSectionIds = [];
        }
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        DB::table('sch_settings')->limit(1)->update(['class_teacher' => $this->previousClassTeacherSetting]);
        app(SchoolContext::class)->clearCache();

        parent::tearDown();
    }

    /**
     * @return array{session:AcademicSession,classA:SchoolClass,sectionA:Section,classB:SchoolClass,sectionB:Section}
     */
    private function seedClasses(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2097-ct']);
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();

        $sectionA = Section::query()->create(['section' => 'CTA-'.uniqid(), 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'CTA-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'is_active' => 'yes',
        ]);

        $sectionB = Section::query()->create(['section' => 'CTB-'.uniqid(), 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'CTB-'.uniqid(), 'is_active' => 'yes']);
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

    private function actingAsTeacherWithAssignment(array $fixtures): Staff
    {
        $roleId = (int) (DB::table('roles')->where('id', 2)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $this->assertSame(2, $roleId, 'Teacher role_id must be 2 for CI class-teacher parity');

        $token = uniqid('cteach', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Class',
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

        $ctId = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $staffId,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->cleanupClassTeacherIds[] = $ctId;
        $this->createdStaffIds[] = $staffId;

        $staff = Staff::query()->findOrFail($staffId);
        $this->actingAs($staff, 'staff');

        return $staff;
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('ctsa', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CTSA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Super',
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

    private function createDisabledStudent(array $fixtures, SchoolClass $class, Section $section, string $admissionNo): Student
    {
        $this->actingAsSuperAdmin();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Scoped',
            'lastname' => 'Kid',
            'gender' => 'Male',
            'dob' => '2010-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        DB::table('students')->where('id', $student->id)->update([
            'is_active' => 'no',
            'dis_reason' => 0,
            'dis_note' => 'scoped',
        ]);

        return $student->fresh();
    }

    public function test_class_teacher_keyword_search_only_sees_assigned_class_sections(): void
    {
        $fixtures = $this->seedClasses();
        $suffix = uniqid();

        $inScope = $this->createDisabledStudent(
            $fixtures,
            $fixtures['classA'],
            $fixtures['sectionA'],
            'CTIN'.$suffix
        );
        $outOfScope = $this->createDisabledStudent(
            $fixtures,
            $fixtures['classB'],
            $fixtures['sectionB'],
            'CTOUT'.$suffix
        );

        $this->actingAsTeacherWithAssignment($fixtures);

        $response = $this->get('/student/disablestudentslist')->assertOk();
        $response->assertSee($fixtures['classA']->class, false);
        $response->assertDontSee($fixtures['classB']->class, false);

        $this->getJson('/sections/getByClass?class_id='.$fixtures['classA']->id)
            ->assertOk()
            ->assertJsonFragment(['section_id' => (string) $fixtures['sectionA']->id]);

        $this->getJson('/sections/getByClass?class_id='.$fixtures['classB']->id)
            ->assertOk()
            ->assertExactJson([]);

        $search = $this->post('/student/disablestudentslist', [
            'search' => 'search_full',
            'search_text' => 'anything',
        ])->assertOk();

        $search->assertSee($inScope->admission_no, false);
        $search->assertDontSee($outOfScope->admission_no, false);
        $search->assertSee(__('system.details_view'), false);
    }
}
