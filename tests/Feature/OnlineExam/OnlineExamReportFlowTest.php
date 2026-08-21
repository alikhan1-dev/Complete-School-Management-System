<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\OnlineExam\Models\Question;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineExamReportFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupQuestionIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            $assignIds = DB::table('onlineexam_students')
                ->whereIn('onlineexam_id', $this->cleanupExamIds)
                ->pluck('id')
                ->all();
            if ($assignIds !== []) {
                DB::table('onlineexam_student_results')->whereIn('onlineexam_student_id', $assignIds)->delete();
                DB::table('onlineexam_attempts')->whereIn('onlineexam_student_id', $assignIds)->delete();
            }
            DB::table('onlineexam_questions')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
            DB::table('onlineexam_students')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
            DB::table('onlineexam')->whereIn('id', $this->cleanupExamIds)->delete();
            $this->cleanupExamIds = [];
        }
        if ($this->cleanupQuestionIds !== []) {
            DB::table('questions')->whereIn('id', $this->cleanupQuestionIds)->delete();
            $this->cleanupQuestionIds = [];
        }
        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
            $this->cleanupSubjectIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
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

        $token = uniqid('oerpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OERPT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'OeReport',
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

    public function test_guest_cannot_open_online_exam_reports(): void
    {
        $this->get('/report/online_examinations')->assertRedirect();
        $this->get('/report/onlineexams')->assertRedirect();
        $this->get('/report/onlineexamattend')->assertRedirect();
        $this->get('/admin/onlineexam/report')->assertRedirect();
        $this->get('/report/onlineexamrank')->assertRedirect();
    }

    public function test_hub_and_exams_report_lists_assigned_exams(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-oer']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'OERS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'OERC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'OEADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Oe',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000000',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;
        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();

        $exam = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'OE Report Exam '.$suffix,
            'attempt' => 2,
            'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '00:45:00',
            'passing_percentage' => 40,
            'description' => 'Report exam',
            'publish_result' => 0,
            'answer_word_count' => 0,
            'is_active' => 1,
            'is_marks_display' => 1,
            'is_neg_marking' => 0,
            'is_random_question' => 0,
            'is_rank_generated' => 0,
            'publish_exam_notification' => 0,
            'publish_result_notification' => 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        $orphan = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'OE Orphan Exam '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '00:30:00',
            'passing_percentage' => 40,
            'description' => 'Unassigned',
            'publish_result' => 0,
            'answer_word_count' => 0,
            'is_active' => 1,
            'is_marks_display' => 1,
            'is_neg_marking' => 0,
            'is_random_question' => 0,
            'is_rank_generated' => 0,
            'publish_exam_notification' => 0,
            'publish_result_notification' => 0,
        ]);
        $this->cleanupExamIds[] = $orphan->id;

        DB::table('onlineexam_students')->insert([
            'onlineexam_id' => $exam->id,
            'student_session_id' => $studentSession->id,
            'is_attempted' => 0,
            'rank' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/report/online_examinations')
            ->assertOk()
            ->assertSee(__('system.exams_report'), false)
            ->assertSee('/report/onlineexams', false);

        $page = $this->post('/report/onlineexams', [
            'search_type' => 'this_year',
            'date_type' => '',
        ]);
        $page->assertOk()
            ->assertSee('OE Report Exam '.$suffix, false)
            ->assertSee('00:45:00', false)
            ->assertDontSee('OE Orphan Exam '.$suffix, false);

        $content = $page->getContent();
        $this->assertMatchesRegularExpression('/OE Report Exam '.preg_quote($suffix, '/').'[\s\S]*?>2</', $content);
    }

    public function test_attempt_report_lists_students_with_assigned_exams(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-oea']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'OEAS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'OEAC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'OEATT'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Attempt',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000001',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;
        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();

        $exam = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'OE Attempt Exam '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '00:20:00',
            'passing_percentage' => 40,
            'description' => 'Attempt report exam',
            'publish_result' => 1,
            'answer_word_count' => 0,
            'is_active' => 1,
            'is_marks_display' => 1,
            'is_neg_marking' => 0,
            'is_random_question' => 0,
            'is_rank_generated' => 0,
            'publish_exam_notification' => 0,
            'publish_result_notification' => 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        DB::table('onlineexam_students')->insert([
            'onlineexam_id' => $exam->id,
            'student_session_id' => $studentSession->id,
            'is_attempted' => 0,
            'rank' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/report/online_examinations')
            ->assertOk()
            ->assertSee('/report/onlineexamattend', false);

        $page = $this->post('/report/onlineexamattend', [
            'search_type' => 'this_year',
            'date_type' => '',
        ]);
        $page->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('OE Attempt Exam '.$suffix, false)
            ->assertSee('00:20:00', false)
            ->assertSee('student/view/'.$student->id, false);
    }

    public function test_result_report_lists_students_for_exam_class_section(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-oer']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'OERRS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'OERRC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'OERES'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Result',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000002',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;
        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();

        $exam = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'OE Result Exam '.$suffix,
            'attempt' => 3,
            'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '00:30:00',
            'passing_percentage' => 40,
            'description' => 'Result report exam',
            'publish_result' => 1,
            'answer_word_count' => 0,
            'is_active' => 1,
            'is_marks_display' => 1,
            'is_neg_marking' => 0,
            'is_random_question' => 0,
            'is_rank_generated' => 0,
            'publish_exam_notification' => 0,
            'publish_result_notification' => 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        $onlineexamStudentId = DB::table('onlineexam_students')->insertGetId([
            'onlineexam_id' => $exam->id,
            'student_session_id' => $studentSession->id,
            'is_attempted' => 1,
            'rank' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('onlineexam_attempts')->insert([
            'onlineexam_student_id' => $onlineexamStudentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/admin/onlineexam/report')
            ->assertOk()
            ->assertSee('OE Result Exam '.$suffix, false);

        $missing = $this->post('/admin/onlineexam/report', [
            'exam_id' => '',
            'class_id' => '',
            'section_id' => '',
        ]);
        $missing->assertOk()
            ->assertSee('field is required', false);

        $page = $this->post('/admin/onlineexam/report', [
            'exam_id' => $exam->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
        ]);
        $page->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('3', false)
            ->assertSee('admin/onlineexam/studentresult/'.$exam->id.'/'.$onlineexamStudentId, false);

        $content = $page->getContent();
        // remaining = attempt(3) - attempts(1) = 2
        $this->assertMatchesRegularExpression('/>3<\/td>\s*<td>2<\/td>/', $content);
    }

    public function test_rank_report_lists_attempted_students_with_scores(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-oerk']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'OERK-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'OERK-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'OERK'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Ranked',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000003',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;
        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'OE Rank Subject '.$suffix,
            'code' => 'ORK'.$suffix,
            'type' => 'theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $exam = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'OE Rank Exam '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '00:15:00',
            'passing_percentage' => 40,
            'description' => 'Rank report exam',
            'publish_result' => 1,
            'answer_word_count' => 0,
            'is_active' => 1,
            'is_marks_display' => 1,
            'is_neg_marking' => 0,
            'is_random_question' => 0,
            'is_rank_generated' => 1,
            'publish_exam_notification' => 0,
            'publish_result_notification' => 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        $question = Question::query()->create([
            'staff_id' => $this->createdStaffIds[0],
            'subject_id' => $subjectId,
            'question_type' => 'singlechoice',
            'level' => 'low',
            'class_id' => $class->id,
            'section_id' => null,
            'question' => 'Rank Q '.$suffix,
            'opt_a' => 'A',
            'opt_b' => 'B',
            'opt_c' => '',
            'opt_d' => '',
            'opt_e' => '',
            'correct' => 'opt_a',
            'descriptive_word_limit' => 0,
        ]);
        $this->cleanupQuestionIds[] = $question->id;

        $oqId = (int) DB::table('onlineexam_questions')->insertGetId([
            'question_id' => $question->id,
            'onlineexam_id' => $exam->id,
            'session_id' => $session->id,
            'marks' => 10,
            'neg_marks' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attemptedId = DB::table('onlineexam_students')->insertGetId([
            'onlineexam_id' => $exam->id,
            'student_session_id' => $studentSession->id,
            'is_attempted' => 1,
            'rank' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('onlineexam_student_results')->insert([
            'onlineexam_student_id' => $attemptedId,
            'onlineexam_question_id' => $oqId,
            'select_option' => 'opt_a',
            'marks' => 0,
            'remark' => '',
            'attachment_name' => null,
            'attachment_upload_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/report/onlineexamrank', [
            'exam_id' => '',
        ])->assertOk()->assertSee('field is required', false);

        $page = $this->post('/report/onlineexamrank', [
            'exam_id' => $exam->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
        ]);
        $page->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('OE Rank Exam '.$suffix, false)
            ->assertDontSee(__('system.exam_rank_not_generated'), false);

        $content = $page->getContent();
        $this->assertMatchesRegularExpression('/>1<\/td>\s*<td>'.preg_quote($admissionNo, '/').'/', $content);
        $this->assertStringContainsString('100.00', $content);
    }
}
