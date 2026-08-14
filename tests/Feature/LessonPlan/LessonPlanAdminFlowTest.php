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
use App\Modules\LessonPlan\Models\Topic;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LessonPlanAdminFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupLessonIds = [];

    /** @var list<int> */
    private array $cleanupTopicIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupTopicIds !== []) {
            DB::table('topic')->whereIn('id', $this->cleanupTopicIds)->delete();
        }
        $this->cleanupTopicIds = [];

        if ($this->cleanupLessonIds !== []) {
            DB::table('topic')->whereIn('lesson_id', $this->cleanupLessonIds)->delete();
            DB::table('lesson')->whereIn('id', $this->cleanupLessonIds)->delete();
        }
        $this->cleanupLessonIds = [];

        foreach ($this->cleanupSubjectGroupIds as $groupId) {
            DB::table('subject_group_class_sections')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_group_subjects')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_groups')->where('id', $groupId)->delete();
        }
        $this->cleanupSubjectGroupIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];

        foreach ($this->cleanupSectionIds as $sectionId) {
            DB::table('sections')->where('id', $sectionId)->delete();
        }
        $this->cleanupSectionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('lpstf', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Lesson',
            'surname' => 'Plan',
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
        ]);
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    /**
     * @return array{
     *     class:SchoolClass,
     *     section:Section,
     *     group:SubjectGroup,
     *     groupSubject:SubjectGroupSubject,
     *     sgcsId:int,
     *     session:AcademicSession
     * }
     */
    private function seedClassSubjectGraph(): array
    {
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-lp']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'LPS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'LPC-'.$suffix, 'is_active' => 'yes']);
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

        return [
            'class' => $class,
            'section' => $section,
            'group' => $group,
            'groupSubject' => $groupSubject,
            'sgcsId' => (int) $sgcs->id,
            'session' => $session,
        ];
    }

    public function test_migration_status_endpoint(): void
    {
        $this->get('/migration-status/lessonplan')
            ->assertOk()
            ->assertJsonPath('module', 'LessonPlan')
            ->assertJsonPath('slices.lesson', 'done')
            ->assertJsonPath('slices.topic', 'done')
            ->assertJsonPath('slices.syllabus_status', 'done')
            ->assertJsonPath('slices.copy_lesson', 'done')
            ->assertJsonPath('slices.manage_lesson_plan_weekly', 'done')
            ->assertJsonPath('slices.forum', 'done');
    }

    public function test_lesson_topic_and_syllabus_status_flow(): void
    {
        $this->actingAsSuperAdmin();
        $ctx = $this->seedClassSubjectGraph();
        $suffix = uniqid();

        $this->get('/admin/lessonplan/lesson')
            ->assertOk()
            ->assertSee('Lesson List', false);

        $this->post('/admin/lessonplan/createlesson', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_id' => $ctx['groupSubject']->id,
            'lessons' => ['Lesson A '.$suffix, 'Lesson B '.$suffix],
        ])->assertRedirect('/admin/lessonplan/lesson');

        $lessonA = Lesson::query()->where('name', 'Lesson A '.$suffix)->firstOrFail();
        $lessonB = Lesson::query()->where('name', 'Lesson B '.$suffix)->firstOrFail();
        $this->cleanupLessonIds[] = $lessonA->id;
        $this->cleanupLessonIds[] = $lessonB->id;

        $this->assertSame($ctx['sgcsId'], (int) $lessonA->subject_group_class_sections_id);
        $this->assertSame((int) $ctx['groupSubject']->id, (int) $lessonA->subject_group_subject_id);

        $this->get('/admin/lessonplan/editlesson/'.$ctx['sgcsId'].'/'.$ctx['groupSubject']->id)
            ->assertOk()
            ->assertSee('Lesson A '.$suffix, false);

        $this->post('/admin/lessonplan/updatelesson', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_id' => $ctx['groupSubject']->id,
            'lessons_'.$lessonA->id => 'Lesson A Updated '.$suffix,
            'lessons' => ['Lesson C '.$suffix],
            'lesson_delete' => [$lessonB->id],
        ])->assertRedirect('/admin/lessonplan/lesson');

        $lessonA->refresh();
        $this->assertSame('Lesson A Updated '.$suffix, $lessonA->name);
        $this->assertNull(Lesson::query()->find($lessonB->id));
        $this->cleanupLessonIds = array_values(array_filter(
            $this->cleanupLessonIds,
            fn ($id) => (int) $id !== (int) $lessonB->id
        ));

        $lessonC = Lesson::query()->where('name', 'Lesson C '.$suffix)->firstOrFail();
        $this->cleanupLessonIds[] = $lessonC->id;

        $this->get('/admin/lessonplan/topic')
            ->assertOk()
            ->assertSee('Topic List', false);

        $this->post('/admin/lessonplan/createtopic', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_id' => $ctx['groupSubject']->id,
            'lesson_id' => $lessonA->id,
            'topic' => ['Topic 1 '.$suffix, 'Topic 2 '.$suffix],
        ])->assertRedirect('/admin/lessonplan/topic');

        $topic1 = Topic::query()->where('name', 'Topic 1 '.$suffix)->firstOrFail();
        $topic2 = Topic::query()->where('name', 'Topic 2 '.$suffix)->firstOrFail();
        $this->cleanupTopicIds[] = $topic1->id;
        $this->cleanupTopicIds[] = $topic2->id;
        $this->assertSame(0, (int) $topic1->status);

        $this->post('/admin/lessonplan/getlessonBysubjectid/'.$ctx['groupSubject']->id, [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
        ])->assertOk()->assertJsonFragment(['name' => 'Lesson A Updated '.$suffix]);

        $this->post('/admin/syllabus/status', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_id' => $ctx['groupSubject']->id,
            'search' => 'search_filter',
        ])->assertOk()
            ->assertSee('Syllabus Status for', false)
            ->assertSee('Topic 1 '.$suffix, false)
            ->assertSee('Incomplete', false);

        $this->post('/admin/lessonplan/topic/complete/'.$topic1->id, [
            'date' => '2026-08-14',
            'redirect' => '/admin/syllabus/status',
        ])->assertRedirect();

        $topic1->refresh();
        $this->assertSame(1, (int) $topic1->status);
        $this->assertSame('2026-08-14', (string) $topic1->complete_date);

        $this->post('/admin/lessonplan/topic/incomplete/'.$topic1->id, [
            'redirect' => '/admin/syllabus/status',
        ])->assertRedirect();

        $topic1->refresh();
        $this->assertSame(0, (int) $topic1->status);
        $this->assertNull($topic1->complete_date);

        $this->get('/admin/lessonplan/deletetopicbulk/'.$lessonA->id)->assertRedirect('/admin/lessonplan/topic');
        $this->assertNull(Topic::query()->find($topic1->id));
        $this->assertNull(Topic::query()->find($topic2->id));
        $this->cleanupTopicIds = [];

        $this->get('/admin/lessonplan/deletelessonbulk/'.$ctx['sgcsId'].'/'.$ctx['groupSubject']->id)
            ->assertRedirect('/admin/lessonplan/lesson');
        $this->assertNull(Lesson::query()->find($lessonA->id));
        $this->assertNull(Lesson::query()->find($lessonC->id));
        $this->cleanupLessonIds = [];
    }

    public function test_copy_old_lesson_into_current_session(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $oldSession = AcademicSession::query()->create(['session' => '2098-lp-old-'.$suffix]);
        $currentSession = AcademicSession::query()->create(['session' => '2099-lp-cur-'.$suffix]);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $currentSession->id]);

        $section = Section::query()->create(['section' => 'LPCS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'LPCC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'Copy Subject '.$suffix,
            'code' => 'CP'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $oldGroup = SubjectGroup::query()->create([
            'name' => 'OldG-'.$suffix,
            'description' => '',
            'session_id' => $oldSession->id,
        ]);
        $curGroup = SubjectGroup::query()->create([
            'name' => 'CurG-'.$suffix,
            'description' => '',
            'session_id' => $currentSession->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $oldGroup->id;
        $this->cleanupSubjectGroupIds[] = $curGroup->id;

        $oldGroupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $oldGroup->id,
            'subject_id' => $subject->id,
            'session_id' => $oldSession->id,
        ]);
        $curGroupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $curGroup->id,
            'subject_id' => $subject->id,
            'session_id' => $currentSession->id,
        ]);

        $oldSgcs = SubjectGroupClassSection::query()->create([
            'subject_group_id' => $oldGroup->id,
            'class_section_id' => $classSection->id,
            'session_id' => $oldSession->id,
            'is_active' => 1,
        ]);
        SubjectGroupClassSection::query()->create([
            'subject_group_id' => $curGroup->id,
            'class_section_id' => $classSection->id,
            'session_id' => $currentSession->id,
            'is_active' => 1,
        ]);

        $oldLesson = Lesson::query()->create([
            'session_id' => $oldSession->id,
            'subject_group_subject_id' => $oldGroupSubject->id,
            'subject_group_class_sections_id' => $oldSgcs->id,
            'name' => 'Old Lesson '.$suffix,
        ]);
        $this->cleanupLessonIds[] = $oldLesson->id;

        $oldTopic = Topic::query()->create([
            'session_id' => $oldSession->id,
            'lesson_id' => $oldLesson->id,
            'name' => 'Old Topic '.$suffix,
            'status' => 1,
            'complete_date' => '2025-01-01',
        ]);
        $this->cleanupTopicIds[] = $oldTopic->id;

        $this->get('/admin/lessonplan/copylesson')
            ->assertOk()
            ->assertSee('Select Old Session Details', false);

        $this->post('/admin/lessonplan/copylesson', [
            'old_session_id' => $oldSession->id,
            'old_class_id' => $class->id,
            'old_section_id' => $section->id,
            'old_subject_group_id' => $oldGroup->id,
            'old_subject_id' => $oldGroupSubject->id,
            'search' => 'search_filter',
        ])->assertOk()
            ->assertSee('Old Lesson '.$suffix, false)
            ->assertSee('Old Topic '.$suffix, false);

        $this->post('/admin/lessonplan/saveCopyLesson', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $curGroup->id,
            'subject_group_subject_id' => $curGroupSubject->id,
            'topic_id' => [
                $oldLesson->id => [$oldTopic->id],
            ],
        ])->assertRedirect('/admin/lessonplan/lesson');

        $copiedLesson = Lesson::query()
            ->where('session_id', $currentSession->id)
            ->where('name', 'Old Lesson '.$suffix)
            ->firstOrFail();
        $this->cleanupLessonIds[] = $copiedLesson->id;

        $copiedTopic = Topic::query()
            ->where('session_id', $currentSession->id)
            ->where('lesson_id', $copiedLesson->id)
            ->where('name', 'Old Topic '.$suffix)
            ->firstOrFail();
        $this->cleanupTopicIds[] = $copiedTopic->id;

        $this->assertSame(0, (int) $copiedTopic->status);
        $this->assertNull($copiedTopic->complete_date);
        $this->assertSame((int) $curGroupSubject->id, (int) $copiedLesson->subject_group_subject_id);

        // Cleanup extra sessions created by this test
        DB::table('sessions')->whereIn('id', [$oldSession->id, $currentSession->id])->delete();
    }
}
