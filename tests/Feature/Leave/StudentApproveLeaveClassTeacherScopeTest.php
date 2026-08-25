<?php

namespace Tests\Feature\Leave;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Leave\Models\StudentApplyLeave;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentApproveLeaveClassTeacherScopeTest extends TestCase
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
    private array $cleanupStudentLeaveIds = [];

    /** @var list<int> */
    private array $cleanupRolePermissionIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupStudentLeaveIds !== []) {
            DB::table('student_applyleave')->whereIn('id', $this->cleanupStudentLeaveIds)->delete();
            $this->cleanupStudentLeaveIds = [];
        }
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
            ?: AcademicSession::query()->create(['session' => '2098-leave-ct']);
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

        [$classA, $sectionA] = $make('LVA');
        [$classB, $sectionB] = $make('LVB');

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
            'name' => 'Leave',
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
        $this->actingAs($this->insertStaff($roleId, 'lvsa'), 'staff');
    }

    /**
     * @return array{student:Student,studentSessionId:int,leaveId:int}
     */
    private function createStudentWithLeave(array $fixtures, SchoolClass $class, Section $section, string $admissionNo): array
    {
        $this->actingAsSuperAdmin();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Scoped',
            'lastname' => 'LeaveKid',
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

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $fixtures['session']->id)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $this->post('/admin/approve_leave/add', [
            'class' => $class->id,
            'section' => $section->id,
            'student' => $studentSessionId,
            'apply_date' => date('Y-m-d'),
            'from_date' => date('Y-m-d'),
            'to_date' => date('Y-m-d', strtotime('+1 day')),
            'leave_status' => 0,
            'message' => 'Sick '.$admissionNo,
        ])->assertRedirect();

        $leave = StudentApplyLeave::query()
            ->where('student_session_id', $studentSessionId)
            ->firstOrFail();
        $this->cleanupStudentLeaveIds[] = (int) $leave->id;

        return [
            'student' => $student,
            'studentSessionId' => $studentSessionId,
            'leaveId' => (int) $leave->id,
        ];
    }

    public function test_approve_leave_respects_class_teacher_scope(): void
    {
        $fixtures = $this->seedClasses();
        $suffix = uniqid();

        $inScope = $this->createStudentWithLeave(
            $fixtures,
            $fixtures['classA'],
            $fixtures['sectionA'],
            'LVIN'.$suffix
        );
        $outOfScope = $this->createStudentWithLeave(
            $fixtures,
            $fixtures['classB'],
            $fixtures['sectionB'],
            'LVOUT'.$suffix
        );

        $this->ensureTeacherPrivilege('approve_leave');

        $teacher = $this->insertStaff(2, 'lvct');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $teacher->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->actingAs($teacher, 'staff');

        $index = $this->get('/admin/approve_leave')->assertOk();
        $index->assertSee($fixtures['classA']->class, false);
        $index->assertDontSee($fixtures['classB']->class, false);

        $this->post('/admin/approve_leave', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'search' => 'search_filter',
        ])->assertOk()
            ->assertSee($inScope['student']->admission_no, false)
            ->assertDontSee($outOfScope['student']->admission_no, false);

        $this->post('/admin/approve_leave', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'search' => 'search_filter',
        ])->assertForbidden();

        $this->get('/admin/approve_leave/edit/'.$outOfScope['leaveId'])->assertNotFound();

        $this->post('/admin/approve_leave/status/'.$outOfScope['leaveId'], [
            'status' => 1,
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])->assertNotFound();

        $this->post('/admin/approve_leave/status/'.$inScope['leaveId'], [
            'status' => 1,
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])->assertRedirect();

        $this->assertSame(1, (int) StudentApplyLeave::query()->findOrFail($inScope['leaveId'])->status);

        $this->get('/migration-status/leave')
            ->assertOk()
            ->assertJsonPath('slices.student_approve_leave_class_teacher', 'done');
    }
}
