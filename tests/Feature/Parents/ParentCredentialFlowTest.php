<?php

namespace Tests\Feature\Parents;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentCredentialFlowTest extends TestCase
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
    private array $cleanupUserIds = [];

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
            $this->cleanupUserIds = [];
        }
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

        $token = uniqid('parcred', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PAR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Parent',
            'surname' => 'Cred',
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

    public function test_guest_cannot_fetch_login_details(): void
    {
        $this->post('/student/getlogindetail', ['student_id' => 1])->assertRedirect();
    }

    public function test_parent_and_student_credentials_on_profile(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-par']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'PCS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'PCC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'PAR'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Child',
            'lastname' => 'One',
            'gender' => 'Male',
            'dob' => '2012-03-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Guardian '.$suffix,
            'guardian_phone' => '03001112233',
            'guardian_email' => 'guard'.$suffix.'@example.test',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentUser = DB::table('users')->where('role', 'student')->where('user_id', $student->id)->first();
        $this->assertNotNull($studentUser);
        $parentUser = DB::table('users')->where('role', 'parent')->where('id', $student->parent_id)->first();
        $this->assertNotNull($parentUser);
        $this->cleanupUserIds[] = (int) $parentUser->id;

        $this->get('/student/view/'.$student->id)
            ->assertOk()
            ->assertSee((string) $studentUser->username, false)
            ->assertSee((string) $parentUser->username, false)
            ->assertSee(__('system.login_details'), false)
            ->assertSee(__('system.send_parent_password'), false);

        $detail = $this->postJson('/student/getlogindetail', ['student_id' => $student->id]);
        $detail->assertOk();
        $payload = $detail->json();
        $this->assertIsArray($payload);
        $usernames = collect($payload)->pluck('username')->all();
        $this->assertContains($studentUser->username, $usernames);
        $this->assertContains($parentUser->username, $usernames);

        $this->postJson('/student/sendpassword', [
            'student_id' => $student->id,
            'username' => $studentUser->username,
            'password' => $studentUser->password,
            'contact_no' => '03001112233',
            'email' => 'child'.$suffix.'@example.test',
            'admission_no' => $admissionNo,
            'student_session_id' => 0,
        ])->assertOk()->assertJson(['status' => 1]);

        $this->postJson('/student/send_parent_password', [
            'student_id' => $student->id,
            'username' => $parentUser->username,
            'password' => $parentUser->password,
            'contact_no' => '03001112233',
            'email' => 'guard'.$suffix.'@example.test',
            'admission_no' => $admissionNo,
            'student_session_id' => 0,
        ])->assertOk()->assertJson(['status' => 1]);
    }
}
