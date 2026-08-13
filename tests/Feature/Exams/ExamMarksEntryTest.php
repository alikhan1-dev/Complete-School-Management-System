<?php

namespace Tests\Feature\Exams;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Exams\Models\ExamGroup;
use App\Modules\Exams\Models\ExamGroupExam;
use App\Modules\Exams\Models\ExamGroupExamSubject;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamMarksEntryTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupGroupIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupExamSubjectIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            $examStudentIds = DB::table('exam_group_class_batch_exam_students')
                ->whereIn('exam_group_class_batch_exam_id', $this->cleanupExamIds)
                ->pluck('id');
            if ($examStudentIds->isNotEmpty()) {
                DB::table('exam_group_exam_results')
                    ->whereIn('exam_group_class_batch_exam_student_id', $examStudentIds)
                    ->delete();
            }
            DB::table('exam_group_class_batch_exam_students')
                ->whereIn('exam_group_class_batch_exam_id', $this->cleanupExamIds)
                ->delete();
            if ($this->cleanupExamSubjectIds !== []) {
                DB::table('exam_group_exam_results')
                    ->whereIn('exam_group_class_batch_exam_subject_id', $this->cleanupExamSubjectIds)
                    ->delete();
                DB::table('exam_group_class_batch_exam_subjects')
                    ->whereIn('id', $this->cleanupExamSubjectIds)
                    ->delete();
            }
            DB::table('exam_group_class_batch_exams')->whereIn('id', $this->cleanupExamIds)->delete();
        }
        $this->cleanupExamIds = [];
        $this->cleanupExamSubjectIds = [];

        if ($this->cleanupGroupIds !== []) {
            DB::table('exam_groups')->whereIn('id', $this->cleanupGroupIds)->delete();
        }
        $this->cleanupGroupIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

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

        $token = uniqid('exmk', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EXMK-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Exam',
            'surname' => 'Marks',
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

    public function test_exam_marks_search_save_and_update_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'MKADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Marks',
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
            'name' => 'Science-'.$suffix,
            'code' => 'SC'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $group = ExamGroup::query()->create([
            'name' => 'MarksGroup-'.$suffix,
            'exam_type' => 'basic_system',
            'description' => '',
            'is_active' => 0,
        ]);
        $this->cleanupGroupIds[] = $group->id;

        $exam = ExamGroupExam::query()->create([
            'exam' => 'MarksExam-'.$suffix,
            'exam_group_id' => $group->id,
            'session_id' => $session->id,
            'description' => '',
            'use_exam_roll_no' => 0,
            'is_publish' => 0,
            'is_rank_generated' => 0,
            'is_active' => 0,
        ]);
        $this->cleanupExamIds[] = $exam->id;

        $examSubject = ExamGroupExamSubject::query()->create([
            'exam_group_class_batch_exams_id' => $exam->id,
            'subject_id' => $subjectId,
            'date_from' => '2026-08-20',
            'time_from' => '09:00:00',
            'duration' => '02:00',
            'room_no' => 'R1',
            'max_marks' => 100,
            'min_marks' => 33,
            'credit_hours' => 1,
            'is_active' => 0,
        ]);
        $this->cleanupExamSubjectIds[] = $examSubject->id;

        $examStudentId = (int) DB::table('exam_group_class_batch_exam_students')->insertGetId([
            'exam_group_class_batch_exam_id' => $exam->id,
            'student_id' => $student->id,
            'student_session_id' => $studentSession->id,
            'rank' => 0,
            'is_active' => 0,
        ]);

        $this->get('/admin/examgroup/marks/'.$exam->id)
            ->assertOk()
            ->assertSee('Exam Marks', false);

        $this->post('/admin/examgroup/marks/'.$exam->id, [
            'exam_group_class_batch_exam_subject_id' => $examSubject->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'session_id' => $session->id,
        ])->assertOk()->assertSee($admissionNo, false);

        $this->post('/admin/examgroup/entrymarks/'.$exam->id, [
            'exam_group_class_batch_exam_subject_id' => $examSubject->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'session_id' => $session->id,
            'exam_group_student_id' => [$examStudentId],
            'exam_group_student_mark_'.$examStudentId => '88.5',
            'exam_group_student_note_'.$examStudentId => 'Good',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_group_exam_results', [
            'exam_group_class_batch_exam_subject_id' => $examSubject->id,
            'exam_group_class_batch_exam_student_id' => $examStudentId,
            'attendence' => 'present',
            'get_marks' => 88.5,
            'note' => 'Good',
        ]);

        $this->post('/admin/examgroup/entrymarks/'.$exam->id, [
            'exam_group_class_batch_exam_subject_id' => $examSubject->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'session_id' => $session->id,
            'exam_group_student_id' => [$examStudentId],
            'exam_group_student_attendance_'.$examStudentId => 'absent',
            'exam_group_student_note_'.$examStudentId => 'Sick',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_group_exam_results', [
            'exam_group_class_batch_exam_subject_id' => $examSubject->id,
            'exam_group_class_batch_exam_student_id' => $examStudentId,
            'attendence' => 'absent',
            'get_marks' => 0,
            'note' => 'Sick',
        ]);

        $this->assertSame(
            1,
            DB::table('exam_group_exam_results')
                ->where('exam_group_class_batch_exam_subject_id', $examSubject->id)
                ->where('exam_group_class_batch_exam_student_id', $examStudentId)
                ->count()
        );
    }
}
