<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\OnlineExam\Models\Question;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionBankCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupQuestionIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    protected function tearDown(): void
    {
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

        $token = uniqid('qbank', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'QB-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Question',
            'surname' => 'Bank',
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

    public function test_question_bank_crud_supports_choice_types(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $classId = (int) DB::table('classes')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $classId);

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'QB Subject '.$suffix,
            'code' => 'QB'.$suffix,
            'type' => 'theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $this->get('/admin/question')->assertOk()->assertSee('Question Bank', false);

        $this->post('/admin/question', [
            'subject_id' => $subjectId,
            'question' => 'What is 2+2? '.$suffix,
            'question_type' => 'singlechoice',
            'question_level' => 'low',
            'class_id' => $classId,
            'opt_a' => '3',
            'opt_b' => '4',
            'opt_c' => '5',
            'correct' => 'opt_b',
        ])->assertRedirect('/admin/question');

        $single = Question::query()->where('question', 'What is 2+2? '.$suffix)->firstOrFail();
        $this->cleanupQuestionIds[] = $single->id;
        $this->assertSame('singlechoice', $single->question_type);
        $this->assertSame('opt_b', $single->correct);
        $this->assertSame(0, (int) $single->descriptive_word_limit);

        $this->post('/admin/question', [
            'subject_id' => $subjectId,
            'question' => 'Select even numbers '.$suffix,
            'question_type' => 'multichoice',
            'question_level' => 'medium',
            'class_id' => $classId,
            'opt_a' => '1',
            'opt_b' => '2',
            'opt_c' => '4',
            'ans' => ['opt_b', 'opt_c'],
        ])->assertRedirect('/admin/question');

        $multi = Question::query()->where('question', 'Select even numbers '.$suffix)->firstOrFail();
        $this->cleanupQuestionIds[] = $multi->id;
        $this->assertSame(['opt_b', 'opt_c'], json_decode((string) $multi->correct, true));

        $this->post('/admin/question', [
            'subject_id' => $subjectId,
            'question' => 'Earth is round '.$suffix,
            'question_type' => 'true_false',
            'question_level' => 'low',
            'class_id' => $classId,
            'correct_true_false' => 'true',
        ])->assertRedirect('/admin/question');

        $tf = Question::query()->where('question', 'Earth is round '.$suffix)->firstOrFail();
        $this->cleanupQuestionIds[] = $tf->id;
        $this->assertSame('true', $tf->correct);

        $this->post('/admin/question', [
            'subject_id' => $subjectId,
            'question' => 'Explain gravity '.$suffix,
            'question_type' => 'descriptive',
            'question_level' => 'high',
            'class_id' => $classId,
        ])->assertRedirect('/admin/question');

        $desc = Question::query()->where('question', 'Explain gravity '.$suffix)->firstOrFail();
        $this->cleanupQuestionIds[] = $desc->id;
        $this->assertSame('', (string) $desc->correct);

        $this->post('/admin/question/edit/'.$single->id, [
            'subject_id' => $subjectId,
            'question' => 'What is 2+2 updated '.$suffix,
            'question_type' => 'singlechoice',
            'question_level' => 'medium',
            'class_id' => $classId,
            'opt_a' => '3',
            'opt_b' => '4',
            'correct' => 'opt_b',
        ])->assertRedirect('/admin/question');
        $single->refresh();
        $this->assertSame('What is 2+2 updated '.$suffix, $single->question);
        $this->assertSame('medium', $single->level);

        $this->get('/admin/question/read/'.$single->id)->assertOk()->assertSee('What is 2+2 updated '.$suffix, false);

        $this->get('/admin/question/delete/'.$single->id)->assertRedirect('/admin/question');
        $this->assertNull(Question::query()->find($single->id));
        $this->cleanupQuestionIds = array_values(array_filter(
            $this->cleanupQuestionIds,
            fn (int $id) => $id !== $single->id
        ));

        foreach ([$multi->id, $tf->id, $desc->id] as $id) {
            $this->get('/admin/question/delete/'.$id)->assertRedirect('/admin/question');
        }
        $this->cleanupQuestionIds = [];
    }
}
