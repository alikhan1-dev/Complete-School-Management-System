<?php

namespace Tests\Feature\Exams;

use App\Modules\Exams\Models\ExamGroup;
use App\Modules\Exams\Models\ExamGroupExam;
use App\Modules\Exams\Models\ExamGroupExamSubject;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamSubjectCrudTest extends TestCase
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
        if ($this->cleanupExamSubjectIds !== []) {
            DB::table('exam_group_exam_results')
                ->whereIn('exam_group_class_batch_exam_subject_id', $this->cleanupExamSubjectIds)
                ->delete();
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

        $token = uniqid('exsub', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EXSUB-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Exam',
            'surname' => 'Subjects',
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

    public function test_exam_subject_crud_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $sessionId = (int) (DB::table('sch_settings')->value('session_id')
            ?: DB::table('sessions')->orderBy('id')->value('id'));
        $this->assertGreaterThan(0, $sessionId);

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'Math-'.$suffix,
            'code' => 'M'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $group = ExamGroup::query()->create([
            'name' => 'SubGroup-'.$suffix,
            'exam_type' => 'basic_system',
            'description' => 'subjects slice',
            'is_active' => 0,
        ]);
        $this->cleanupGroupIds[] = $group->id;

        $exam = ExamGroupExam::query()->create([
            'exam' => 'Term-'.$suffix,
            'exam_group_id' => $group->id,
            'session_id' => $sessionId,
            'description' => '',
            'use_exam_roll_no' => 0,
            'is_publish' => 0,
            'is_rank_generated' => 0,
            'is_active' => 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        $this->get('/admin/examgroup/examsubject/'.$exam->id)
            ->assertOk()
            ->assertSee('Exam Subjects', false)
            ->assertSee('Term-'.$suffix, false);

        $this->post('/admin/examgroup/examsubject/'.$exam->id, [
            'subject_id' => $subjectId,
            'date_from' => '2026-08-20',
            'time_from' => '09:30',
            'duration' => '02:00',
            'credit_hours' => '3',
            'room_no' => 'A-101',
            'max_marks' => '100',
            'min_marks' => '33',
        ])->assertRedirect('/admin/examgroup/examsubject/'.$exam->id);

        $row = ExamGroupExamSubject::query()
            ->where('exam_group_class_batch_exams_id', $exam->id)
            ->where('subject_id', $subjectId)
            ->firstOrFail();
        $this->cleanupExamSubjectIds[] = $row->id;
        $this->assertSame(100.0, (float) $row->max_marks);
        $this->assertSame('A-101', $row->room_no);

        $this->post('/admin/examgroup/examsubject/'.$exam->id, [
            'subject_id' => $subjectId,
            'date_from' => '2026-08-21',
            'time_from' => '10:00',
            'duration' => '01:00',
            'credit_hours' => '1',
            'room_no' => 'B-1',
            'max_marks' => '50',
            'min_marks' => '20',
        ])->assertSessionHasErrors('subject_id');

        $this->post('/admin/examgroup/examsubject/'.$exam->id.'/edit/'.$row->id, [
            'subject_id' => $subjectId,
            'date_from' => '2026-08-22',
            'time_from' => '11:00',
            'duration' => '03:00',
            'credit_hours' => '4',
            'room_no' => 'Hall-2',
            'max_marks' => '120',
            'min_marks' => '40',
        ])->assertRedirect('/admin/examgroup/examsubject/'.$exam->id);

        $row->refresh();
        $this->assertSame('Hall-2', $row->room_no);
        $this->assertSame(120.0, (float) $row->max_marks);
        $this->assertSame('2026-08-22', substr((string) $row->date_from, 0, 10));

        $this->get('/admin/examgroup/examsubject/'.$exam->id.'/delete/'.$row->id)
            ->assertRedirect('/admin/examgroup/examsubject/'.$exam->id);
        $this->assertNull(ExamGroupExamSubject::query()->find($row->id));
        $this->cleanupExamSubjectIds = [];
    }
}
