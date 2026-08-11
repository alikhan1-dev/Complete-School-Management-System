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

class StudentsCoreTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdStudentIds = [];

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
        $this->createdStudentIds = [];

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

        $token = uniqid('sa', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STU-'.$token,
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

        return compact('session', 'class', 'section');
    }

    public function test_guest_is_redirected_from_student_search(): void
    {
        $this->get('/student/search')->assertRedirect('/site/login');
    }

    public function test_student_create_view_disable_delete_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        ['class' => $class, 'section' => $section] = $this->ensureSessionAndClassSection();

        $this->get('/student/search')->assertOk()->assertSee('Student List', false);
        $this->get('/student/create')->assertOk()->assertSee('Student Admission', false);

        $admissionNo = 'ADM'.uniqid();
        $response = $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Ali',
            'lastname' => 'Test',
            'gender' => 'Male',
            'dob' => '2012-01-15',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03001234567',
        ]);

        $student = Student::query()->where('admission_no', $admissionNo)->first();
        $this->assertNotNull($student);
        $this->createdStudentIds[] = $student->id;

        $response->assertRedirect(route('students.view', $student->id));

        $this->assertDatabaseHas('student_session', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
        ]);
        $this->assertDatabaseHas('users', [
            'user_id' => $student->id,
            'role' => 'student',
            'username' => 'std'.$student->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $student->parent_id,
            'role' => 'parent',
            'username' => 'parent'.$student->id,
        ]);

        $this->get('/student/view/'.$student->id)->assertOk()->assertSee('Ali', false);

        $this->get('/student/disablestudent/'.$student->id)
            ->assertRedirect(route('students.view', $student->id));
        $this->assertSame('no', Student::query()->find($student->id)->is_active);

        $this->post('/student/dtstudentlist', [
            'srch_type' => 'search_filter',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'draw' => 1,
        ])->assertOk()->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $this->get('/student/delete/'.$student->id)->assertRedirect(route('students.search'));
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->createdStudentIds = array_values(array_filter(
            $this->createdStudentIds,
            fn ($id) => (int) $id !== (int) $student->id
        ));

        // Cleanup class/section created for the test
        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
