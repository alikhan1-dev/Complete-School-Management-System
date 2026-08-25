<?php

namespace Tests\Feature\LessonPlan;

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

class WeeklySyllabusClassTeacherScopeTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupTimetableIds = [];

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
        if ($this->cleanupTimetableIds !== []) {
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
            'name' => 'Week',
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
     * @return array{group:SubjectGroup,sgs:SubjectGroupSubject}
     */
    private function createSubjectGraph(AcademicSession $session, string $label): array
    {
        $subject = Subject::query()->create([
            'name' => 'Sub-'.$label.uniqid(),
            'code' => 'W'.substr(uniqid(), -5),
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'G-'.$label.uniqid(),
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $sgs = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
        ]);

        return compact('group', 'sgs');
    }

    public function test_weekly_grid_filters_slots_by_viewer_class_teacher_matrix(): void
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2098-week-ct']);
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();

        $sectionA = Section::query()->create(['section' => 'WA-'.uniqid(), 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'WA-'.uniqid(), 'is_active' => 'yes']);
        $sectionB = Section::query()->create(['section' => 'WB-'.uniqid(), 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'WB-'.uniqid(), 'is_active' => 'yes']);
        $this->cleanupSectionIds = [$sectionA->id, $sectionB->id];
        $this->cleanupClassIds = [$classA->id, $classB->id];
        ClassSection::query()->create(['class_id' => $classA->id, 'section_id' => $sectionA->id, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $classB->id, 'section_id' => $sectionB->id, 'is_active' => 'yes']);

        $graphA = $this->createSubjectGraph($session, 'A');
        $graphB = $this->createSubjectGraph($session, 'B');

        $subjectTeacher = $this->insertStaff(2, 'subj');
        $day = 'Monday';
        $this->cleanupTimetableIds[] = DB::table('subject_timetable')->insertGetId([
            'session_id' => $session->id,
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'subject_group_id' => $graphA['group']->id,
            'subject_group_subject_id' => $graphA['sgs']->id,
            'staff_id' => $subjectTeacher->id,
            'day' => $day,
            'time_from' => '08:00 AM',
            'time_to' => '08:45 AM',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'room_no' => 'R1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupTimetableIds[] = DB::table('subject_timetable')->insertGetId([
            'session_id' => $session->id,
            'class_id' => $classB->id,
            'section_id' => $sectionB->id,
            'subject_group_id' => $graphB['group']->id,
            'subject_group_subject_id' => $graphB['sgs']->id,
            'staff_id' => $subjectTeacher->id,
            'day' => $day,
            'time_from' => '09:00 AM',
            'time_to' => '09:45 AM',
            'start_time' => '09:00:00',
            'end_time' => '09:45:00',
            'room_no' => 'R2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->ensureTeacherPrivilege('manage_lesson_plan');

        $viewer = $this->insertStaff(2, 'viewer');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'staff_id' => $viewer->id,
            'session_id' => $session->id,
        ]);
        $this->actingAs($viewer, 'staff');

        // Restricted class teacher viewing another staff week → only Class A slots
        $this->get('/admin/syllabus?staff_id='.$subjectTeacher->id)
            ->assertOk()
            ->assertSee($classA->class, false)
            ->assertDontSee($classB->class, false);

        $this->get('/migration-status/lessonplan')
            ->assertOk()
            ->assertJsonPath('slices.weekly_class_teacher_matrix', 'done');
    }
}
