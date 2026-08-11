<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use App\Modules\Students\Services\StudentAdmissionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StdTransferTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdStudentIds = [];

    /** @var list<int> */
    private array $createdClassIds = [];

    /** @var list<int> */
    private array $createdSectionIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdStudentIds as $studentId) {
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        foreach ($this->createdClassIds as $classId) {
            ClassSection::query()->where('class_id', $classId)->delete();
            SchoolClass::query()->where('id', $classId)->delete();
        }
        foreach ($this->createdSectionIds as $sectionId) {
            Section::query()->where('id', $sectionId)->delete();
        }
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('tr', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TR-'.$token,
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

    public function test_promote_pass_creates_next_session_row(): void
    {
        $this->actingAsSuperAdmin();

        $current = AcademicSession::query()->first() ?? AcademicSession::query()->create(['session' => '2090-91']);
        $next = AcademicSession::query()->create(['session' => '2091-92-'.uniqid()]);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $current->id]);

        $section = Section::query()->create(['section' => 'TS-'.uniqid(), 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'TC-'.uniqid(), 'is_active' => 'yes']);
        $nextClass = SchoolClass::query()->create(['class' => 'TN-'.uniqid(), 'is_active' => 'yes']);
        $this->createdSectionIds[] = $section->id;
        $this->createdClassIds[] = $class->id;
        $this->createdClassIds[] = $nextClass->id;

        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $nextClass->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admit = app(StudentAdmissionService::class)->admit([
            'admission_no' => 'TP'.uniqid(),
            'firstname' => 'Promo',
            'gender' => 'Male',
            'dob' => '2012-05-01',
            'guardian_is' => 'father',
            'guardian_name' => 'G',
            'guardian_phone' => '03001112222',
            'blood_group' => '',
        ], $class->id, $section->id);

        $studentId = $admit['student_id'];
        $this->createdStudentIds[] = $studentId;

        $this->get('/admin/stdtransfer/index')->assertOk()->assertSee('Select Criteria', false);

        $this->post('/admin/stdtransfer/index', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'session_id' => $next->id,
            'class_promote_id' => $nextClass->id,
            'section_promote_id' => $section->id,
        ])->assertOk()->assertSee('Promo', false);

        $this->postJson('/admin/stdtransfer/promote', [
            'session_id' => $next->id,
            'class_promote_id' => $nextClass->id,
            'section_promote_id' => $section->id,
            'class_post' => $class->id,
            'section_post' => $section->id,
            'student_list' => [$studentId],
            'result_'.$studentId => 'pass',
            'next_working_'.$studentId => 'countinue',
        ])->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('student_session', [
            'student_id' => $studentId,
            'session_id' => $next->id,
            'class_id' => $nextClass->id,
            'section_id' => $section->id,
        ]);

        $next->delete();
    }
}
