<?php

namespace Tests\Feature\Reports;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\LessonPlan\Models\Lesson;
use App\Modules\LessonPlan\Models\SubjectSyllabus;
use App\Modules\LessonPlan\Models\Topic;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LessonPlanReportFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupLessonIds = [];

    /** @var list<int> */
    private array $cleanupTopicIds = [];

    /** @var list<int> */
    private array $cleanupSyllabusIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupSyllabusIds !== []) {
            DB::table('subject_syllabus')->whereIn('id', $this->cleanupSyllabusIds)->delete();
            $this->cleanupSyllabusIds = [];
        }
        if ($this->cleanupTopicIds !== []) {
            DB::table('topic')->whereIn('id', $this->cleanupTopicIds)->delete();
            $this->cleanupTopicIds = [];
        }
        if ($this->cleanupLessonIds !== []) {
            DB::table('lesson')->whereIn('id', $this->cleanupLessonIds)->delete();
            $this->cleanupLessonIds = [];
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
            $csIds = DB::table('class_sections')->where('class_id', $classId)->pluck('id');
            if ($csIds->isNotEmpty()) {
                DB::table('class_sections')->whereIn('id', $csIds)->delete();
            }
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

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('lprpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LPRPT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'LpReport',
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

        return $staffId;
    }

    public function test_guest_cannot_open_lesson_plan_reports(): void
    {
        $this->get('/report/lesson_plan')->assertRedirect();
        $this->get('/report/teachersyllabusstatus')->assertRedirect();
    }

    public function test_syllabus_status_and_teacher_lesson_plan_reports(): void
    {
        $adminId = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-lpr']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'LPRS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'LPRC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'LP Subject '.$suffix,
            'code' => 'LP'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'LPG-'.$suffix,
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubject = SubjectGroupSubject::query()->create([
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

        $lesson = Lesson::query()->create([
            'session_id' => $session->id,
            'subject_group_subject_id' => $groupSubject->id,
            'subject_group_class_sections_id' => $sgcs->id,
            'name' => 'LP Lesson '.$suffix,
        ]);
        $this->cleanupLessonIds[] = $lesson->id;

        $topicComplete = Topic::query()->create([
            'session_id' => $session->id,
            'lesson_id' => $lesson->id,
            'name' => 'LP Topic Done '.$suffix,
            'status' => 1,
            'complete_date' => '2026-01-15',
        ]);
        $topicOpen = Topic::query()->create([
            'session_id' => $session->id,
            'lesson_id' => $lesson->id,
            'name' => 'LP Topic Open '.$suffix,
            'status' => 0,
            'complete_date' => null,
        ]);
        $this->cleanupTopicIds[] = $topicComplete->id;
        $this->cleanupTopicIds[] = $topicOpen->id;

        $syllabus = SubjectSyllabus::query()->create([
            'topic_id' => $topicComplete->id,
            'session_id' => $session->id,
            'created_by' => $adminId,
            'created_for' => $adminId,
            'date' => '2026-01-20',
            'time_from' => '09:00 AM',
            'time_to' => '09:45 AM',
            'presentation' => 'Pres '.$suffix,
            'attachment' => '',
            'lacture_youtube_url' => '',
            'lacture_video' => '',
            'sub_topic' => 'Sub '.$suffix,
            'teaching_method' => 'Lecture',
            'general_objectives' => 'Obj',
            'previous_knowledge' => 'PK',
            'comprehensive_questions' => 'CQ',
            'status' => 0,
        ]);
        $this->cleanupSyllabusIds[] = $syllabus->id;

        $this->get('/report/lesson_plan')
            ->assertOk()
            ->assertSee(__('system.syllabus_status_report'), false)
            ->assertSee('/report/teachersyllabusstatus', false);

        $statusPage = $this->post('/report/lesson_plan', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
        ]);
        $statusPage->assertOk()
            ->assertSee('LP Subject '.$suffix, false)
            ->assertSee('LP Lesson '.$suffix, false)
            ->assertSee('LP Topic Done '.$suffix, false)
            ->assertSee('LP Topic Open '.$suffix, false)
            ->assertSee('50% '. __('system.complete'), false);

        $this->post('/report/lesson_plan', [])
            ->assertSessionHasErrors(['class_id', 'section_id', 'subject_group_id']);

        $teacherPage = $this->post('/report/teachersyllabusstatus', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'subject_id' => $groupSubject->id,
        ]);
        $teacherPage->assertOk()
            ->assertSee(__('system.subject_lesson_plan_report_for'), false)
            ->assertSee('LP Lesson '.$suffix, false)
            ->assertSee('LP Topic Done '.$suffix, false)
            ->assertSee('Sub '.$suffix, false)
            ->assertSee('09:00 AM', false)
            ->assertSee('09:45 AM', false);
    }
}
