<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\OnlineExam\Models\OnlineExamQuestion;
use App\Modules\OnlineExam\Models\Question;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineExamAttachQuestionsTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    /** @var list<int> */
    private array $cleanupQuestionIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    protected function tearDown(): void
    {
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

        $token = uniqid('oxaq', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OXAQ-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Attach',
            'surname' => 'Questions',
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

    public function test_attach_update_detach_questions_and_quiz_blocks_descriptive(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $sessionId = (int) (DB::table('sch_settings')->value('session_id') ?: DB::table('sessions')->orderBy('id')->value('id'));
        $classId = (int) DB::table('classes')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $sessionId);
        $this->assertGreaterThan(0, $classId);

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'AQ Subject '.$suffix,
            'code' => 'AQ'.$suffix,
            'type' => 'theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $single = Question::query()->create([
            'staff_id' => $this->createdStaffIds[0],
            'subject_id' => $subjectId,
            'question_type' => 'singlechoice',
            'level' => 'low',
            'class_id' => $classId,
            'section_id' => null,
            'question' => 'Attach single '.$suffix,
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
            'level' => 'high',
            'class_id' => $classId,
            'section_id' => null,
            'question' => 'Attach descriptive '.$suffix,
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
            'session_id' => $sessionId,
            'exam' => 'Attach Exam '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->addDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '01:00:00',
            'passing_percentage' => 40,
            'description' => 'attach questions test',
            'publish_result' => 0,
            'answer_word_count' => -1,
            'is_active' => '1',
            'is_marks_display' => 0,
            'is_neg_marking' => 0,
            'is_random_question' => 0,
            'is_rank_generated' => 0,
            'publish_exam_notification' => 0,
            'publish_result_notification' => 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        $this->get('/admin/onlineexam/questions/'.$exam->id)
            ->assertOk()
            ->assertSee('Add Questions', false)
            ->assertSee('Attach single '.$suffix, false);

        $this->post('/admin/onlineexam/questions/'.$exam->id, [
            'question_id' => $single->id,
            'marks' => 5,
            'neg_marks' => 1,
        ])->assertRedirect('/admin/onlineexam/questions/'.$exam->id);

        $link = OnlineExamQuestion::query()
            ->where('onlineexam_id', $exam->id)
            ->where('question_id', $single->id)
            ->firstOrFail();
        $this->assertSame(5.0, (float) $link->marks);
        $this->assertSame(1.0, (float) $link->neg_marks);
        $this->assertSame($sessionId, (int) $link->session_id);

        $this->post('/admin/onlineexam/questions/'.$exam->id, [
            'question_id' => $single->id,
            'marks' => 2,
            'neg_marks' => 0,
        ])->assertSessionHasErrors('question_id');

        $this->post('/admin/onlineexam/questions/'.$exam->id.'/marks/'.$link->id, [
            'marks' => 8,
            'neg_marks' => 0.5,
        ])->assertRedirect('/admin/onlineexam/questions/'.$exam->id);
        $link->refresh();
        $this->assertSame(8.0, (float) $link->marks);
        $this->assertSame(0.5, (float) $link->neg_marks);

        $this->post('/admin/onlineexam/questions/'.$exam->id, [
            'question_id' => $desc->id,
            'marks' => 10,
            'neg_marks' => 0,
        ])->assertRedirect('/admin/onlineexam/questions/'.$exam->id);
        $this->assertTrue(
            OnlineExamQuestion::query()
                ->where('onlineexam_id', $exam->id)
                ->where('question_id', $desc->id)
                ->exists()
        );

        $quiz = OnlineExam::query()->create([
            'session_id' => $sessionId,
            'exam' => 'Quiz Attach '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->addDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'is_quiz' => 1,
            'auto_publish_date' => null,
            'duration' => '00:30:00',
            'passing_percentage' => 50,
            'description' => 'quiz attach test',
            'publish_result' => 0,
            'answer_word_count' => -1,
            'is_active' => '1',
            'is_marks_display' => 0,
            'is_neg_marking' => 0,
            'is_random_question' => 0,
            'is_rank_generated' => 0,
            'publish_exam_notification' => 0,
            'publish_result_notification' => 0,
        ]);
        $this->cleanupExamIds[] = $quiz->id;

        $this->get('/admin/onlineexam/questions/'.$quiz->id)
            ->assertOk()
            ->assertDontSee('Attach descriptive '.$suffix, false);

        $this->post('/admin/onlineexam/questions/'.$quiz->id, [
            'question_id' => $desc->id,
            'marks' => 10,
            'neg_marks' => 0,
        ])->assertSessionHasErrors('question_id');

        $this->get('/admin/onlineexam/questions/'.$exam->id.'/detach/'.$link->id)
            ->assertRedirect('/admin/onlineexam/questions/'.$exam->id);
        $this->assertNull(OnlineExamQuestion::query()->find($link->id));
    }
}
