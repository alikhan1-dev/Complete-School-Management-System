<?php

namespace Tests\Feature\Homework;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Homework\Models\Homework;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HomeworkEvaluationTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupHomeworkIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<string> */
    private array $cleanupAssignmentDocs = [];

    protected function tearDown(): void
    {
        if ($this->cleanupHomeworkIds !== []) {
            DB::table('homework_evaluation')->whereIn('homework_id', $this->cleanupHomeworkIds)->delete();
            DB::table('submit_assignment')->whereIn('homework_id', $this->cleanupHomeworkIds)->delete();
            DB::table('homework')->whereIn('id', $this->cleanupHomeworkIds)->delete();
        }
        $this->cleanupHomeworkIds = [];

        foreach ($this->cleanupAssignmentDocs as $name) {
            $path = public_path('uploads/homework/assignment/'.basename($name));
            if (is_file($path)) {
                File::delete($path);
            }
        }
        $this->cleanupAssignmentDocs = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        foreach ($this->cleanupSubjectGroupIds as $groupId) {
            DB::table('subject_group_class_sections')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_group_subjects')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_groups')->where('id', $groupId)->delete();
        }
        $this->cleanupSubjectGroupIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];

        foreach ($this->cleanupSectionIds as $sectionId) {
            DB::table('sections')->where('id', $sectionId)->delete();
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

        $token = uniqid('hweval', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HWE-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Eval',
            'surname' => 'Staff',
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

    public function test_homework_evaluation_save_and_assignment_download(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-hwe']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'HWES-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'HWEC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'Eval Subject '.$suffix,
            'code' => 'EV'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'EVG-'.$suffix,
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
        ]);

        SubjectGroupClassSection::query()->create([
            'subject_group_id' => $group->id,
            'class_section_id' => $classSection->id,
            'session_id' => $session->id,
            'is_active' => 1,
        ]);

        $admissionNo = 'HWEADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Eval',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $homework = Homework::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'session_id' => $session->id,
            'staff_id' => $this->createdStaffIds[0],
            'subject_group_subject_id' => $groupSubject->id,
            'subject_id' => null,
            'homework_date' => now()->format('Y-m-d'),
            'submit_date' => now()->addDays(2)->format('Y-m-d'),
            'marks' => 10,
            'description' => 'Eval homework '.$suffix,
            'create_date' => now()->format('Y-m-d'),
            'evaluation_date' => null,
            'document' => '',
            'created_by' => $this->createdStaffIds[0],
            'evaluated_by' => null,
        ]);
        $this->cleanupHomeworkIds[] = $homework->id;

        File::ensureDirectoryExists(public_path('uploads/homework/assignment'));
        $docName = 'assign-'.$suffix.'.txt';
        File::put(public_path('uploads/homework/assignment/'.$docName), 'student work');
        $this->cleanupAssignmentDocs[] = $docName;

        $submitId = (int) DB::table('submit_assignment')->insertGetId([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'message' => 'My answer '.$suffix,
            'docs' => $docName,
            'file_name' => 'answer.txt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/homework/evaluation/'.$homework->id)
            ->assertOk()
            ->assertSee('Evaluate Students', false)
            ->assertSee('Eval', false)
            ->assertSee('Pupil', false)
            ->assertSee('My answer '.$suffix, false);

        $this->post('/homework/add_evaluation', [
            'homework_id' => $homework->id,
            'evaluation_date' => now()->format('Y-m-d'),
            'student_list' => [
                $studentSessionId => 0,
            ],
            'student_id' => [
                $studentSessionId => $student->id,
            ],
            'marks' => [
                $studentSessionId => 8,
            ],
            'note' => [
                $studentSessionId => 'Good work',
            ],
        ])->assertRedirect();

        $homework->refresh();
        $this->assertNotNull($homework->evaluation_date);
        $this->assertSame($this->createdStaffIds[0], (int) $homework->evaluated_by);

        $this->assertDatabaseHas('homework_evaluation', [
            'homework_id' => $homework->id,
            'student_session_id' => $studentSessionId,
            'student_id' => $student->id,
            'status' => 'Complete',
            'note' => 'Good work',
        ]);

        $evalMarks = DB::table('homework_evaluation')
            ->where('homework_id', $homework->id)
            ->where('student_session_id', $studentSessionId)
            ->value('marks');
        $this->assertSame('8.00', number_format((float) $evalMarks, 2));

        $this->get('/homework/assigmnetDownload/'.$submitId)->assertOk();
    }
}
