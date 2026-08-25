<?php

namespace Tests\Feature\LessonPlan;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\LessonPlan\Models\Lesson;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LessonPlanClassTeacherScopeTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupLessonIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

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
        if ($this->cleanupLessonIds !== []) {
            DB::table('topic')->whereIn('lesson_id', $this->cleanupLessonIds)->delete();
            DB::table('lesson')->whereIn('id', $this->cleanupLessonIds)->delete();
            $this->cleanupLessonIds = [];
        }
        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
            $this->cleanupClassTeacherIds = [];
        }
        foreach ($this->cleanupSubjectGroupIds as $groupId) {
            DB::table('subject_group_class_sections')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_group_subjects')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_groups')->where('id', $groupId)->delete();
        }
        $this->cleanupSubjectGroupIds = [];
        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
            $this->cleanupSubjectIds = [];
        }
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

    private function insertStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'LP',
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
        $this->actingAs($this->insertStaff($roleId, 'lpsa'), 'staff');
    }

    private function ensureTeacherPrivilege(string $shortCode): void
    {
        $permCatId = (int) DB::table('permission_category')->where('short_code', $shortCode)->value('id');
        $this->assertGreaterThan(0, $permCatId);
        $existing = DB::table('roles_permissions')
            ->where('role_id', 2)
            ->where('perm_cat_id', $permCatId)
            ->first();
        $payload = ['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1];
        if ($existing) {
            DB::table('roles_permissions')->where('id', $existing->id)->update($payload);
        } else {
            $this->cleanupRolePermissionIds[] = DB::table('roles_permissions')->insertGetId(array_merge([
                'role_id' => 2,
                'perm_cat_id' => $permCatId,
            ], $payload));
        }
    }

    /**
     * @return array{
     *     session:AcademicSession,
     *     classA:SchoolClass,
     *     sectionA:Section,
     *     classB:SchoolClass,
     *     sectionB:Section,
     *     groupA:SubjectGroup,
     *     sgsA:SubjectGroupSubject,
     *     sgcsA:int,
     *     groupB:SubjectGroup,
     *     sgsB:SubjectGroupSubject,
     *     sgcsB:int
     * }
     */
    private function seedTwoClassGraphs(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2098-lp-ct']);
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();

        $make = function (string $prefix) use ($session) {
            $section = Section::query()->create(['section' => $prefix.'-'.uniqid(), 'is_active' => 'yes']);
            $class = SchoolClass::query()->create(['class' => $prefix.'-'.uniqid(), 'is_active' => 'yes']);
            $this->cleanupSectionIds[] = $section->id;
            $this->cleanupClassIds[] = $class->id;
            $classSection = ClassSection::query()->create([
                'class_id' => $class->id,
                'section_id' => $section->id,
                'is_active' => 'yes',
            ]);
            $subject = Subject::query()->create([
                'name' => 'Sub-'.$prefix.uniqid(),
                'code' => 'C'.substr(uniqid(), -5),
                'type' => 'Theory',
                'is_active' => 'yes',
            ]);
            $this->cleanupSubjectIds[] = $subject->id;
            $group = SubjectGroup::query()->create([
                'name' => 'G-'.$prefix.uniqid(),
                'description' => '',
                'session_id' => $session->id,
            ]);
            $this->cleanupSubjectGroupIds[] = $group->id;
            $sgs = SubjectGroupSubject::query()->create([
                'subject_group_id' => $group->id,
                'subject_id' => $subject->id,
                'session_id' => $session->id,
            ]);
            $sgcs = SubjectGroupClassSection::query()->create([
                'subject_group_id' => $group->id,
                'class_section_id' => $classSection->id,
                'session_id' => $session->id,
                'is_active' => 1,
            ]);

            return [$class, $section, $group, $sgs, (int) $sgcs->id];
        };

        [$classA, $sectionA, $groupA, $sgsA, $sgcsA] = $make('LPA');
        [$classB, $sectionB, $groupB, $sgsB, $sgcsB] = $make('LPB');

        return compact(
            'session',
            'classA',
            'sectionA',
            'classB',
            'sectionB',
            'groupA',
            'sgsA',
            'sgcsA',
            'groupB',
            'sgsB',
            'sgcsB'
        );
    }

    public function test_lesson_list_and_update_respect_class_teacher_scope(): void
    {
        $fixtures = $this->seedTwoClassGraphs();
        $suffix = uniqid();

        $this->actingAsSuperAdmin();
        $this->post('/admin/lessonplan/createlesson', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'subject_group_id' => $fixtures['groupA']->id,
            'subject_id' => $fixtures['sgsA']->id,
            'lessons' => ['In Scope '.$suffix],
        ])->assertRedirect();
        $this->post('/admin/lessonplan/createlesson', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'subject_group_id' => $fixtures['groupB']->id,
            'subject_id' => $fixtures['sgsB']->id,
            'lessons' => ['Out Scope '.$suffix],
        ])->assertRedirect();

        $inLesson = Lesson::query()->where('name', 'In Scope '.$suffix)->firstOrFail();
        $outLesson = Lesson::query()->where('name', 'Out Scope '.$suffix)->firstOrFail();
        $this->cleanupLessonIds[] = $inLesson->id;
        $this->cleanupLessonIds[] = $outLesson->id;

        $this->ensureTeacherPrivilege('lesson');
        $this->ensureTeacherPrivilege('topic');
        $this->ensureTeacherPrivilege('manage_syllabus_status');
        $this->ensureTeacherPrivilege('copy_old_lesson');

        $teacher = $this->insertStaff(2, 'lpct');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $teacher->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->actingAs($teacher, 'staff');

        $lessonPage = $this->get('/admin/lessonplan/lesson')->assertOk();
        $lessonPage->assertSee($fixtures['classA']->class, false);
        $lessonPage->assertDontSee($fixtures['classB']->class, false);
        $lessonPage->assertSee('In Scope '.$suffix, false);
        $lessonPage->assertDontSee('Out Scope '.$suffix, false);

        $this->get('/admin/lessonplan/topic')->assertOk()
            ->assertSee($fixtures['classA']->class, false)
            ->assertDontSee($fixtures['classB']->class, false);

        $this->get('/admin/lessonplan')->assertOk()
            ->assertSee($fixtures['classA']->class, false)
            ->assertDontSee($fixtures['classB']->class, false);

        $this->get('/admin/lessonplan/copylesson')->assertOk()
            ->assertSee($fixtures['classA']->class, false)
            ->assertDontSee($fixtures['classB']->class, false);

        // Out-of-scope update denied
        $this->post('/admin/lessonplan/updatelesson', [
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'subject_group_id' => $fixtures['groupB']->id,
            'subject_id' => $fixtures['sgsB']->id,
            'lessons_'.$outLesson->id => 'Hacked '.$suffix,
        ])->assertForbidden();

        // In-scope update allowed
        $this->post('/admin/lessonplan/updatelesson', [
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'subject_group_id' => $fixtures['groupA']->id,
            'subject_id' => $fixtures['sgsA']->id,
            'lessons_'.$inLesson->id => 'Updated '.$suffix,
        ])->assertRedirect('/admin/lessonplan/lesson');

        $this->assertSame('Updated '.$suffix, (string) Lesson::query()->findOrFail($inLesson->id)->name);

        $this->get('/migration-status/lessonplan')
            ->assertOk()
            ->assertJsonPath('slices.class_teacher_lesson_topic', 'done');
    }
}
