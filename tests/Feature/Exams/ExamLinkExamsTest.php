<?php

namespace Tests\Feature\Exams;

use App\Modules\Exams\Models\ExamGroup;
use App\Modules\Exams\Models\ExamGroupExam;
use App\Modules\Exams\Models\ExamGroupExamSubject;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamLinkExamsTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupGroupIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupExamSubjectIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupGroupIds !== []) {
            DB::table('exam_group_exam_connections')
                ->whereIn('exam_group_id', $this->cleanupGroupIds)
                ->delete();
        }

        if ($this->cleanupExamSubjectIds !== []) {
            DB::table('exam_group_class_batch_exam_subjects')
                ->whereIn('id', $this->cleanupExamSubjectIds)
                ->delete();
        }
        $this->cleanupExamSubjectIds = [];

        if ($this->cleanupExamIds !== []) {
            DB::table('exam_group_class_batch_exam_subjects')
                ->whereIn('exam_group_class_batch_exams_id', $this->cleanupExamIds)
                ->delete();
            DB::table('exam_group_class_batch_exams')->whereIn('id', $this->cleanupExamIds)->delete();
        }
        $this->cleanupExamIds = [];

        if ($this->cleanupGroupIds !== []) {
            DB::table('exam_groups')->whereIn('id', $this->cleanupGroupIds)->delete();
        }
        $this->cleanupGroupIds = [];

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

        $token = uniqid('exlnk', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EXLNK-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Exam',
            'surname' => 'Link',
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

    private function makeSubject(string $suffix): int
    {
        $id = (int) DB::table('subjects')->insertGetId([
            'name' => 'Sub-'.$suffix,
            'code' => 'C'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $id;

        return $id;
    }

    private function attachSubject(int $examId, int $subjectId): int
    {
        $row = ExamGroupExamSubject::query()->create([
            'exam_group_class_batch_exams_id' => $examId,
            'subject_id' => $subjectId,
            'date_from' => '2026-08-20',
            'time_from' => '09:00:00',
            'duration' => '01:00',
            'room_no' => 'R1',
            'max_marks' => 100,
            'min_marks' => 33,
            'credit_hours' => 1,
            'is_active' => 0,
        ]);
        $this->cleanupExamSubjectIds[] = $row->id;

        return $row->id;
    }

    public function test_link_exams_validates_and_saves_then_resets(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $sessionId = (int) (DB::table('sch_settings')->value('session_id')
            ?: DB::table('sessions')->orderBy('id')->value('id'));

        $group = ExamGroup::query()->create([
            'name' => 'LinkGroup-'.$suffix,
            'exam_type' => 'basic_system',
            'description' => '',
            'is_active' => 0,
        ]);
        $this->cleanupGroupIds[] = $group->id;

        $examA = ExamGroupExam::query()->create([
            'exam' => 'Mid-'.$suffix,
            'exam_group_id' => $group->id,
            'session_id' => $sessionId,
            'description' => '',
            'use_exam_roll_no' => 0,
            'is_publish' => 0,
            'is_rank_generated' => 0,
            'is_active' => 0,
        ]);
        $examB = ExamGroupExam::query()->create([
            'exam' => 'Final-'.$suffix,
            'exam_group_id' => $group->id,
            'session_id' => $sessionId,
            'description' => '',
            'use_exam_roll_no' => 0,
            'is_publish' => 0,
            'is_rank_generated' => 0,
            'is_active' => 0,
        ]);
        $this->cleanupExamIds[] = $examA->id;
        $this->cleanupExamIds[] = $examB->id;

        $subjectId = $this->makeSubject($suffix);
        $this->attachSubject($examA->id, $subjectId);
        $this->attachSubject($examB->id, $subjectId);

        $this->get('/admin/examgroup/link/'.$group->id)
            ->assertOk()
            ->assertSee('Link Exams', false)
            ->assertSee('Mid-'.$suffix, false);

        $this->post('/admin/examgroup/link/'.$group->id, [
            'exam' => [$examA->id],
            'weightage' => [$examA->id => 100],
        ])->assertSessionHasErrors();

        $this->post('/admin/examgroup/link/'.$group->id, [
            'exam' => [$examA->id, $examB->id],
            'weightage' => [$examA->id => 40, $examB->id => 40],
        ])->assertSessionHasErrors('exam_weightage');

        $extraSubject = $this->makeSubject('x'.$suffix);
        $this->attachSubject($examB->id, $extraSubject);

        $this->post('/admin/examgroup/link/'.$group->id, [
            'exam' => [$examA->id, $examB->id],
            'weightage' => [$examA->id => 40, $examB->id => 60],
        ])->assertSessionHasErrors('exam');

        // Restore matching subjects on B: remove extra
        DB::table('exam_group_class_batch_exam_subjects')
            ->where('exam_group_class_batch_exams_id', $examB->id)
            ->where('subject_id', $extraSubject)
            ->delete();
        $this->cleanupExamSubjectIds = array_values(array_filter(
            $this->cleanupExamSubjectIds,
            function ($id) use ($examB, $extraSubject) {
                $row = DB::table('exam_group_class_batch_exam_subjects')->where('id', $id)->first();

                return $row !== null;
            }
        ));

        $this->post('/admin/examgroup/link/'.$group->id, [
            'exam' => [$examA->id, $examB->id],
            'weightage' => [$examA->id => 40, $examB->id => 60],
        ])->assertRedirect('/admin/examgroup/link/'.$group->id);

        $this->assertDatabaseHas('exam_group_exam_connections', [
            'exam_group_id' => $group->id,
            'exam_group_class_batch_exams_id' => $examA->id,
            'exam_weightage' => 40,
        ]);
        $this->assertDatabaseHas('exam_group_exam_connections', [
            'exam_group_id' => $group->id,
            'exam_group_class_batch_exams_id' => $examB->id,
            'exam_weightage' => 60,
        ]);

        $this->post('/admin/examgroup/link/'.$group->id.'/reset')
            ->assertRedirect('/admin/examgroup/link/'.$group->id);

        $this->assertDatabaseMissing('exam_group_exam_connections', [
            'exam_group_id' => $group->id,
        ]);
    }
}
