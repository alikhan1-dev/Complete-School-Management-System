<?php

namespace Tests\Feature\Reports;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentInformationClassTeacherScopeTest extends TestCase
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

    /** @var list<int> */
    private array $cleanupRolePermissionIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
            $this->cleanupClassTeacherIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
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
        if ($this->cleanupRolePermissionIds !== []) {
            DB::table('roles_permissions')->whereIn('id', $this->cleanupRolePermissionIds)->delete();
            $this->cleanupRolePermissionIds = [];
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
            ?: AcademicSession::query()->create(['session' => '2099-rpt-ct']);
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();

        $make = function (string $prefix) {
            $section = Section::query()->create(['section' => $prefix.'-'.uniqid(), 'is_active' => 'yes']);
            $class = SchoolClass::query()->create(['class' => $prefix.'-'.uniqid(), 'is_active' => 'yes']);
            ClassSection::query()->create([
                'class_id' => $class->id,
                'section_id' => $section->id,
                'is_active' => 'yes',
            ]);
            $this->cleanupSectionIds[] = $section->id;
            $this->cleanupClassIds[] = $class->id;

            return [$class, $section];
        };

        [$classA, $sectionA] = $make('RPA');
        [$classB, $sectionB] = $make('RPB');

        return compact('session', 'classA', 'sectionA', 'classB', 'sectionB');
    }

    private function ensureTeacherPrivilege(string $shortCode): void
    {
        $permCatId = (int) DB::table('permission_category')->where('short_code', $shortCode)->value('id');
        $this->assertGreaterThan(0, $permCatId);

        $existing = DB::table('roles_permissions')
            ->where('role_id', 2)
            ->where('perm_cat_id', $permCatId)
            ->first();

        $payload = [
            'can_view' => 1,
            'can_add' => 1,
            'can_edit' => 1,
            'can_delete' => 1,
        ];

        if ($existing) {
            DB::table('roles_permissions')->where('id', $existing->id)->update($payload);
        } else {
            $this->cleanupRolePermissionIds[] = DB::table('roles_permissions')->insertGetId(array_merge([
                'role_id' => 2,
                'perm_cat_id' => $permCatId,
            ], $payload));
        }
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
            'name' => 'Report',
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
        $this->actingAs($this->insertStaff($roleId, 'rpsa'), 'staff');
    }

    private function createStudent(array $fixtures, SchoolClass $class, Section $section, string $admissionNo): Student
    {
        $this->actingAsSuperAdmin();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Scoped',
            'lastname' => 'ReportKid',
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

        return $student;
    }

    public function test_student_information_reports_respect_class_teacher_scope(): void
    {
        $fixtures = $this->seedClasses();
        $suffix = uniqid();

        $inScope = $this->createStudent(
            $fixtures,
            $fixtures['classA'],
            $fixtures['sectionA'],
            'RPIN'.$suffix
        );
        $outOfScope = $this->createStudent(
            $fixtures,
            $fixtures['classB'],
            $fixtures['sectionB'],
            'RPOUT'.$suffix
        );

        foreach ([
            'student_report',
            'guardian_report',
            'student_history',
            'student_login_credential_report',
            'student_gender_ratio_report',
            'student_teacher_ratio_report',
            'admission_report',
            'sibling_report',
            'student_profile',
            'alumni_report',
            'online_admission_report',
            'class_subject_report',
        ] as $shortCode) {
            $this->ensureTeacherPrivilege($shortCode);
        }

        $teacher = $this->insertStaff(2, 'rpct');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $teacher->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->actingAs($teacher, 'staff');

        $studentReport = $this->get('/report/studentreport')->assertOk();
        $studentReport->assertSee($fixtures['classA']->class, false);
        $studentReport->assertDontSee($fixtures['classB']->class, false);

        $this->post('/report/studentreport', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])->assertOk()
            ->assertSee($inScope->admission_no, false)
            ->assertDontSee($outOfScope->admission_no, false);

        $this->post('/report/studentreport', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertForbidden();

        $this->post('/report/guardianreport', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertForbidden();

        $this->post('/report/admissionreport', [
            'class_id' => $fixtures['classB']->id,
        ])->assertForbidden();

        $classSection = $this->get('/report/classsectionreport')->assertOk();
        $classSection->assertSee($fixtures['classA']->class.' ('.$fixtures['sectionA']->section.')', false);
        $classSection->assertDontSee($fixtures['classB']->class.' ('.$fixtures['sectionB']->section.')', false);

        $gender = $this->get('/report/boys_girls_ratio')->assertOk();
        $gender->assertSee($fixtures['classA']->class.' ('.$fixtures['sectionA']->section.')', false);
        $gender->assertDontSee($fixtures['classB']->class.' ('.$fixtures['sectionB']->section.')', false);

        $this->get('/report/admission_report')->assertOk();
        $this->get('/report/online_admission_report')->assertOk();

        $this->post('/report/sibling_report', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertForbidden();

        $this->post('/report/student_profile', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertForbidden();

        $this->post('/report/logindetailreport', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertForbidden();

        $this->post('/report/class_subject', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertForbidden();

        $this->post('/report/alumnireport', [
            'search' => 'search_filter',
            'session_id' => $fixtures['session']->id,
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertForbidden();

        $this->get('/migration-status/reports')
            ->assertOk()
            ->assertJsonPath('slices.student_information_class_teacher', 'done');
    }
}
