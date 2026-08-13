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
use Tests\TestCase;

class HomeworkReportTest extends TestCase
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

    protected function tearDown(): void
    {
        if ($this->cleanupHomeworkIds !== []) {
            DB::table('homework_evaluation')->whereIn('homework_id', $this->cleanupHomeworkIds)->delete();
            DB::table('submit_assignment')->whereIn('homework_id', $this->cleanupHomeworkIds)->delete();
            DB::table('homework')->whereIn('id', $this->cleanupHomeworkIds)->delete();
        }
        $this->cleanupHomeworkIds = [];

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

    private function actingAsSuperAdmin(): Staff
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('hwrpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HWR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Report',
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
        $staff = Staff::query()->findOrFail($staffId);
        $this->actingAs($staff, 'staff');

        return $staff;
    }

    /**
     * @return array{
     *   staff:Staff,
     *   class:SchoolClass,
     *   section:Section,
     *   group:SubjectGroup,
     *   groupSubject:SubjectGroupSubject,
     *   session:AcademicSession,
     *   homework:Homework,
     *   student:Student,
     *   studentSessionId:int
     * }
     */
    private function seedReportContext(): array
    {
        $staff = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-hwr']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'HWRS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'HWRC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'Report Subject '.$suffix,
            'code' => 'RS'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'RWG-'.$suffix,
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

        $admissionNo = 'HWRADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Report',
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
            'staff_id' => $staff->id,
            'subject_group_subject_id' => $groupSubject->id,
            'subject_id' => null,
            'homework_date' => now()->format('Y-m-d'),
            'submit_date' => now()->addDays(2)->format('Y-m-d'),
            'marks' => 10,
            'description' => 'Report homework '.$suffix,
            'create_date' => now()->format('Y-m-d'),
            'evaluation_date' => now()->format('Y-m-d'),
            'document' => '',
            'created_by' => $staff->id,
            'evaluated_by' => $staff->id,
        ]);
        $this->cleanupHomeworkIds[] = $homework->id;

        DB::table('submit_assignment')->insert([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'message' => 'Submitted for report '.$suffix,
            'docs' => '',
        ]);

        DB::table('homework_evaluation')->insert([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'student_session_id' => $studentSessionId,
            'date' => now()->format('Y-m-d'),
            'status' => 'Complete',
            'marks' => 8,
            'note' => 'Good work '.$suffix,
        ]);

        return [
            'staff' => $staff,
            'class' => $class,
            'section' => $section,
            'group' => $group,
            'groupSubject' => $groupSubject,
            'session' => $session,
            'homework' => $homework,
            'student' => $student,
            'studentSessionId' => $studentSessionId,
            'suffix' => $suffix,
        ];
    }

    public function test_homework_core_reports_hub_homework_evaluation_marks(): void
    {
        $ctx = $this->seedReportContext();

        $this->get('/homework/homeworkordailyassignmentreport')
            ->assertOk()
            ->assertSee('Homework Report', false)
            ->assertSee('Homework Evaluation Report', false)
            ->assertSee('Homework Marks Report', false);

        $this->get('/homework/homeworkreport?'.http_build_query([
            'search' => 1,
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_id' => $ctx['groupSubject']->id,
        ]))
            ->assertOk()
            ->assertSee('Report Subject', false)
            ->assertSee((string) $ctx['class']->class, false);

        $this->get('/homework/homeworkreport/students?'.http_build_query([
            'homework_id' => $ctx['homework']->id,
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'type' => 'homework_submitted',
        ]))
            ->assertOk()
            ->assertSee('Report Pupil', false)
            ->assertSee('Submitted for report '.$ctx['suffix'], false);

        $this->get('/homework/evaluation_report?'.http_build_query([
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_id' => $ctx['groupSubject']->id,
        ]))
            ->assertOk()
            ->assertSee('Homework Evaluation Report', false)
            ->assertSee('Report Subject', false)
            ->assertSee('100', false);

        $this->get('/homework/homework_marksreport?'.http_build_query([
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_id' => $ctx['groupSubject']->id,
        ]))
            ->assertOk()
            ->assertSee('Homework Marks Report', false)
            ->assertSee('Good work '.$ctx['suffix'], false)
            ->assertSee('8', false);
    }
}
