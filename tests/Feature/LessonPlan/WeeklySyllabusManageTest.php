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
use App\Modules\LessonPlan\Models\SubjectSyllabus;
use App\Modules\LessonPlan\Models\Topic;
use App\Modules\Staff\Models\Staff;
use App\Modules\Timetable\Models\SubjectTimetable;
use App\Modules\Timetable\Services\ClassTimetableService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WeeklySyllabusManageTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupSyllabusIds = [];

    /** @var list<int> */
    private array $cleanupTimetableIds = [];

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
        if ($this->cleanupSyllabusIds !== []) {
            DB::table('subject_syllabus')->whereIn('id', $this->cleanupSyllabusIds)->delete();
        }
        $this->cleanupSyllabusIds = [];

        if ($this->cleanupTimetableIds !== []) {
            DB::table('subject_timetable')->whereIn('id', $this->cleanupTimetableIds)->delete();
        }
        $this->cleanupTimetableIds = [];

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

        $token = uniqid('syladm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Syllabus',
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

    private function createTeacher(): int
    {
        $token = uniqid('syltch', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'T-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Syllabus',
            'surname' => 'Teacher',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => $token.'@example.test',
            'dob' => '1991-01-01',
            'marital_status' => '',
            'local_address' => '',
            'permanent_address' => '',
            'note' => '',
            'image' => '',
            'password' => bcrypt('secret'),
            'gender' => 'Female',
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
            'role_id' => ClassTimetableService::TEACHER_ROLE_ID,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;

        return $staffId;
    }

    public function test_weekly_syllabus_manage_create_view_edit_delete(): void
    {
        $this->actingAsSuperAdmin();
        $teacherId = $this->createTeacher();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-syl']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'SYS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'SYC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'Syllabus Subject '.$suffix,
            'code' => 'SY'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'SYG-'.$suffix,
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

        $days = app(ClassTimetableService::class)->dayNames();
        $day = $days[0];

        $tt = SubjectTimetable::query()->create([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'subject_group_subject_id' => $groupSubject->id,
            'staff_id' => $teacherId,
            'day' => $day,
            'time_from' => '09:00 AM',
            'time_to' => '09:45 AM',
            'start_time' => '09:00:00',
            'end_time' => '09:45:00',
            'room_no' => 'R-1',
        ]);
        $this->cleanupTimetableIds[] = $tt->id;

        $lesson = Lesson::query()->create([
            'session_id' => $session->id,
            'subject_group_subject_id' => $groupSubject->id,
            'subject_group_class_sections_id' => $sgcs->id,
            'name' => 'Syl Lesson '.$suffix,
        ]);
        $this->cleanupLessonIds[] = $lesson->id;

        $topic = Topic::query()->create([
            'session_id' => $session->id,
            'lesson_id' => $lesson->id,
            'name' => 'Syl Topic '.$suffix,
            'status' => 0,
            'complete_date' => null,
        ]);
        $this->cleanupTopicIds[] = $topic->id;

        $this->get('/admin/syllabus?staff_id='.$teacherId)
            ->assertOk()
            ->assertSee('Manage Lesson Plan', false)
            ->assertSee('Syllabus Subject', false)
            ->assertSee('09:00 AM', false);

        $meta = app(\App\Modules\LessonPlan\Services\SyllabusManageService::class)->weekMeta();
        $slotDate = \Carbon\Carbon::parse($meta['week_start'])->toDateString();

        $this->get('/admin/syllabus/create?'.http_build_query([
            'subject_group_subject_id' => $groupSubject->id,
            'subject_group_class_sections_id' => $sgcs->id,
            'time_from' => '09:00 AM',
            'time_to' => '09:45 AM',
            'date' => $slotDate,
            'created_for' => $teacherId,
            'week_start' => $meta['week_start'],
        ]))->assertOk()->assertSee('Add Lesson Plan', false);

        $this->post('/admin/syllabus/add_syllabus', [
            'lesson_id' => $lesson->id,
            'topic_id' => $topic->id,
            'date' => $slotDate,
            'time_from' => '09:00 AM',
            'time_to' => '09:45 AM',
            'created_for' => $teacherId,
            'presentation' => 'Intro '.$suffix,
            'sub_topic' => 'Sub '.$suffix,
            'teaching_method' => 'Lecture',
            'general_objectives' => 'Obj',
            'previous_knowledge' => 'PK',
            'comprehensive_questions' => 'CQ',
            'lacture_youtube_url' => '',
            'week_start' => $meta['week_start'],
        ])->assertRedirect();

        $syllabus = SubjectSyllabus::query()
            ->where('topic_id', $topic->id)
            ->where('date', $slotDate)
            ->where('created_for', $teacherId)
            ->firstOrFail();
        $this->cleanupSyllabusIds[] = $syllabus->id;
        $this->assertSame('Intro '.$suffix, $syllabus->presentation);

        $this->get('/admin/syllabus/show/'.$syllabus->id)
            ->assertOk()
            ->assertSee('Syl Topic '.$suffix, false)
            ->assertSee('Intro '.$suffix, false)
            ->assertSee('Comments', false);

        $this->post('/admin/syllabus/addmessage', [
            'subject_syllabus_id' => $syllabus->id,
            'message' => 'Forum note '.$suffix,
        ])->assertRedirect('/admin/syllabus/show/'.$syllabus->id);

        $comment = DB::table('lesson_plan_forum')
            ->where('subject_syllabus_id', $syllabus->id)
            ->where('message', 'Forum note '.$suffix)
            ->first();
        $this->assertNotNull($comment);
        $this->assertSame('staff', $comment->type);

        $this->get('/admin/syllabus/show/'.$syllabus->id)
            ->assertOk()
            ->assertSee('Forum note '.$suffix, false);

        $this->get('/admin/syllabus/deletemessage/'.$comment->id)
            ->assertRedirect('/admin/syllabus/show/'.$syllabus->id);
        $this->assertNull(DB::table('lesson_plan_forum')->where('id', $comment->id)->first());

        $this->get('/admin/syllabus/edit/'.$syllabus->id)
            ->assertOk()
            ->assertSee('Edit Lesson Plan', false);

        $this->post('/admin/syllabus/edit/'.$syllabus->id, [
            'lesson_id' => $lesson->id,
            'topic_id' => $topic->id,
            'date' => $slotDate,
            'time_from' => '09:00 AM',
            'time_to' => '09:45 AM',
            'created_for' => $teacherId,
            'presentation' => 'Updated '.$suffix,
            'sub_topic' => 'Sub '.$suffix,
            'teaching_method' => 'Lecture',
            'general_objectives' => 'Obj',
            'previous_knowledge' => 'PK',
            'comprehensive_questions' => 'CQ',
            'lacture_youtube_url' => '',
            'week_start' => $meta['week_start'],
        ])->assertRedirect();

        $syllabus->refresh();
        $this->assertSame('Updated '.$suffix, $syllabus->presentation);

        $this->get('/admin/lessonplan/gettopicBylessonid/'.$lesson->id)
            ->assertOk()
            ->assertJsonFragment(['name' => 'Syl Topic '.$suffix]);

        $this->get('/admin/syllabus?staff_id='.$teacherId.'&week_start='.$meta['week_start'])
            ->assertOk()
            ->assertSee('fa-reorder', false);

        $this->get('/admin/syllabus/delete/'.$syllabus->id.'?week_start='.$meta['week_start'])
            ->assertRedirect();
        $this->assertNull(SubjectSyllabus::query()->find($syllabus->id));
        $this->cleanupSyllabusIds = [];
    }
}
