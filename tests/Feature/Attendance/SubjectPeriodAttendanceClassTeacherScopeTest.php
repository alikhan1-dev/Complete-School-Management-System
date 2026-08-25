<?php

namespace Tests\Feature\Attendance;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubjectPeriodAttendanceClassTeacherScopeTest extends TestCase
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
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupRolePermissionIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupTimetableIds !== []) {
            DB::table('student_subject_attendances')
                ->whereIn('subject_timetable_id', $this->cleanupTimetableIds)
                ->delete();
            DB::table('subject_timetable')->whereIn('id', $this->cleanupTimetableIds)->delete();
            $this->cleanupTimetableIds = [];
        }
        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
            $this->cleanupClassTeacherIds = [];
        }
        foreach ($this->cleanupSubjectGroupIds as $groupId) {
            DB::table('subject_group_subjects')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_groups')->where('id', $groupId)->delete();
        }
        $this->cleanupSubjectGroupIds = [];
        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
            $this->cleanupSubjectIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                DB::table('student_subject_attendances')->whereIn('student_session_id', $sessionIds)->delete();
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
            ?: AcademicSession::query()->create(['session' => '2098-sub-ct']);
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

        [$classA, $sectionA] = $make('SUBA');
        [$classB, $sectionB] = $make('SUBB');
        [$classC, $sectionC] = $make('SUBC');

        return compact('session', 'classA', 'sectionA', 'classB', 'sectionB', 'classC', 'sectionC');
    }

    private function ensureTeacherPrivilege(string $shortCode, bool $canAdd = false): void
    {
        $permCatId = (int) DB::table('permission_category')->where('short_code', $shortCode)->value('id');
        $this->assertGreaterThan(0, $permCatId);

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

    private function insertStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Sub',
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

    private function actingAsSuperAdmin(): Staff
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);
        $staff = $this->insertStaff($roleId, 'subsa');
        $this->actingAs($staff, 'staff');

        return $staff;
    }

    /**
     * @return array{groupSubject:SubjectGroupSubject,timetableId:int}
     */
    private function createPeriod(
        array $fixtures,
        SchoolClass $class,
        Section $section,
        Staff $teacher,
        string $label,
        string $day = 'Wednesday'
    ): array {
        $suffix = uniqid($label);
        $subject = Subject::query()->create([
            'name' => 'Sub-'.$suffix,
            'code' => 'S'.substr($suffix, -6),
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'SG-'.$suffix,
            'description' => '',
            'session_id' => $fixtures['session']->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subject->id,
            'session_id' => $fixtures['session']->id,
        ]);

        $timetableId = DB::table('subject_timetable')->insertGetId([
            'session_id' => $fixtures['session']->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'subject_group_subject_id' => $groupSubject->id,
            'staff_id' => $teacher->id,
            'day' => $day,
            'time_from' => '08:00 AM',
            'time_to' => '08:45 AM',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'room_no' => 'R1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupTimetableIds[] = $timetableId;

        return compact('groupSubject', 'timetableId');
    }

    public function test_subject_period_class_teacher_and_subject_teacher_filters(): void
    {
        $fixtures = $this->seedClasses();
        $date = '2026-08-12'; // Wednesday

        $this->actingAsSuperAdmin();
        $otherTeacher = $this->insertStaff(2, 'other');

        $this->ensureTeacherPrivilege('student_attendance', true);

        $teacher = $this->insertStaff(2, 'subct');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $teacher->id,
            'session_id' => $fixtures['session']->id,
        ]);

        // Class A: teacher is class teacher → sees ALL periods (own + other)
        $periodOtherOnA = $this->createPeriod($fixtures, $fixtures['classA'], $fixtures['sectionA'], $otherTeacher, 'othA');
        $periodOwnOnA = $this->createPeriod($fixtures, $fixtures['classA'], $fixtures['sectionA'], $teacher, 'ownA');

        // Class B: subject teacher only → only own period
        $periodOtherOnB = $this->createPeriod($fixtures, $fixtures['classB'], $fixtures['sectionB'], $otherTeacher, 'othB');
        $periodOwnOnB = $this->createPeriod($fixtures, $fixtures['classB'], $fixtures['sectionB'], $teacher, 'ownB');

        $this->actingAs($teacher, 'staff');

        $page = $this->get('/admin/subjectattendence')->assertOk();
        $page->assertSee($fixtures['classA']->class, false);
        $page->assertSee($fixtures['classB']->class, false);
        $page->assertDontSee($fixtures['classC']->class, false);

        $reportPage = $this->get('/admin/subjectattendence/reportbydate')->assertOk();
        $reportPage->assertSee($fixtures['classA']->class, false);
        $reportPage->assertDontSee($fixtures['classC']->class, false);

        // Class teacher on A → all periods
        $periodsA = $this->postJson('/admin/subjectgroup/getSubjectByClassandSectionDate', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'date' => $date,
        ])->assertOk()->json();

        $periodIdsA = collect($periodsA)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($periodOtherOnA['timetableId'], $periodIdsA);
        $this->assertContains($periodOwnOnA['timetableId'], $periodIdsA);

        // Subject teacher on B → only own period
        $periodsB = $this->postJson('/admin/subjectgroup/getSubjectByClassandSectionDate', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'date' => $date,
        ])->assertOk()->json();

        $periodIdsB = collect($periodsB)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($periodOwnOnB['timetableId'], $periodIdsB);
        $this->assertNotContains($periodOtherOnB['timetableId'], $periodIdsB);

        // Unassigned class/section → empty
        $this->postJson('/admin/subjectgroup/getSubjectByClassandSectionDate', [
            'class_id' => $fixtures['classC']->id,
            'section_id' => $fixtures['sectionC']->id,
            'date' => $date,
        ])->assertOk()->assertExactJson([]);

        // Search with other teacher's period on B must be rejected
        $this->post('/admin/subjectattendence', [
            'search' => 'search',
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'date' => $date,
            'subject_timetable_id' => $periodOtherOnB['timetableId'],
        ])->assertRedirect()->assertSessionHasErrors('subject_timetable_id');

        // Report by date for unassigned class is scoped out
        $this->post('/admin/subjectattendence/reportbydate', [
            'class_id' => $fixtures['classC']->id,
            'section_id' => $fixtures['sectionC']->id,
            'date' => $date,
        ])->assertOk();
    }
}
