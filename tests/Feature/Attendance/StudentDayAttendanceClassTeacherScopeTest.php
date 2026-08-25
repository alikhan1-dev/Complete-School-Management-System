<?php

namespace Tests\Feature\Attendance;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Attendance\Models\StudentAttendence;
use App\Modules\Attendance\Services\StudentDayAttendanceService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentDayAttendanceClassTeacherScopeTest extends TestCase
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
    private array $cleanupTimetableIds = [];

    /** @var list<int> */
    private array $cleanupRolePermissionIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupTimetableIds !== []) {
            DB::table('subject_timetable')->whereIn('id', $this->cleanupTimetableIds)->delete();
            $this->cleanupTimetableIds = [];
        }
        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
            $this->cleanupClassTeacherIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                DB::table('student_attendences')->whereIn('student_session_id', $sessionIds)->delete();
            }
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
     * @return array{
     *     session:AcademicSession,
     *     classA:SchoolClass,
     *     sectionA:Section,
     *     classB:SchoolClass,
     *     sectionB:Section,
     *     classC:SchoolClass,
     *     sectionC:Section
     * }
     */
    private function seedClasses(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2098-att-ct']);
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

        [$classA, $sectionA] = $make('ATTA');
        [$classB, $sectionB] = $make('ATTB');
        [$classC, $sectionC] = $make('ATTC');

        return compact('session', 'classA', 'sectionA', 'classB', 'sectionB', 'classC', 'sectionC');
    }

    private function ensureTeacherPrivilege(string $shortCode, bool $canAdd = false): void
    {
        $permCatId = (int) DB::table('permission_category')->where('short_code', $shortCode)->value('id');
        $this->assertGreaterThan(0, $permCatId, 'Missing permission_category '.$shortCode);

        $existing = DB::table('roles_permissions')
            ->where('role_id', 2)
            ->where('perm_cat_id', $permCatId)
            ->first();

        $payload = [
            'can_view' => 1,
            'can_add' => $canAdd ? 1 : 0,
            'can_edit' => 0,
            'can_delete' => 0,
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

    private function insertTeacher(array $fixtures): Staff
    {
        $roleId = (int) (DB::table('roles')->where('id', 2)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $this->assertSame(2, $roleId);

        $token = uniqid('attct', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ATTCT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Att',
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

        // Class-teacher on A; timetable-only on C (should appear on mark, not by-date)
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $staffId,
            'session_id' => $fixtures['session']->id,
        ]);

        // Timetable-only assignment (no class_teacher row) for classC
        $this->cleanupTimetableIds[] = DB::table('subject_timetable')->insertGetId([
            'session_id' => $fixtures['session']->id,
            'class_id' => $fixtures['classC']->id,
            'section_id' => $fixtures['sectionC']->id,
            'subject_group_id' => null,
            'subject_group_subject_id' => null,
            'staff_id' => $staffId,
            'day' => 'Monday',
            'time_from' => '08:00 AM',
            'time_to' => '08:45 AM',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'room_no' => 'R1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Staff::query()->findOrFail($staffId);
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('attsa', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ATTSA-'.$token,
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

    private function createStudent(array $fixtures, SchoolClass $class, Section $section, string $admissionNo): Student
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

        return $student;
    }

    public function test_day_mark_and_by_date_respect_class_teacher_scope(): void
    {
        $fixtures = $this->seedClasses();
        $suffix = uniqid();

        $inScope = $this->createStudent(
            $fixtures,
            $fixtures['classA'],
            $fixtures['sectionA'],
            'ATTIN'.$suffix
        );
        $outOfScope = $this->createStudent(
            $fixtures,
            $fixtures['classB'],
            $fixtures['sectionB'],
            'ATTOUT'.$suffix
        );

        $inSession = StudentSession::query()
            ->where('student_id', $inScope->id)
            ->where('session_id', $fixtures['session']->id)
            ->firstOrFail();

        $date = '2026-08-20';
        $this->actingAsSuperAdmin();
        StudentAttendence::query()->create([
            'student_session_id' => $inSession->id,
            'date' => $date,
            'attendence_type_id' => StudentDayAttendanceService::TYPE_PRESENT,
            'remark' => 'prep',
            'biometric_attendence' => 0,
            'qrcode_attendance' => 0,
            'is_active' => 'no',
        ]);

        $this->ensureTeacherPrivilege('student_attendance', true);
        $this->ensureTeacherPrivilege('attendance_by_date');

        $teacher = $this->insertTeacher($fixtures);
        $this->actingAs($teacher, 'staff');

        $markPage = $this->get('/admin/stuattendence')->assertOk();
        $markPage->assertSee($fixtures['classA']->class, false);
        $markPage->assertSee($fixtures['classC']->class, false); // timetable union
        $markPage->assertDontSee($fixtures['classB']->class, false);

        $byDatePage = $this->get('/admin/stuattendence/attendencereport')->assertOk();
        $byDatePage->assertSee($fixtures['classA']->class, false);
        $byDatePage->assertDontSee($fixtures['classC']->class, false); // timetable-only hidden
        $byDatePage->assertDontSee($fixtures['classB']->class, false);

        $this->getJson('/sections/getByClass?class_id='.$fixtures['classA']->id.'&day_wise=yes')
            ->assertOk()
            ->assertJsonFragment(['section_id' => (string) $fixtures['sectionA']->id]);

        $this->getJson('/sections/getByClass?class_id='.$fixtures['classB']->id.'&day_wise=yes')
            ->assertOk()
            ->assertExactJson([]);

        $this->getJson('/sections/getByClass?class_id='.$fixtures['classC']->id.'&day_wise=yes')
            ->assertOk()
            ->assertExactJson([]);

        $searchIn = $this->post('/admin/stuattendence', [
            'search' => 'search',
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'date' => $date,
        ])->assertOk();
        $searchIn->assertSee($inScope->admission_no, false);

        $searchOut = $this->post('/admin/stuattendence', [
            'search' => 'search',
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'date' => $date,
        ])->assertOk();
        $searchOut->assertDontSee($outOfScope->admission_no, false);

        $this->post('/admin/stuattendence/attendencereport', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'date' => $date,
        ])->assertOk()->assertSee($inScope->admission_no, false);

        $this->post('/admin/stuattendence/attendencereport', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'date' => $date,
        ])->assertOk()->assertDontSee($outOfScope->admission_no, false);

        $this->post('/admin/stuattendence', [
            'search' => 'saveattendence',
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'date' => $date,
            'student_session' => [$inSession->id],
            'attendencetype'.$inSession->id => StudentDayAttendanceService::TYPE_ABSENT,
        ])->assertRedirect()->assertSessionHasErrors('search');
    }
}
