<?php

namespace Tests\Feature\Timetable;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Staff\Models\Staff;
use App\Modules\Timetable\Services\ClassTimetableService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherTimetableTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupTimetableIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupTimetableIds !== []) {
            DB::table('subject_timetable')->whereIn('id', $this->cleanupTimetableIds)->delete();
        }
        $this->cleanupTimetableIds = [];

        foreach ($this->cleanupSubjectGroupIds as $groupId) {
            DB::table('subject_group_subjects')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_groups')->where('id', $groupId)->delete();
        }
        $this->cleanupSubjectGroupIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function insertStaff(array $overrides = []): Staff
    {
        $token = uniqid('tt', true);
        $staffId = DB::table('staff')->insertGetId(array_merge([
            'employee_id' => 'TT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Time',
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
        ], $overrides));
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    private function assignRole(Staff $staff, int $roleId): void
    {
        DB::table('staff_roles')->insert([
            'staff_id' => $staff->id,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
    }

    private function seedTimetablePeriod(Staff $teacher): array
    {
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'TTS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'TTC-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $subject = Subject::query()->create([
            'name' => 'Physics-'.$suffix,
            'code' => 'PHY'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'TTG-'.$suffix,
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
        ]);

        $timetableId = DB::table('subject_timetable')->insertGetId([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'subject_group_subject_id' => $groupSubject->id,
            'staff_id' => $teacher->id,
            'day' => 'Wednesday',
            'time_from' => '10:00 AM',
            'time_to' => '10:45 AM',
            'start_time' => '10:00:00',
            'end_time' => '10:45:00',
            'room_no' => 'LAB1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupTimetableIds[] = $timetableId;

        return [
            'subjectName' => 'Physics-'.$suffix,
            'className' => 'TTC-'.$suffix,
            'teacherId' => $teacher->id,
            'classId' => $class->id,
            'sectionId' => $section->id,
        ];
    }

    public function test_teacher_sees_own_mytimetable(): void
    {
        $teacherRoleId = (int) (DB::table('roles')->where('id', ClassTimetableService::TEACHER_ROLE_ID)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $teacher = $this->insertStaff(['name' => 'Own', 'surname' => 'Teacher']);
        $this->assignRole($teacher, $teacherRoleId);

        $seed = $this->seedTimetablePeriod($teacher);
        $this->actingAs($teacher, 'staff');

        $this->get('/admin/timetable/mytimetable')
            ->assertOk()
            ->assertSee('Teacher Time Table', false)
            ->assertSee($seed['subjectName'], false)
            ->assertSee($seed['className'], false)
            ->assertSee('LAB1', false);
    }

    public function test_admin_ajax_teacher_timetable_returns_grid(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $superRoleId);

        $admin = $this->insertStaff(['name' => 'Timetable', 'surname' => 'Admin']);
        $this->assignRole($admin, $superRoleId);

        $teacherRoleId = (int) (DB::table('roles')->where('id', ClassTimetableService::TEACHER_ROLE_ID)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $teacher = $this->insertStaff(['name' => 'Ajax', 'surname' => 'Teacher']);
        $this->assignRole($teacher, $teacherRoleId);

        $seed = $this->seedTimetablePeriod($teacher);
        $this->actingAs($admin, 'staff');

        $this->get('/admin/timetable/mytimetable')
            ->assertOk()
            ->assertSee('id="teacher"', false);

        $this->postJson('/admin/timetable/getteachertimetable', [
            'teacher' => $seed['teacherId'],
        ])
            ->assertOk()
            ->assertJsonPath('status', '1')
            ->assertJsonStructure(['status', 'error', 'message'])
            ->assertSee($seed['subjectName'], false);
    }

    public function test_print_class_timetable_returns_html_page_json(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $admin = $this->insertStaff(['name' => 'Print', 'surname' => 'Admin']);
        $this->assignRole($admin, $superRoleId);

        $teacherRoleId = (int) (DB::table('roles')->where('id', ClassTimetableService::TEACHER_ROLE_ID)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $teacher = $this->insertStaff(['name' => 'Print', 'surname' => 'Teacher']);
        $this->assignRole($teacher, $teacherRoleId);

        $seed = $this->seedTimetablePeriod($teacher);
        $this->actingAs($admin, 'staff');

        $this->postJson('/admin/timetable/printclasstimetable', [
            'class_id' => $seed['classId'],
            'section_id' => $seed['sectionId'],
        ])
            ->assertOk()
            ->assertJsonPath('status', '1')
            ->assertJsonStructure(['status', 'error', 'page'])
            ->assertSee($seed['subjectName'], false)
            ->assertSee($seed['className'], false);
    }

    public function test_print_teacher_timetable_returns_html_page_json(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $admin = $this->insertStaff(['name' => 'Print2', 'surname' => 'Admin']);
        $this->assignRole($admin, $superRoleId);

        $teacherRoleId = (int) (DB::table('roles')->where('id', ClassTimetableService::TEACHER_ROLE_ID)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $teacher = $this->insertStaff(['name' => 'Print2', 'surname' => 'Teacher', 'employee_id' => 'PRT-'.uniqid()]);
        $this->assignRole($teacher, $teacherRoleId);

        $seed = $this->seedTimetablePeriod($teacher);
        $this->actingAs($admin, 'staff');

        $this->postJson('/admin/timetable/printteachertimetable', [
            'staff_id' => $seed['teacherId'],
        ])
            ->assertOk()
            ->assertJsonPath('status', '1')
            ->assertJsonStructure(['status', 'error', 'page'])
            ->assertSee($seed['subjectName'], false)
            ->assertSee($seed['className'], false);
    }

    public function test_duplicate_check_detects_conflicting_teacher_slot(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $admin = $this->insertStaff(['name' => 'Dup', 'surname' => 'Admin']);
        $this->assignRole($admin, $superRoleId);

        $teacherRoleId = (int) (DB::table('roles')->where('id', ClassTimetableService::TEACHER_ROLE_ID)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $teacher = $this->insertStaff(['name' => 'Dup', 'surname' => 'Teacher']);
        $this->assignRole($teacher, $teacherRoleId);

        $this->seedTimetablePeriod($teacher);
        $this->actingAs($admin, 'staff');

        $this->postJson('/admin/timetable/check_class_dublicate_recored', [
            'staff_id' => $teacher->id,
            'day' => 'Wednesday',
            'time_from' => '10:00',
            'time_to' => '10:45',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonStructure(['status', 'result', 'error']);
    }

    public function test_duplicate_check_passes_when_no_conflict(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $admin = $this->insertStaff(['name' => 'Clear', 'surname' => 'Admin']);
        $this->assignRole($admin, $superRoleId);

        $teacherRoleId = (int) (DB::table('roles')->where('id', ClassTimetableService::TEACHER_ROLE_ID)->value('id')
            ?: DB::table('roles')->where('name', 'Teacher')->value('id'));
        $teacher = $this->insertStaff(['name' => 'Clear', 'surname' => 'Teacher']);
        $this->assignRole($teacher, $teacherRoleId);

        $this->seedTimetablePeriod($teacher);
        $this->actingAs($admin, 'staff');

        $this->postJson('/admin/timetable/check_class_dublicate_recored', [
            'staff_id' => $teacher->id,
            'day' => 'Wednesday',
            'time_from' => '11:00',
            'time_to' => '11:45',
        ])
            ->assertOk()
            ->assertJsonPath('status', 0);
    }
}
