<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\OnlineExam\Models\OnlineExamQuestion;
use App\Modules\OnlineExam\Models\OnlineExamStudent;
use App\Modules\OnlineExam\Models\Question;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineExamResultsEvaluationTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    /** @var list<int> */
    private array $cleanupQuestionIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            $examStudentIds = DB::table('onlineexam_students')
                ->whereIn('onlineexam_id', $this->cleanupExamIds)
                ->pluck('id');
            if ($examStudentIds->isNotEmpty()) {
                DB::table('onlineexam_student_results')
                    ->whereIn('onlineexam_student_id', $examStudentIds)
                    ->delete();
            }
            DB::table('onlineexam_questions')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
            DB::table('onlineexam_students')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
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
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

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

        $token = uniqid('oxre', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OXRE-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Result',
            'surname' => 'Eval',
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

    public function test_results_scoring_and_descriptive_fillmarks(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-re']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'RE-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $class = SchoolClass::query()->create(['class' => 'RC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'OXR'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Result',
            'lastname' => 'Pupil',
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

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'RE Subject '.$suffix,
            'code' => 'RE'.$suffix,
            'type' => 'theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $single = Question::query()->create([
            'staff_id' => $this->createdStaffIds[0],
            'subject_id' => $subjectId,
            'question_type' => 'singlechoice',
            'level' => 'low',
            'class_id' => $class->id,
            'question' => 'Single RE '.$suffix,
            'opt_a' => 'A',
            'opt_b' => 'B',
            'opt_c' => '',
            'opt_d' => '',
            'opt_e' => '',
            'correct' => 'opt_a',
            'descriptive_word_limit' => 0,
        ]);
        $this->cleanupQuestionIds[] = $single->id;

        $desc = Question::query()->create([
            'staff_id' => $this->createdStaffIds[0],
            'subject_id' => $subjectId,
            'question_type' => 'descriptive',
            'level' => 'medium',
            'class_id' => $class->id,
            'question' => 'Desc RE '.$suffix,
            'opt_a' => '',
            'opt_b' => '',
            'opt_c' => '',
            'opt_d' => '',
            'opt_e' => '',
            'correct' => '',
            'descriptive_word_limit' => 0,
        ]);
        $this->cleanupQuestionIds[] = $desc->id;

        $exam = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'Result Exam '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'duration' => '01:00:00',
            'passing_percentage' => 40,
            'description' => 'results test',
            'publish_result' => 1,
            'answer_word_count' => -1,
            'is_active' => '1',
            'is_marks_display' => 1,
            'is_neg_marking' => 0,
            'is_random_question' => 0,
            'is_rank_generated' => 0,
            'publish_exam_notification' => 0,
            'publish_result_notification' => 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        $oqSingle = OnlineExamQuestion::query()->create([
            'onlineexam_id' => $exam->id,
            'question_id' => $single->id,
            'session_id' => $session->id,
            'marks' => 5,
            'neg_marks' => 1,
            'is_active' => '0',
        ]);
        $oqDesc = OnlineExamQuestion::query()->create([
            'onlineexam_id' => $exam->id,
            'question_id' => $desc->id,
            'session_id' => $session->id,
            'marks' => 10,
            'neg_marks' => 0,
            'is_active' => '0',
        ]);

        $examStudent = OnlineExamStudent::query()->create([
            'onlineexam_id' => $exam->id,
            'student_session_id' => $studentSession->id,
            'is_attempted' => 1,
            'rank' => 0,
            'quiz_attempted' => 0,
        ]);

        DB::table('onlineexam_student_results')->insert([
            [
                'onlineexam_student_id' => $examStudent->id,
                'onlineexam_question_id' => $oqSingle->id,
                'select_option' => 'opt_a',
                'marks' => 0,
                'remark' => '',
                'attachment_name' => '',
                'attachment_upload_name' => '',
            ],
            [
                'onlineexam_student_id' => $examStudent->id,
                'onlineexam_question_id' => $oqDesc->id,
                'select_option' => 'My descriptive answer '.$suffix,
                'marks' => 0,
                'remark' => '',
                'attachment_name' => '',
                'attachment_upload_name' => '',
            ],
        ]);

        $descResultId = (int) DB::table('onlineexam_student_results')
            ->where('onlineexam_student_id', $examStudent->id)
            ->where('onlineexam_question_id', $oqDesc->id)
            ->value('id');

        $this->get('/admin/onlineexam/results/'.$exam->id)
            ->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('View Result', false);

        $this->get('/admin/onlineexam/studentresult/'.$exam->id.'/'.$examStudent->id)
            ->assertOk()
            ->assertSee('Student Result', false)
            ->assertSee('Single RE '.$suffix, false)
            ->assertSee('5/5', false);

        $this->get('/admin/onlineexam/evalution/'.$exam->id)
            ->assertOk()
            ->assertSee('Evaluation', false)
            ->assertSee('My descriptive answer '.$suffix, false);

        $this->post('/admin/onlineexam/fillmarks/'.$exam->id, [
            'onlineexam_student_result_id' => $descResultId,
            'question_marks' => 10,
            'fill_mark' => 8,
            'remark' => 'Good effort',
        ])->assertRedirect();

        $this->assertDatabaseHas('onlineexam_student_results', [
            'id' => $descResultId,
            'marks' => 8,
            'remark' => 'Good effort',
        ]);

        $this->post('/admin/onlineexam/fillmarks/'.$exam->id, [
            'onlineexam_student_result_id' => $descResultId,
            'question_marks' => 10,
            'fill_mark' => 12,
            'remark' => 'too high',
        ])->assertSessionHasErrors('fill_mark');
    }
}
