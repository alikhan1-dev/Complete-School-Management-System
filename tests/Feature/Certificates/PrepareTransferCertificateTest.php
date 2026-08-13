<?php

namespace Tests\Feature\Certificates;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\CustomField;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrepareTransferCertificateTest extends TestCase
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

    /** @var list<int> */
    private array $cleanupCustomFieldIds = [];

    /** @var list<int> */
    private array $cleanupTcFieldIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupTcNoIds !== []) {
            DB::table('transfer_certificate_no')->whereIn('id', $this->cleanupTcNoIds)->delete();
        }
        $this->cleanupTcNoIds = [];

        if ($this->cleanupCustomFieldIds !== []) {
            DB::table('custom_field_values')->whereIn('custom_field_id', $this->cleanupCustomFieldIds)->delete();
            DB::table('custom_fields')->whereIn('id', $this->cleanupCustomFieldIds)->delete();
        }
        $this->cleanupCustomFieldIds = [];

        if ($this->cleanupTcFieldIds !== []) {
            DB::table('transfer_certificate_fields')->whereIn('id', $this->cleanupTcFieldIds)->delete();
        }
        $this->cleanupTcFieldIds = [];

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

        $token = uniqid('tcpr', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TCPR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'TcPr',
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

    public function test_prepare_tc_saves_custom_fields_and_prints_them(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-tcpr']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'PRS-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $class = SchoolClass::query()->create(['class' => 'PRC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'PRADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Prepare',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2010-08-01',
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

        $fieldName = 'tc_reason_'.$suffix;
        $customField = CustomField::query()->create([
            'name' => $fieldName,
            'belong_to' => 'transfer_certificate',
            'type' => 'input',
            'bs_column' => 12,
            'validation' => 0,
            'field_values' => '',
            'show_table' => 0,
            'visible_on_table' => 0,
            'weight' => 1,
            'is_active' => 1,
        ]);
        $this->cleanupCustomFieldIds[] = $customField->id;

        $tcFieldId = (int) DB::table('transfer_certificate_fields')->insertGetId([
            'name' => $fieldName,
            'lang_key' => $fieldName,
            'status' => 1,
            'position' => 99,
            'is_default' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupTcFieldIds[] = $tcFieldId;

        $this->get('/admin/transfercertificate/prepare_tc')
            ->assertOk()
            ->assertSee('Prepare Transfer Certificate', false);

        $this->post('/admin/transfercertificate/prepare_tc/search', [
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()->assertSee($admissionNo, false);

        $this->get('/admin/transfercertificate/edit_custom_field/'.$student->id)
            ->assertOk()
            ->assertSee('Fill Other Details', false)
            ->assertSee(ucfirst($fieldName), false);

        $fieldValue = 'Left for higher studies '.$suffix;
        $this->post('/admin/transfercertificate/save_custom_fields', [
            'student_id' => $student->id,
            'custom_fields' => [
                'transfer_certificate' => [
                    $customField->id => $fieldValue,
                ],
            ],
        ])->assertRedirect('/admin/transfercertificate/edit_custom_field/'.$student->id);

        $this->assertDatabaseHas('custom_field_values', [
            'belong_table_id' => $student->id,
            'custom_field_id' => $customField->id,
            'field_value' => $fieldValue,
        ]);

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
            ->where('student_session_id', $studentSessionId)
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($issued);
        $this->cleanupTcNoIds[] = (int) $issued->id;

        // Content check via HTML fallback (custom fields must appear on the sheet).
        $htmlBefore = (int) (DB::table('transfer_certificate_no')->max('id') ?? 0);
        $this->post('/admin/transfercertificate/print_transfer_certificate_html', [
            'student_id' => $student->id,
            'student_session_id' => $studentSessionId,
            'is_regenerte' => '0',
        ])
            ->assertOk()
            ->assertSee($fieldName, false)
            ->assertSee($fieldValue, false);

        $htmlIssued = DB::table('transfer_certificate_no')
            ->where('id', '>', $htmlBefore)
            ->orderByDesc('id')
            ->first();
        if ($htmlIssued) {
            $this->cleanupTcNoIds[] = (int) $htmlIssued->id;
        }
    }
}
