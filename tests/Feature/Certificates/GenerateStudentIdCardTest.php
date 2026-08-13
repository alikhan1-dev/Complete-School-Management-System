<?php

namespace Tests\Feature\Certificates;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Certificates\Models\IdCard;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateStudentIdCardTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIdCardIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupStudentIds as $studentId) {
            File::delete(public_path('uploads/student_id_card/barcodes/'.$studentId.'.png'));
            File::delete(public_path('uploads/student_id_card/qrcode/'.$studentId.'.png'));
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupIdCardIds !== []) {
            DB::table('id_card')->whereIn('id', $this->cleanupIdCardIds)->delete();
        }
        $this->cleanupIdCardIds = [];

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

        $token = uniqid('genidc', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'GID-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'GenId',
            'surname' => 'Card',
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

    public function test_search_and_print_student_id_card(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-gid']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'GIS-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $class = SchoolClass::query()->create(['class' => 'GIC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'GIDADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Idcard',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000000',
            'father_name' => 'Father Name',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $idcard = IdCard::query()->create([
            'title' => 'Gen ID '.$suffix,
            'school_name' => 'Gen School '.$suffix,
            'school_address' => '1 Gen Street',
            'background' => '',
            'logo' => '',
            'sign_image' => '',
            'enable_vertical_card' => 0,
            'header_color' => '#334455',
            'enable_admission_no' => 1,
            'enable_student_name' => 1,
            'enable_class' => 1,
            'enable_fathers_name' => 1,
            'enable_mothers_name' => 0,
            'enable_address' => 0,
            'enable_phone' => 0,
            'enable_dob' => 1,
            'enable_blood_group' => 0,
            'enable_student_barcode' => 1,
            'enable_student_rollno' => 0,
            'enable_student_house_name' => 0,
            'status' => 1,
        ]);
        $this->cleanupIdCardIds[] = $idcard->id;

        $this->get('/admin/generateidcard')
            ->assertOk()
            ->assertSee('Generate Student ID Card', false);

        $this->post('/admin/generateidcard/search', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'id_card' => $idcard->id,
        ])->assertOk()->assertSee($admissionNo, false);

        $this->post('/admin/generateidcard/print', [
            'id_card' => $idcard->id,
            'student_ids' => [$student->id],
        ])
            ->assertOk()
            ->assertSee('Idcard Pupil', false)
            ->assertSee($admissionNo, false)
            ->assertSee('Gen School '.$suffix, false)
            ->assertSee($class->class, false);

        $scanType = (string) (DB::table('sch_settings')->value('scan_code_type') ?: 'barcode');
        $scanFolder = $scanType === 'qrcode' ? 'qrcode' : 'barcodes';
        $this->assertFileExists(public_path('uploads/student_id_card/'.$scanFolder.'/'.$student->id.'.png'));
    }
}
