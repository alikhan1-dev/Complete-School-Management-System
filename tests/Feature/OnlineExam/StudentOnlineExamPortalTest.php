<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\OnlineExam\Models\Question;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentOnlineExamPortalTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    /** @var list<int> */
    private array $cleanupQuestionIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupAssignIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupAssignIds !== []) {
            DB::table('onlineexam_attempts')->whereIn('onlineexam_student_id', $this->cleanupAssignIds)->delete();
            DB::table('onlineexam_student_results')->whereIn('onlineexam_student_id', $this->cleanupAssignIds)->delete();
            DB::table('onlineexam_students')->whereIn('id', $this->cleanupAssignIds)->delete();
        }
        $this->cleanupAssignIds = [];

        if ($this->cleanupExamIds !== []) {
            DB::table('onlineexam_questions')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
            DB::table('onlineexam')->whereIn('id', $this->cleanupExamIds)->delete();
        }
        $this->cleanupExamIds = [];

        if ($this->cleanupQuestionIds !== []) {
            DB::table('questions')->whereIn('id', $this->cleanupQuestionIds)->delete();
        }
        $this->cleanupQuestionIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupUserIds !== []) {
            DB::table('users')->whereIn('id', $this->cleanupUserIds)->delete();
        }
        $this->cleanupUserIds = [];

        if ($this->cleanupClassIds !== []) {
            DB::table('class_sections')->whereIn('class_id', $this->cleanupClassIds)->delete();
            DB::table('classes')->whereIn('id', $this->cleanupClassIds)->delete();
        }
        $this->cleanupClassIds = [];

        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
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

        $token = uniqid('oestf', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OEST-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Oe',
            'surname' => 'Staff',
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
     * @return array{student:Student,sessionId:int,session:AcademicSession,classId:int,sectionId:int}
     */
    private function seedStudentAndActAsPortal(): array
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-oep']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'OES-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $class = SchoolClass::query()->create(['class' => 'OEC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'OEADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Portal',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        // Admission already creates a portal user (plaintext password; users.password is short).
        $user = PortalUser::query()
            ->where('user_id', $student->id)
            ->where('role', 'student')
            ->firstOrFail();
        $user->login_token = 'tok'.$suffix;
        $user->save();
        $this->cleanupUserIds[] = (int) $user->id;

        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => $studentSessionId]]);

        return [
            'student' => $student,
            'sessionId' => $studentSessionId,
            'session' => $session,
            'classId' => (int) $class->id,
            'sectionId' => (int) $section->id,
        ];
    }

    public function test_student_can_list_take_and_submit_objective_exam(): void
    {
        $ctx = $this->seedStudentAndActAsPortal();
        $suffix = uniqid();

        $exam = OnlineExam::query()->create([
            'session_id' => $ctx['session']->id,
            'exam' => 'Portal Exam '.$suffix,
            'attempt' => 2,
            'exam_from' => now()->subHour()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'is_quiz' => 1,
            'auto_publish_date' => null,
            'duration' => '01:00:00',
            'passing_percentage' => 40,
            'description' => 'Portal test exam',
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

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'OE Portal Subject '.$suffix,
            'code' => 'OEP'.$suffix,
            'type' => 'theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $question = Question::query()->create([
            'staff_id' => $this->createdStaffIds[0],
            'subject_id' => $subjectId,
            'question_type' => 'singlechoice',
            'level' => 'low',
            'class_id' => $ctx['classId'],
            'section_id' => null,
            'question' => 'What is 2+2?',
            'opt_a' => '3',
            'opt_b' => '4',
            'opt_c' => '5',
            'opt_d' => '',
            'opt_e' => '',
            'correct' => 'opt_b',
            'descriptive_word_limit' => 0,
        ]);
        $this->cleanupQuestionIds[] = $question->id;

        $oqId = (int) DB::table('onlineexam_questions')->insertGetId([
            'question_id' => $question->id,
            'onlineexam_id' => $exam->id,
            'session_id' => $ctx['session']->id,
            'marks' => 5,
            'neg_marks' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignId = (int) DB::table('onlineexam_students')->insertGetId([
            'onlineexam_id' => $exam->id,
            'student_session_id' => $ctx['sessionId'],
            'is_attempted' => 0,
            'rank' => 0,
            'quiz_attempted' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupAssignIds[] = $assignId;

        $this->get('/user/onlineexam')
            ->assertOk()
            ->assertSee('Portal Exam '.$suffix, false);

        $this->get('/user/onlineexam/view/'.$exam->id)
            ->assertOk()
            ->assertSee('Start Exam', false);

        $this->get('/user/onlineexam/take/'.$exam->id)
            ->assertOk()
            ->assertSee('What is 2+2?', false)
            ->assertSee('Submit Exam', false);

        $this->assertSame(1, (int) DB::table('onlineexam_attempts')->where('onlineexam_student_id', $assignId)->count());

        $this->post('/user/onlineexam/save', [
            'exam_id' => $exam->id,
            'onlineexam_student_id' => $assignId,
            'answers' => [
                $oqId => 'opt_b',
            ],
        ])->assertRedirect('/user/onlineexam');

        $this->assertSame(1, (int) DB::table('onlineexam_students')->where('id', $assignId)->value('is_attempted'));
        $this->assertDatabaseHas('onlineexam_student_results', [
            'onlineexam_student_id' => $assignId,
            'onlineexam_question_id' => $oqId,
            'select_option' => 'opt_b',
        ]);

        $this->get('/user/onlineexam/view/'.$exam->id)
            ->assertOk()
            ->assertSee('Result', false)
            ->assertSee('Score %', false)
            ->assertDontSee('Start Exam', false);
    }
}
