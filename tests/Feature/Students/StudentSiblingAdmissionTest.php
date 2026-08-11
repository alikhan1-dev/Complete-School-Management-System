<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentSiblingAdmissionTest extends TestCase
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
            DB::table('custom_field_values')->where('belong_table_id', $studentId)->delete();
            DB::table('student_doc')->where('student_id', $studentId)->delete();
            DB::table('student_timeline')->where('student_id', $studentId)->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                $stillLinked = DB::table('students')->where('parent_id', $parentId)->exists();
                if (! $stillLinked) {
                    DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
                }
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

        $token = uniqid('sib', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SIB-'.$token,
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

    private function ensureSessionAndClassSection(): array
    {
        $session = AcademicSession::query()->first();
        if (! $session) {
            $session = AcademicSession::query()->create(['session' => '2098-99']);
        }
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.uniqid(), 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);
        $this->createdClassSections[] = compact('class', 'section');

        return compact('session', 'class', 'section');
    }

    public function test_sibling_admission_reuses_parent_and_lists_siblings(): void
    {
        $this->actingAsSuperAdmin();
        ['class' => $class, 'section' => $section] = $this->ensureSessionAndClassSection();

        $this->get('/student/create')->assertOk()->assertSee('Add Sibling', false);

        $firstAdmission = 'ADM'.uniqid();
        $this->post('/student/create', [
            'admission_no' => $firstAdmission,
            'firstname' => 'Elder',
            'lastname' => 'Sibling',
            'gender' => 'Male',
            'dob' => '2010-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'father_name' => 'Parent Father',
            'guardian_is' => 'father',
            'guardian_name' => 'Parent Father',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $elder = Student::query()->where('admission_no', $firstAdmission)->firstOrFail();
        $this->createdStudentIds[] = $elder->id;
        $parentId = (int) $elder->parent_id;
        $this->assertGreaterThan(0, $parentId);

        $parentsBefore = (int) DB::table('users')->where('role', 'parent')->count();

        $secondAdmission = 'ADM'.uniqid();
        $this->post('/student/create', [
            'admission_no' => $secondAdmission,
            'firstname' => 'Younger',
            'lastname' => 'Sibling',
            'gender' => 'Female',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'sibling_id' => $elder->id,
            'sibling_name' => 'Elder Sibling',
            'father_name' => 'Parent Father',
            'guardian_is' => 'father',
            'guardian_name' => 'Parent Father',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $younger = Student::query()->where('admission_no', $secondAdmission)->firstOrFail();
        $this->createdStudentIds[] = $younger->id;

        $this->assertSame($parentId, (int) $younger->parent_id);
        $this->assertSame(
            $parentsBefore,
            (int) DB::table('users')->where('role', 'parent')->count()
        );
        $this->assertDatabaseMissing('users', [
            'role' => 'parent',
            'username' => 'parent'.$younger->id,
        ]);

        $this->get('/student/getStudentRecordByID?student_id='.$elder->id)
            ->assertOk()
            ->assertJsonFragment(['id' => $elder->id]);

        $this->get('/student/view/'.$younger->id)
            ->assertOk()
            ->assertSee('Siblings', false)
            ->assertSee('Elder', false);

        $this->get('/student/view/'.$elder->id)
            ->assertOk()
            ->assertSee('Younger', false);
    }
}
