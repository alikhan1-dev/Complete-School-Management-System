<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\OnlineExam\Models\OnlineExam;
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

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            DB::table('onlineexam_questions')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
            DB::table('onlineexam_students')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
            DB::table('onlineexam')->whereIn('id', $this->cleanupExamIds)->delete();
            $this->cleanupExamIds = [];
        }
        if ($this->cleanupQuestionIds !== []) {
            DB::table('questions')->whereIn('id', $this->cleanupQuestionIds)->delete();
            $this->cleanupQuestionIds = [];
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
}
