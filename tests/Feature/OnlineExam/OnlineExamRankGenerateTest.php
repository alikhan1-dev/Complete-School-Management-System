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

class OnlineExamRankGenerateTest extends TestCase
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

        $token = uniqid('oerkgen', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OERK-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'OeRank',
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

    public function test_generate_rank_assigns_dense_ranks_by_net_score(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-rk']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'RKS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'RKC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admA = 'RKA'.$suffix;
        $admB = 'RKB'.$suffix;
        foreach ([['adm' => $admA, 'first' => 'High'], ['adm' => $admB, 'first' => 'Low']] as $info) {
            $this->post('/student/create', [
                'admission_no' => $info['adm'],
                'firstname' => $info['first'],
                'lastname' => 'Scorer',
                'gender' => 'Male',
                'dob' => '2012-01-01',
                'class_id' => $class->id,
                'section_id' => $section->id,
                'guardian_is' => 'father',
                'guardian_name' => 'Dad',
                'guardian_phone' => '03001112233',
            ])->assertRedirect();
            $student = Student::query()->where('admission_no', $info['adm'])->firstOrFail();
            $this->cleanupStudentIds[] = $student->id;
        }

        $studentA = Student::query()->where('admission_no', $admA)->firstOrFail();
        $studentB = Student::query()->where('admission_no', $admB)->firstOrFail();
        $ssA = StudentSession::query()->where('student_id', $studentA->id)->where('session_id', $session->id)->firstOrFail();
        $ssB = StudentSession::query()->where('student_id', $studentB->id)->where('session_id', $session->id)->firstOrFail();

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'Rank Gen Sub '.$suffix,
            'code' => 'RG'.$suffix,
            'type' => 'theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $exam = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'Rank Gen Exam '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '00:20:00',
            'passing_percentage' => 40,
            'description' => 'Rank gen',
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

        $question = Question::query()->create([
            'staff_id' => $this->createdStaffIds[0],
            'subject_id' => $subjectId,
            'question_type' => 'singlechoice',
            'level' => 'low',
            'class_id' => $class->id,
            'section_id' => null,
            'question' => 'Rank gen Q '.$suffix,
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

        $assignA = DB::table('onlineexam_students')->insertGetId([
            'onlineexam_id' => $exam->id,
            'student_session_id' => $ssA->id,
            'is_attempted' => 1,
            'rank' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assignB = DB::table('onlineexam_students')->insertGetId([
            'onlineexam_id' => $exam->id,
            'student_session_id' => $ssB->id,
            'is_attempted' => 1,
            'rank' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('onlineexam_student_results')->insert([
            [
                'onlineexam_student_id' => $assignA,
                'onlineexam_question_id' => $oqId,
                'select_option' => 'opt_a',
                'marks' => 0,
                'remark' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'onlineexam_student_id' => $assignB,
                'onlineexam_question_id' => $oqId,
                'select_option' => 'opt_b',
                'marks' => 0,
                'remark' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get('/admin/onlineexam')
            ->assertOk()
            ->assertSee('/admin/onlineexam/rank/'.$exam->id, false);

        $this->get('/admin/onlineexam/rank/'.$exam->id)
            ->assertOk()
            ->assertSee($admA, false)
            ->assertSee($admB, false)
            ->assertSee(__('system.generate_rank'), false);

        $this->post('/admin/onlineexam/saverank/'.$exam->id)
            ->assertRedirect('/admin/onlineexam/rank/'.$exam->id);

        $this->assertSame(1, (int) DB::table('onlineexam')->where('id', $exam->id)->value('is_rank_generated'));
        $this->assertSame(1, (int) DB::table('onlineexam_students')->where('id', $assignA)->value('rank'));
        $this->assertSame(2, (int) DB::table('onlineexam_students')->where('id', $assignB)->value('rank'));
    }

    public function test_rank_page_forbidden_when_result_not_published(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-rk2']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $exam = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'Unpublished Rank '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '00:10:00',
            'passing_percentage' => 40,
            'description' => 'No publish',
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

        $this->get('/admin/onlineexam/rank/'.$exam->id)->assertForbidden();
    }
}
