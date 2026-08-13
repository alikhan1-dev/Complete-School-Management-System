<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineExamCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            DB::table('onlineexam')->whereIn('id', $this->cleanupExamIds)->delete();
        }
        $this->cleanupExamIds = [];

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

        $token = uniqid('oxexam', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OX-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Online',
            'surname' => 'Exam',
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

    public function test_online_exam_crud_open_closed_and_quiz_rules(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $sessionId = (int) (DB::table('sch_settings')->value('session_id') ?: DB::table('sessions')->orderBy('id')->value('id'));
        $this->assertGreaterThan(0, $sessionId);

        $this->get('/admin/onlineexam')->assertOk()->assertSee('Online Examinations', false);

        $from = now()->addDay()->format('Y-m-d\TH:i');
        $to = now()->addDays(3)->format('Y-m-d\TH:i');

        $this->post('/admin/onlineexam', [
            'exam' => 'Open Exam '.$suffix,
            'attempt' => 2,
            'exam_from' => $from,
            'exam_to' => $to,
            'duration' => '01:30:00',
            'passing_percentage' => 40,
            'word_limit' => -1,
            'description' => 'Open exam description',
            'is_active' => '1',
            'publish_result' => '1',
            'is_marks_display' => '1',
            'auto_publish_date' => now()->addDays(4)->format('Y-m-d\TH:i'),
        ])->assertRedirect('/admin/onlineexam');

        $open = OnlineExam::query()->where('exam', 'Open Exam '.$suffix)->firstOrFail();
        $this->cleanupExamIds[] = $open->id;
        $this->assertSame($sessionId, (int) $open->session_id);
        $this->assertSame('1', (string) $open->is_active);
        $this->assertSame(1, (int) $open->publish_result);
        $this->assertSame(0, (int) $open->publish_exam_notification);
        $this->assertNotNull($open->auto_publish_date);

        $this->get('/admin/onlineexam')->assertOk()->assertSee('Open Exam '.$suffix, false);

        $this->post('/admin/onlineexam', [
            'exam' => 'Quiz Exam '.$suffix,
            'attempt' => 1,
            'exam_from' => $from,
            'exam_to' => $to,
            'duration' => '00:45:00',
            'passing_percentage' => 50,
            'word_limit' => -1,
            'description' => 'Quiz description',
            'is_active' => '1',
            'publish_result' => '1',
            'is_quiz' => '1',
            'auto_publish_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
        ])->assertRedirect('/admin/onlineexam');

        $quiz = OnlineExam::query()->where('exam', 'Quiz Exam '.$suffix)->firstOrFail();
        $this->cleanupExamIds[] = $quiz->id;
        $this->assertSame(1, (int) $quiz->is_quiz);
        $this->assertSame(0, (int) $quiz->publish_result);
        $this->assertNull($quiz->auto_publish_date);

        $closedFrom = now()->subDays(5)->format('Y-m-d\TH:i');
        $closedTo = now()->subDay()->format('Y-m-d\TH:i');
        $this->post('/admin/onlineexam', [
            'exam' => 'Closed Exam '.$suffix,
            'attempt' => 1,
            'exam_from' => $closedFrom,
            'exam_to' => $closedTo,
            'duration' => '01:00:00',
            'passing_percentage' => 33,
            'word_limit' => 200,
            'description' => 'Closed exam description',
        ])->assertRedirect('/admin/onlineexam');

        $closed = OnlineExam::query()->where('exam', 'Closed Exam '.$suffix)->firstOrFail();
        $this->cleanupExamIds[] = $closed->id;
        $this->assertSame('0', (string) $closed->is_active);
        $this->assertSame(200, (int) $closed->answer_word_count);

        $this->post('/admin/onlineexam', [
            'exam' => 'Bad Duration '.$suffix,
            'attempt' => 1,
            'exam_from' => $from,
            'exam_to' => $to,
            'duration' => '00:00:00',
            'passing_percentage' => 33,
            'word_limit' => -1,
            'description' => 'Should fail',
        ])->assertSessionHasErrors('duration');

        $this->post('/admin/onlineexam', [
            'exam' => 'Bad Word Limit '.$suffix,
            'attempt' => 1,
            'exam_from' => $from,
            'exam_to' => $to,
            'duration' => '01:00:00',
            'passing_percentage' => 33,
            'word_limit' => 0,
            'description' => 'Should fail',
        ])->assertSessionHasErrors('word_limit');

        $this->post('/admin/onlineexam/edit/'.$open->id, [
            'exam' => 'Open Exam Updated '.$suffix,
            'attempt' => 3,
            'exam_from' => $from,
            'exam_to' => $to,
            'duration' => '02:00:00',
            'passing_percentage' => 45,
            'word_limit' => -1,
            'description' => 'Updated description',
            'is_active' => '1',
            'is_neg_marking' => '1',
        ])->assertRedirect('/admin/onlineexam');
        $open->refresh();
        $this->assertSame('Open Exam Updated '.$suffix, $open->exam);
        $this->assertSame(3, (int) $open->attempt);
        $this->assertSame(1, (int) $open->is_neg_marking);

        foreach ([$open->id, $quiz->id, $closed->id] as $id) {
            $this->get('/admin/onlineexam/delete/'.$id)->assertRedirect('/admin/onlineexam');
            $this->assertNull(OnlineExam::query()->find($id));
        }
        $this->cleanupExamIds = [];
    }
}
