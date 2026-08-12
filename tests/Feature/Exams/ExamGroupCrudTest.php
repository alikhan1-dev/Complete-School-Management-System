<?php

namespace Tests\Feature\Exams;

use App\Modules\Exams\Models\ExamGroup;
use App\Modules\Exams\Models\ExamGroupExam;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamGroupCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupGroupIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            DB::table('exam_group_class_batch_exam_subjects')
                ->whereIn('exam_group_class_batch_exams_id', $this->cleanupExamIds)
                ->delete();
            DB::table('exam_group_class_batch_exams')->whereIn('id', $this->cleanupExamIds)->delete();
        }
        $this->cleanupExamIds = [];

        if ($this->cleanupGroupIds !== []) {
            $examIds = DB::table('exam_group_class_batch_exams')
                ->whereIn('exam_group_id', $this->cleanupGroupIds)
                ->pluck('id');
            if ($examIds->isNotEmpty()) {
                DB::table('exam_group_class_batch_exam_subjects')
                    ->whereIn('exam_group_class_batch_exams_id', $examIds)
                    ->delete();
                DB::table('exam_group_class_batch_exams')->whereIn('id', $examIds)->delete();
            }
            DB::table('exam_groups')->whereIn('id', $this->cleanupGroupIds)->delete();
        }
        $this->cleanupGroupIds = [];

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

        $token = uniqid('exam', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EXAM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Exams',
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

    public function test_exam_group_and_exam_crud_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $sessionId = (int) (DB::table('sch_settings')->value('session_id')
            ?: DB::table('sessions')->orderBy('id')->value('id'));
        $this->assertGreaterThan(0, $sessionId);

        $this->get('/admin/examgroup')->assertOk()->assertSee('Exam Group', false);
        $this->post('/admin/examgroup', [
            'name' => 'Group-'.$suffix,
            'exam_type' => 'basic_system',
            'description' => 'Phase 5 slice',
        ])->assertRedirect('/admin/examgroup');

        $group = ExamGroup::query()->where('name', 'Group-'.$suffix)->firstOrFail();
        $this->cleanupGroupIds[] = $group->id;
        $this->assertSame('basic_system', $group->exam_type);

        $this->post('/admin/examgroup/edit/'.$group->id, [
            'name' => 'GroupUpdated-'.$suffix,
            'exam_type' => 'gpa',
            'description' => 'Updated',
        ])->assertRedirect('/admin/examgroup');
        $group->refresh();
        $this->assertSame('GroupUpdated-'.$suffix, $group->name);
        $this->assertSame('gpa', $group->exam_type);

        $this->get('/admin/examgroup/addexam/'.$group->id)
            ->assertOk()
            ->assertSee('Exam List', false)
            ->assertSee('GroupUpdated-'.$suffix, false);

        $this->post('/admin/examgroup/addexam/'.$group->id, [
            'exam' => 'Midterm-'.$suffix,
            'session_id' => $sessionId,
            'description' => 'First term',
            'is_publish' => '1',
        ])->assertRedirect('/admin/examgroup/addexam/'.$group->id);

        $exam = ExamGroupExam::query()
            ->where('exam_group_id', $group->id)
            ->where('exam', 'Midterm-'.$suffix)
            ->firstOrFail();
        $this->cleanupExamIds[] = $exam->id;
        $this->assertSame(1, (int) $exam->is_publish);
        $this->assertSame(0, (int) $exam->is_active);
        $this->assertSame($sessionId, (int) $exam->session_id);

        $this->post('/admin/examgroup/addexam/'.$group->id.'/edit/'.$exam->id, [
            'exam' => 'MidtermUpdated-'.$suffix,
            'session_id' => $sessionId,
            'description' => 'Updated exam',
            'is_active' => '1',
            'use_exam_roll_no' => '1',
        ])->assertRedirect('/admin/examgroup/addexam/'.$group->id);
        $exam->refresh();
        $this->assertSame('MidtermUpdated-'.$suffix, $exam->exam);
        $this->assertSame(1, (int) $exam->is_active);
        $this->assertSame(0, (int) $exam->is_publish);
        $this->assertSame(1, (int) $exam->use_exam_roll_no);

        $this->get('/admin/examgroup/addexam/'.$group->id.'/delete/'.$exam->id)
            ->assertRedirect('/admin/examgroup/addexam/'.$group->id);
        $this->assertNull(ExamGroupExam::query()->find($exam->id));
        $this->cleanupExamIds = [];

        $this->get('/admin/examgroup/delete/'.$group->id)->assertRedirect('/admin/examgroup');
        $this->assertNull(ExamGroup::query()->find($group->id));
        $this->cleanupGroupIds = [];
    }
}
