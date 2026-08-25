<?php

namespace Tests\Feature\Homework;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Homework\Models\Homework;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeworkReportClassTeacherScopeTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupHomeworkIds = [];

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
        if ($this->cleanupHomeworkIds !== []) {
            DB::table('homework')->whereIn('id', $this->cleanupHomeworkIds)->delete();
            $this->cleanupHomeworkIds = [];
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

    private function enableClassTeacherMode(AcademicSession $session): void
    {
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();
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
            'name' => 'HW',
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

    /**
     * @return array{
     *   session:AcademicSession,
     *   classA:SchoolClass,
     *   sectionA:Section,
     *   classB:SchoolClass,
     *   sectionB:Section,
     *   homeworkA:Homework,
     *   homeworkB:Homework,
     *   suffix:string
     * }
     */
    private function seedScopedHomework(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2099-hwct']);
        $this->enableClassTeacherMode($session);

        $suffix = uniqid();
        $admin = $this->insertStaff(
            (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
                ?: DB::table('roles')->where('name', 'Super Admin')->value('id')),
            'hwsa'
        );

        $sectionA = Section::query()->create(['section' => 'SEC-A-'.$suffix, 'is_active' => 'yes']);
        $sectionB = Section::query()->create(['section' => 'SEC-B-'.$suffix, 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'CLS-A-'.$suffix, 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'CLS-B-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $sectionA->id;
        $this->cleanupSectionIds[] = $sectionB->id;
        $this->cleanupClassIds[] = $classA->id;
        $this->cleanupClassIds[] = $classB->id;

        $csA = ClassSection::query()->create([
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'is_active' => 'yes',
        ]);
        $csB = ClassSection::query()->create([
            'class_id' => $classB->id,
            'section_id' => $sectionB->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'HWCT Subject '.$suffix,
            'code' => 'HW'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'HWCTG-'.$suffix,
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
        ]);

        SubjectGroupClassSection::query()->create([
            'subject_group_id' => $group->id,
            'class_section_id' => $csA->id,
            'session_id' => $session->id,
            'is_active' => 1,
        ]);
        SubjectGroupClassSection::query()->create([
            'subject_group_id' => $group->id,
            'class_section_id' => $csB->id,
            'session_id' => $session->id,
            'is_active' => 1,
        ]);

        $markerA = 'IN-SCOPE-HW-'.$suffix;
        $markerB = 'OUT-SCOPE-HW-'.$suffix;

        $homeworkA = Homework::query()->create([
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'session_id' => $session->id,
            'staff_id' => $admin->id,
            'subject_group_subject_id' => $groupSubject->id,
            'subject_id' => null,
            'homework_date' => now()->format('Y-m-d'),
            'submit_date' => now()->addDays(2)->format('Y-m-d'),
            'marks' => 10,
            'description' => $markerA,
            'create_date' => now()->format('Y-m-d'),
            'evaluation_date' => null,
            'document' => '',
            'created_by' => $admin->id,
            'evaluated_by' => null,
        ]);
        $homeworkB = Homework::query()->create([
            'class_id' => $classB->id,
            'section_id' => $sectionB->id,
            'session_id' => $session->id,
            'staff_id' => $admin->id,
            'subject_group_subject_id' => $groupSubject->id,
            'subject_id' => null,
            'homework_date' => now()->format('Y-m-d'),
            'submit_date' => now()->addDays(2)->format('Y-m-d'),
            'marks' => 10,
            'description' => $markerB,
            'create_date' => now()->format('Y-m-d'),
            'evaluation_date' => null,
            'document' => '',
            'created_by' => $admin->id,
            'evaluated_by' => null,
        ]);
        $this->cleanupHomeworkIds[] = $homeworkA->id;
        $this->cleanupHomeworkIds[] = $homeworkB->id;

        return compact(
            'session',
            'classA',
            'sectionA',
            'classB',
            'sectionB',
            'homeworkA',
            'homeworkB',
            'suffix'
        );
    }

    public function test_homework_report_denies_empty_matrix_and_filters_rows(): void
    {
        $fixtures = $this->seedScopedHomework();
        $this->ensureTeacherPrivilege('homework');

        $emptyTeacher = $this->insertStaff(2, 'hwempty');
        $this->actingAs($emptyTeacher, 'staff');
        $this->get('/homework/homeworkreport')->assertForbidden();

        $scopedTeacher = $this->insertStaff(2, 'hwct');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $scopedTeacher->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->actingAs($scopedTeacher, 'staff');

        $page = $this->get('/homework/homeworkreport?'.http_build_query([
            'search' => 1,
        ]))->assertOk();
        $page->assertSee('CLS-A-'.$fixtures['suffix'], false);
        $page->assertDontSee('CLS-B-'.$fixtures['suffix'], false);
        $page->assertSee('homework_id='.$fixtures['homeworkA']->id, false);
        $page->assertDontSee('homework_id='.$fixtures['homeworkB']->id, false);

        $this->get('/migration-status/homework')
            ->assertOk()
            ->assertJsonPath('slices.homework_report_class_teacher', 'done');
    }
}
