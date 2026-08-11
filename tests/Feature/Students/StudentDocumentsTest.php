<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StudentDocumentsTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdStudentIds = [];

    /** @var list<array{class:\Illuminate\Database\Eloquent\Model,section:\Illuminate\Database\Eloquent\Model}> */
    private array $createdClassSections = [];

    protected function tearDown(): void
    {
        foreach ($this->createdStudentIds as $studentId) {
            DB::table('student_doc')->where('student_id', $studentId)->delete();
            $dir = public_path('uploads/student_documents/'.$studentId);
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->createdStudentIds = [];

        foreach ($this->createdClassSections as $pair) {
            ClassSection::query()->where('class_id', $pair['class']->id)->delete();
            $pair['class']->delete();
            $pair['section']->delete();
        }
        $this->createdClassSections = [];

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

        $token = uniqid('sdoc', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SDOC-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
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

    private function admitStudent(): Student
    {
        $session = AcademicSession::query()->first();
        if (! $session) {
            $session = AcademicSession::query()->create(['session' => '2098-99']);
        }
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'upload_documents' => 1,
        ]);

        $section = Section::query()->create(['section' => 'S-'.uniqid(), 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);
        $this->createdClassSections[] = compact('class', 'section');

        $admissionNo = 'ADM'.uniqid();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Doc',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-15',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03001234567',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->createdStudentIds[] = $student->id;

        return $student;
    }

    public function test_student_document_upload_download_delete_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $student = $this->admitStudent();

        $this->get('/student/view/'.$student->id)
            ->assertOk()
            ->assertSee('Documents', false)
            ->assertSee('Upload Documents', false);

        $file = UploadedFile::fake()->create('report.pdf', 120, 'application/pdf');

        $this->post('/student/create_doc', [
            'student_id' => $student->id,
            'first_title' => 'Birth Certificate',
            'first_doc' => [$file],
        ])->assertRedirect(route('students.view', $student->id));

        $doc = DB::table('student_doc')->where('student_id', $student->id)->first();
        $this->assertNotNull($doc);
        $this->assertSame('Birth Certificate', $doc->title);
        $path = public_path('uploads/student_documents/'.$student->id.'/'.$doc->doc);
        $this->assertFileExists($path);

        $this->get('/student/view/'.$student->id)
            ->assertOk()
            ->assertSee('Birth Certificate', false)
            ->assertSee($doc->doc, false);

        $this->get('/student/download/'.$student->id.'/'.$doc->id)
            ->assertOk();

        $this->get('/student/doc_delete/'.$doc->id.'/'.$student->id)
            ->assertRedirect(route('students.view', $student->id));

        $this->assertDatabaseMissing('student_doc', ['id' => $doc->id]);
        $this->assertFileDoesNotExist($path);
    }
}
