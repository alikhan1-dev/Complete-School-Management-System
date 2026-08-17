<?php

namespace Tests\Feature\Reports;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Reports\Services\StudentInformationReportService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentInformationReportFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('users')->where('childs', (string) $studentId)->where('role', 'parent')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupUserIds !== []) {
            DB::table('users')->whereIn('id', $this->cleanupUserIds)->delete();
        }
        $this->cleanupUserIds = [];

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

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('rpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'RPT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Report',
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
            'basic_salary' => 0,
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

        return $staffId;
    }

    /**
     * @return array{student: Student, class: SchoolClass, section: Section, classSectionId: int}
     */
    private function seedStudent(): array
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-rpt']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'RPTS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'RPTC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'RPTADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Ratio',
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
        $user = DB::table('users')->where('user_id', $student->id)->where('role', 'student')->first();
        if ($user) {
            $this->cleanupUserIds[] = (int) $user->id;
        }
        $parent = DB::table('users')->where('role', 'parent')->where('childs', (string) $student->id)->first();
        if ($parent) {
            $this->cleanupUserIds[] = (int) $parent->id;
        }

        return [
            'student' => $student,
            'class' => $class,
            'section' => $section,
            'classSectionId' => (int) $classSection->id,
        ];
    }

    public function test_student_information_reports_require_staff_auth(): void
    {
        $this->get('/report/studentinformation')->assertRedirect();
        $this->get('/report/studentreport')->assertRedirect();
        $this->get('/report/classsectionreport')->assertRedirect();
        $this->get('/report/boys_girls_ratio')->assertRedirect();
        $this->get('/report/student_teacher_ratio')->assertRedirect();
        $this->get('/report/guardianreport')->assertRedirect();
        $this->get('/report/admissionreport')->assertRedirect();
        $this->get('/report/logindetailreport')->assertRedirect();
        $this->get('/report/parentlogindetailreport')->assertRedirect();
    }

    public function test_superadmin_can_open_hub_and_student_report(): void
    {
        $ctx = $this->seedStudent();

        $this->get('/report/studentinformation')
            ->assertOk()
            ->assertSee('Student Information Report', false)
            ->assertSee('report/studentreport', false)
            ->assertSee('report/classsectionreport', false)
            ->assertSee('report/boys_girls_ratio', false);

        $this->get('/report/studentreport')
            ->assertOk()
            ->assertSee('Select Criteria', false)
            ->assertSee($ctx['class']->class, false);

        $this->post('/report/studentreportvalidation', [
            'search_type' => 'search_filter',
        ])->assertOk()->assertJsonPath('status', 0)->assertSee('The Class field is required.', false);

        $ok = $this->post('/report/studentreportvalidation', [
            'search_type' => 'search_filter',
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
        ])->assertOk();
        $this->assertSame(1, (int) $ok->json('status'));
        $this->assertEquals($ctx['class']->id, (int) $ok->json('params.class_id'));

        $this->post('/report/studentreport', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
        ])->assertOk()->assertSee($ctx['student']->admission_no, false)->assertSee('Ratio Pupil', false);

        $json = $this->post('/report/dtstudentreportlist', [
            'draw' => 1,
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
        ])->assertOk()->json();
        $this->assertSame(1, (int) $json['draw']);
        $this->assertGreaterThan(0, (int) $json['recordsTotal']);
        $admissions = array_column($json['data'], 1);
        $this->assertContains($ctx['student']->admission_no, $admissions);
        $matched = collect($json['data'])->first(fn ($row) => $row[1] === $ctx['student']->admission_no);
        $this->assertNotNull($matched);
        $this->assertStringContainsString('student/view/'.$ctx['student']->id, $matched[2]);
    }

    public function test_class_section_and_ratio_reports_count_active_students(): void
    {
        $ctx = $this->seedStudent();
        $label = $ctx['class']->class.' ('.$ctx['section']->section.')';

        $page = $this->get('/report/classsectionreport')
            ->assertOk()
            ->assertSee($label, false);
        $this->assertMatchesRegularExpression(
            '/'.preg_quote($label, '/').'<\/td>\s*<td>\s*1\s*<\/td>/',
            $page->getContent()
        );

        $this->get('/report/boys_girls_ratio')
            ->assertOk()
            ->assertSee('Student Gender Ratio Report', false)
            ->assertSee($label, false)
            ->assertSee('1:0', false);

        $this->get('/report/student_teacher_ratio')
            ->assertOk()
            ->assertSee('Student Teacher Ratio Report', false)
            ->assertSee($label, false)
            ->assertSee('1:0', false);

        $ratio = app(StudentInformationReportService::class);
        $this->assertSame('1:0.5', $ratio->getRatio(10, 5));
        $this->assertSame('0:5', $ratio->getRatio(0, 5));
        $this->assertSame('1:0', $ratio->getRatio(4, 0));
    }

    public function test_guardian_history_and_login_credential_reports(): void
    {
        $ctx = $this->seedStudent();
        $student = $ctx['student'];
        $classId = $ctx['class']->id;
        $sectionId = $ctx['section']->id;
        $admissionNo = $student->admission_no;
        $studentUsername = 'std'.$student->id;
        $parentUsername = 'parent'.$student->id;

        $this->get('/report/guardianreport')
            ->assertOk()
            ->assertSee('Guardian Report', false);

        $this->post('/report/guardianreport', [])
            ->assertSessionHasErrors(['class_id', 'section_id']);

        $this->post('/report/guardianreport', [
            'class_id' => $classId,
            'section_id' => $sectionId,
        ])->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('Dad', false)
            ->assertSee($ctx['class']->class, false);

        $this->get('/report/admissionreport')
            ->assertOk()
            ->assertSee('Student History', false);

        $this->post('/report/admissionsearchvalidation', [])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertSee('The Class field is required.', false);

        $historyOk = $this->post('/report/admissionsearchvalidation', [
            'class_id' => $classId,
            'year' => '',
        ])->assertOk();
        $this->assertSame(1, (int) $historyOk->json('status'));
        $this->assertEquals($classId, (int) $historyOk->json('params.class_id'));

        $this->post('/report/admissionreport', [
            'class_id' => $classId,
        ])->assertOk()->assertSee($admissionNo, false);

        $historyJson = $this->post('/report/dtadmissionreportlist', [
            'draw' => 1,
            'class_id' => $classId,
        ])->assertOk()->json();
        $this->assertGreaterThan(0, (int) $historyJson['recordsTotal']);
        $historyRow = collect($historyJson['data'])->first(fn ($row) => $row[0] === $admissionNo);
        $this->assertNotNull($historyRow);
        $this->assertStringContainsString('student/view/'.$student->id, $historyRow[1]);
        $this->assertStringContainsString('  -  ', $historyRow[4]);

        $this->get('/report/logindetailreport')
            ->assertOk()
            ->assertSee('Student Login Credential Report', false);

        $this->post('/report/searchloginvalidation', [])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertSee('The Class field is required.', false)
            ->assertSee('The Section field is required.', false);

        $loginOk = $this->post('/report/searchloginvalidation', [
            'class_id' => $classId,
            'section_id' => $sectionId,
        ])->assertOk();
        $this->assertSame(1, (int) $loginOk->json('status'));

        $studentLogin = $this->post('/report/dtcredentialreportlist', [
            'draw' => 1,
            'class_id' => $classId,
            'section_id' => $sectionId,
        ])->assertOk()->json();
        $studentRow = collect($studentLogin['data'])->first(
            fn ($row) => str_contains((string) $row[0], $admissionNo)
        );
        $this->assertNotNull($studentRow);
        $this->assertSame($studentUsername, $studentRow[2]);
        $this->assertNotSame('', (string) $studentRow[3]);

        $this->get('/report/parentlogindetailreport')
            ->assertOk()
            ->assertSee('Parent Login Credential Report', false);

        $parentLogin = $this->post('/report/dtparentcredentialreportlist', [
            'draw' => 1,
            'class_id' => $classId,
            'section_id' => $sectionId,
        ])->assertOk()->json();
        $parentRow = collect($parentLogin['data'])->first(fn ($row) => $row[0] === $admissionNo);
        $this->assertNotNull($parentRow);
        $this->assertSame($parentUsername, $parentRow[2]);
        $this->assertNotSame('', (string) $parentRow[3]);
    }
}
