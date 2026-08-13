<?php

namespace Tests\Feature\Certificates;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DownloadTransferCertificateTest extends TestCase
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
    private array $cleanupTcNoIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupTcNoIds !== []) {
            DB::table('transfer_certificate_no')->whereIn('id', $this->cleanupTcNoIds)->delete();
        }
        $this->cleanupTcNoIds = [];

        foreach ($this->cleanupStudentIds as $studentId) {
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

        $token = uniqid('tcdl', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TCDL-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'TcDl',
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

    public function test_search_and_print_transfer_certificate_issues_tc_no(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-tcdl']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'TCS-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $class = SchoolClass::query()->create(['class' => 'TCC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'TCADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Transfer',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-05-15',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000000',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $this->get('/admin/transfercertificate/download')
            ->assertOk()
            ->assertSee('Download Transfer Certificate', false)
            ->assertSee('Select Criteria', false);

        $this->post('/admin/transfercertificate/download/search', [
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()->assertSee($admissionNo, false)->assertSee('Transfer', false);

        $beforeMaxId = (int) (DB::table('transfer_certificate_no')->max('id') ?? 0);

        $pdfResponse = $this->post('/admin/transfercertificate/print_transfer_certificate', [
            'student_id' => $student->id,
            'student_session_id' => $studentSessionId,
            'is_regenerte' => '0',
        ]);

        $pdfResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', (string) $pdfResponse->getContent());

        $issued = DB::table('transfer_certificate_no')
            ->where('id', '>', $beforeMaxId)
            ->where('student_session_id', $studentSessionId)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($issued);
        $this->cleanupTcNoIds[] = (int) $issued->id;
        $this->assertSame(0, (int) $issued->is_regenerte);
        $this->assertGreaterThan(0, (int) $issued->tc_no);

        $reissueResponse = $this->post('/admin/transfercertificate/print_transfer_certificate', [
            'student_id' => $student->id,
            'student_session_id' => $studentSessionId,
            'is_regenerte' => '1',
        ]);
        $reissueResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $reissueResponse->getContent());

        $reissue = DB::table('transfer_certificate_no')
            ->where('id', '>', (int) $issued->id)
            ->where('student_session_id', $studentSessionId)
            ->where('is_regenerte', 1)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($reissue);
        $this->cleanupTcNoIds[] = (int) $reissue->id;
        $this->assertGreaterThan((int) $issued->tc_no, (int) $reissue->tc_no);

        // HTML fallback still available (issues another serial — intentional same builder).
        $htmlBefore = (int) (DB::table('transfer_certificate_no')->max('id') ?? 0);
        $this->post('/admin/transfercertificate/print_transfer_certificate_html', [
            'student_id' => $student->id,
            'student_session_id' => $studentSessionId,
            'is_regenerte' => '0',
        ])
            ->assertOk()
            ->assertSee('Transfer Certificate', false)
            ->assertSee('Transfer Pupil', false);

        $htmlIssued = DB::table('transfer_certificate_no')
            ->where('id', '>', $htmlBefore)
            ->where('student_session_id', $studentSessionId)
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($htmlIssued);
        $this->cleanupTcNoIds[] = (int) $htmlIssued->id;
    }
}
