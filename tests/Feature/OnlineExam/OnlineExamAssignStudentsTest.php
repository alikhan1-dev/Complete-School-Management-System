<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\OnlineExam\Models\OnlineExamStudent;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineExamAssignStudentsTest extends TestCase
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

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            DB::table('onlineexam_students')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
            DB::table('onlineexam')->whereIn('id', $this->cleanupExamIds)->delete();
        }
        $this->cleanupExamIds = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('onlineexam_students')
                ->whereIn('student_session_id', function ($q) use ($studentId) {
                    $q->select('id')->from('student_session')->where('student_id', $studentId);
                })
                ->delete();
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

        $token = uniqid('oxas', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OXAS-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Assign',
            'surname' => 'Online',
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

    public function test_assign_and_unassign_students_to_online_exam(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-ox']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'OSX-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $class = SchoolClass::query()->create(['class' => 'OCX-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'OXADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Online',
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

        $exam = OnlineExam::query()->create([
            'session_id' => $session->id,
            'exam' => 'Assign OE '.$suffix,
            'attempt' => 1,
            'exam_from' => now()->addDay()->format('Y-m-d H:i:s'),
            'exam_to' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'is_quiz' => 0,
            'auto_publish_date' => null,
            'duration' => '01:00:00',
            'passing_percentage' => 40,
            'description' => 'assign students test',
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

        $this->get('/admin/onlineexam/assign/'.$exam->id)
            ->assertOk()
            ->assertSee('Assign / View Student', false);

        $this->post('/admin/onlineexam/assign/'.$exam->id, [
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()->assertSee($admissionNo, false);

        $this->post('/admin/onlineexam/addstudent/'.$exam->id, [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'students_id' => [$studentSession->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('onlineexam_students', [
            'onlineexam_id' => $exam->id,
            'student_session_id' => $studentSession->id,
            'is_attempted' => 0,
        ]);
        $this->assertSame(1, OnlineExamStudent::query()->where('onlineexam_id', $exam->id)->count());

        $this->post('/admin/onlineexam/addstudent/'.$exam->id, [
            'class_id' => $class->id,
            'section_id' => $section->id,
            // unchecked = unassign
        ])->assertRedirect();

        $this->assertDatabaseMissing('onlineexam_students', [
            'onlineexam_id' => $exam->id,
            'student_session_id' => $studentSession->id,
        ]);
    }
}
