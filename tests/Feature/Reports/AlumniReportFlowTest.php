<?php

namespace Tests\Feature\Reports;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlumniReportFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupAlumniIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupAlumniIds !== []) {
            DB::table('alumni_students')->whereIn('id', $this->cleanupAlumniIds)->delete();
            $this->cleanupAlumniIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('alumni_students')->where('student_id', $studentId)->delete();
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
            $this->cleanupSectionIds = [];
        }
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

        $token = uniqid('alumrpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ALR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Alumni',
            'surname' => 'Report',
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
    }

    public function test_guest_cannot_open_alumni_report(): void
    {
        $this->get('/report/alumnireport')->assertRedirect();
    }

    public function test_alumni_report_filter_requires_alumni_details_row(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-alumrpt']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'ARS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'ARC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'ALR'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Report',
            'lastname' => 'Alumni',
            'gender' => 'Male',
            'dob' => '2005-06-15',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03005556677',
            'current_address' => 'Old Street',
            'city' => 'Lahore',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->update(['is_alumni' => 1]);

        $this->get('/report/alumnireport')
            ->assertOk()
            ->assertSee(__('system.pass_out_session'), false);

        // Without alumni_students row, CI inner join excludes the student.
        $this->post('/report/alumnireport', [
            'search' => 'search_filter',
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()
            ->assertDontSee($admissionNo, false);

        $alumniId = DB::table('alumni_students')->insertGetId([
            'student_id' => $student->id,
            'current_email' => 'grad'.$suffix.'@example.test',
            'current_phone' => '03001234567',
            'occupation' => 'Engineer',
            'address' => 'New Colony',
            'photo' => '',
        ]);
        $this->cleanupAlumniIds[] = $alumniId;

        $this->post('/report/alumnireport', [
            'search' => 'search_filter',
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('grad'.$suffix.'@example.test', false)
            ->assertSee('03001234567', false)
            ->assertSee('Engineer', false)
            ->assertSee('New Colony', false);

        $this->post('/report/alumnireport', [
            'search' => 'search_filter',
            'session_id' => '',
            'class_id' => '',
        ])->assertSessionHasErrors(['session_id', 'class_id']);
    }
}
