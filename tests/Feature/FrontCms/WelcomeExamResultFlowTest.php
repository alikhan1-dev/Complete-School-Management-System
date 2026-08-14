<?php

namespace Tests\Feature\FrontCms;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Exams\Models\ExamGroup;
use App\Modules\Exams\Models\ExamGroupExam;
use App\Modules\Exams\Models\ExamGroupExamSubject;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WelcomeExamResultFlowTest extends TestCase
{
    private ?int $cmsSettingId = null;

    private mixed $originalCmsActive = null;

    private mixed $originalExamResult = null;

    private mixed $originalSessionId = null;

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupGroupIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupExamSubjectIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('front_cms_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->cmsSettingId = (int) $row->id;
        $this->originalCmsActive = $row->is_active_front_cms;
        $settings = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($settings);
        $this->originalExamResult = $settings->exam_result ?? 0;
        $this->originalSessionId = $settings->session_id ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            $examStudentIds = DB::table('exam_group_class_batch_exam_students')
                ->whereIn('exam_group_class_batch_exam_id', $this->cleanupExamIds)
                ->pluck('id');
            if ($examStudentIds->isNotEmpty()) {
                DB::table('exam_group_exam_results')
                    ->whereIn('exam_group_class_batch_exam_student_id', $examStudentIds)
                    ->delete();
            }
            DB::table('exam_group_class_batch_exam_students')
                ->whereIn('exam_group_class_batch_exam_id', $this->cleanupExamIds)
                ->delete();
            if ($this->cleanupExamSubjectIds !== []) {
                DB::table('exam_group_exam_results')
                    ->whereIn('exam_group_class_batch_exam_subject_id', $this->cleanupExamSubjectIds)
                    ->delete();
                DB::table('exam_group_class_batch_exam_subjects')
                    ->whereIn('id', $this->cleanupExamSubjectIds)
                    ->delete();
            }
            DB::table('exam_group_class_batch_exams')->whereIn('id', $this->cleanupExamIds)->delete();
        }
        if ($this->cleanupGroupIds !== []) {
            DB::table('exam_groups')->whereIn('id', $this->cleanupGroupIds)->delete();
        }
        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
        }
        if ($this->cmsSettingId) {
            DB::table('front_cms_settings')->where('id', $this->cmsSettingId)->update([
                'is_active_front_cms' => $this->originalCmsActive,
            ]);
        }
        $settingsUpdate = ['exam_result' => $this->originalExamResult];
        if ($this->originalSessionId !== null) {
            $settingsUpdate['session_id'] = $this->originalSessionId;
        }
        DB::table('sch_settings')->orderBy('id')->limit(1)->update($settingsUpdate);
        app(SchoolContext::class)->clearCache();

        parent::tearDown();
    }

    public function test_examresult_redirects_to_userlogin_when_cms_inactive(): void
    {
        DB::table('front_cms_settings')->where('id', $this->cmsSettingId)->update([
            'is_active_front_cms' => 0,
        ]);

        $this->get('/welcome/examresult')->assertRedirect('/site/userlogin');
        $this->post('/welcome/getstudentexam', ['admission_no' => 'x'])->assertRedirect('/site/userlogin');
    }

    public function test_examresult_shows_disabled_message_when_flag_off(): void
    {
        $this->enablePublicCms();
        DB::table('sch_settings')->orderBy('id')->limit(1)->update(['exam_result' => 0]);
        app(SchoolContext::class)->clearCache();

        $this->get('/welcome/examresult')
            ->assertOk()
            ->assertSee('Exam Result module is Disabled Please Contact To Administrator', false)
            ->assertDontSee('name="admission_no"', false);
    }

    public function test_examresult_form_and_validation_when_flag_on(): void
    {
        $this->enablePublicCms();
        $this->enableExamResult();

        $this->get('/welcome/examresult')
            ->assertOk()
            ->assertSee('name="admission_no"', false)
            ->assertSee('name="exam_id"', false);

        $this->from('/welcome/examresult')
            ->post('/welcome/examresult', ['search' => '1'])
            ->assertRedirect('/welcome/examresult')
            ->assertSessionHasErrors(['admission_no', 'exam_id']);
    }

    public function test_examresult_search_and_getstudentexam_json(): void
    {
        $this->enablePublicCms();
        $this->enableExamResult();
        $fixture = $this->seedPublishedExamStudent();

        $this->post('/welcome/getstudentexam', ['admission_no' => $fixture['admission_no']])
            ->assertOk()
            ->assertJsonFragment([
                'admission_no' => $fixture['admission_no'],
                'exam' => $fixture['exam_name'],
                'id' => $fixture['exam_id'],
            ]);

        $this->post('/welcome/examresult', [
            'admission_no' => $fixture['admission_no'],
            'exam_id' => $fixture['exam_id'],
            'search' => '1',
        ])
            ->assertOk()
            ->assertSee($fixture['admission_no'], false)
            ->assertSee('Welcome', false)
            ->assertSee($fixture['exam_name'], false)
            ->assertSee('88.50', false)
            ->assertSee('Science', false);
    }

    public function test_unpublished_exam_search_shows_no_record(): void
    {
        $this->enablePublicCms();
        $this->enableExamResult();
        $fixture = $this->seedPublishedExamStudent(publish: false);

        $this->post('/welcome/examresult', [
            'admission_no' => $fixture['admission_no'],
            'exam_id' => $fixture['exam_id'],
            'search' => '1',
        ])
            ->assertOk()
            ->assertSee('No Record Found', false)
            ->assertDontSee('88.50', false);
    }

    private function enablePublicCms(): void
    {
        DB::table('front_cms_settings')->where('id', $this->cmsSettingId)->update([
            'is_active_front_cms' => 1,
        ]);
    }

    private function enableExamResult(): void
    {
        DB::table('sch_settings')->orderBy('id')->limit(1)->update(['exam_result' => 1]);
        app(SchoolContext::class)->clearCache();
    }

    /**
     * @return array{admission_no:string,exam_id:int,exam_name:string}
     */
    private function seedPublishedExamStudent(bool $publish = true): array
    {
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-wer']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'WER-S-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $class = SchoolClass::query()->create(['class' => 'WER-C-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'WER'.$suffix;
        $student = Student::query()->create([
            'admission_no' => $admissionNo,
            'firstname' => 'Welcome',
            'lastname' => 'Exam',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'parent_id' => 0,
            'blood_group' => '',
            'guardian_is' => 'father',
            'guardian_occupation' => '',
            'father_pic' => '',
            'mother_pic' => '',
            'guardian_pic' => '',
            'height' => '',
            'weight' => '',
            'dis_reason' => 0,
            'dis_note' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupStudentIds[] = $student->id;

        $studentSession = StudentSession::query()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'session_id' => $session->id,
            'fees_discount' => 0,
            'is_alumni' => 0,
            'is_active' => 'yes',
            'is_leave' => 0,
            'default_login' => 0,
            'transport_fees' => 0,
        ]);

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'Science',
            'code' => 'SC'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $examName = 'WelcomeExam-'.$suffix;
        $group = ExamGroup::query()->create([
            'name' => 'WelcomeGroup-'.$suffix,
            'exam_type' => 'basic_system',
            'description' => '',
            'is_active' => 0,
        ]);
        $this->cleanupGroupIds[] = $group->id;

        $exam = ExamGroupExam::query()->create([
            'exam' => $examName,
            'exam_group_id' => $group->id,
            'session_id' => $session->id,
            'description' => '',
            'use_exam_roll_no' => 0,
            'is_publish' => $publish ? 1 : 0,
            'is_rank_generated' => 0,
            'is_active' => $publish ? 1 : 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        $examSubject = ExamGroupExamSubject::query()->create([
            'exam_group_class_batch_exams_id' => $exam->id,
            'subject_id' => $subjectId,
            'date_from' => '2026-08-20',
            'time_from' => '09:00:00',
            'duration' => '02:00',
            'room_no' => 'R1',
            'max_marks' => 100,
            'min_marks' => 33,
            'credit_hours' => 1,
            'is_active' => 0,
        ]);
        $this->cleanupExamSubjectIds[] = $examSubject->id;

        $examStudentId = (int) DB::table('exam_group_class_batch_exam_students')->insertGetId([
            'exam_group_class_batch_exam_id' => $exam->id,
            'student_id' => $student->id,
            'student_session_id' => $studentSession->id,
            'rank' => 0,
            'is_active' => 0,
        ]);

        DB::table('exam_group_exam_results')->insert([
            'exam_group_class_batch_exam_subject_id' => $examSubject->id,
            'exam_group_class_batch_exam_student_id' => $examStudentId,
            'attendence' => 'present',
            'get_marks' => 88.5,
            'note' => 'Good',
        ]);

        return [
            'admission_no' => $admissionNo,
            'exam_id' => (int) $exam->id,
            'exam_name' => $examName,
        ];
    }
}
