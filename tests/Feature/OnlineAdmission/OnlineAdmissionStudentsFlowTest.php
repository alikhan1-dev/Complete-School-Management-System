<?php

namespace Tests\Feature\OnlineAdmission;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineAdmissionStudentsFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

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

        if ($this->cleanupIds !== []) {
            DB::table('online_admissions')->whereIn('id', $this->cleanupIds)->delete();
        }
        $this->cleanupIds = [];

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

        $token = uniqid('oas', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OAS-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Online',
            'surname' => 'Staff',
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

    private function insertApplication(): array
    {
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('ref', false);
        $id = DB::table('online_admissions')->insertGetId([
            'admission_no' => '',
            'roll_no' => '',
            'reference_no' => $token,
            'firstname' => 'Ayaan '.$token,
            'middlename' => '',
            'lastname' => 'Khan',
            'rte' => 'No',
            'image' => '',
            'mobileno' => '03001112233',
            'email' => $token.'@example.test',
            'state' => '',
            'city' => '',
            'pincode' => '',
            'religion' => '',
            'cast' => '',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'current_address' => '',
            'permanent_address' => '',
            'class_section_id' => $section->id,
            'route_id' => 0,
            'blood_group' => '',
            'vehroute_id' => 0,
            'adhar_no' => '',
            'samagra_id' => '',
            'guardian_is' => '',
            'father_name' => 'Father',
            'father_phone' => '',
            'father_occupation' => '',
            'mother_name' => '',
            'mother_phone' => '',
            'mother_occupation' => '',
            'guardian_name' => 'Guardian',
            'guardian_relation' => '',
            'guardian_phone' => '',
            'guardian_occupation' => '',
            'guardian_address' => '',
            'guardian_email' => '',
            'father_pic' => '',
            'mother_pic' => '',
            'guardian_pic' => '',
            'is_enroll' => 0,
            'previous_school' => '',
            'height' => '',
            'weight' => '',
            'note' => '',
            'form_status' => 1,
            'paid_status' => 0,
            'document' => '',
        ]);
        $this->cleanupIds[] = $id;

        return ['id' => $id, 'token' => $token, 'section' => $section];
    }

    public function test_list_requires_staff_auth(): void
    {
        $this->get('/admin/onlinestudent')->assertRedirect();
    }

    public function test_edit_requires_core_fields(): void
    {
        $this->actingAsSuperAdmin();
        $app = $this->insertApplication();
        $this->post('/admin/onlinestudent/edit/'.$app['id'], [
            'student_id' => (string) $app['id'],
            'save' => 'save',
        ])->assertOk()
            ->assertSee('The First Name field is required.', false)
            ->assertSee('The Date Of Birth field is required.', false)
            ->assertSee('The Class field is required.', false)
            ->assertSee('The Section field is required.', false)
            ->assertSee('The Gender field is required.', false);
    }

    public function test_superadmin_can_list_edit_and_delete_application(): void
    {
        $this->actingAsSuperAdmin();
        $app = $this->insertApplication();

        $this->get('/admin/onlinestudent')
            ->assertOk()
            ->assertSee('Student List', false)
            ->assertSee($app['token'], false)
            ->assertSee('Ayaan '.$app['token'], false);

        $this->get('/admin/onlinestudent/edit/'.$app['id'])
            ->assertOk()
            ->assertSee('Edit Student', false)
            ->assertSee('Ayaan '.$app['token'], false);

        $this->post('/admin/onlinestudent/edit/'.$app['id'], [
            'student_id' => (string) $app['id'],
            'save' => 'save',
            'firstname' => 'Ayaan Edited',
            'lastname' => 'Khan',
            'dob' => '2012-05-01',
            'class_id' => (string) $app['section']->class_id,
            'section_id' => (string) $app['section']->id,
            'gender' => 'Male',
            'email' => $app['token'].'@example.test',
            'father_name' => 'Father Edited',
        ])->assertRedirect('/admin/onlinestudent');

        $updated = DB::table('online_admissions')->where('id', $app['id'])->first();
        $this->assertSame('Ayaan Edited', $updated->firstname);
        $this->assertSame('Father Edited', $updated->father_name);
        $this->assertSame(0, (int) $updated->is_enroll);

        $this->post('/admin/onlinestudent/checkpaymentstatus', ['id' => $app['id']])
            ->assertOk();

        $this->post('/admin/onlinestudent/getByClass', ['class_id' => $app['section']->class_id])
            ->assertOk()
            ->assertJsonFragment(['id' => $app['section']->id]);

        $this->get('/admin/onlinestudent/delete/'.$app['id'])->assertRedirect('/admin/onlinestudent');
        $this->assertNull(DB::table('online_admissions')->where('id', $app['id'])->first());
        $this->cleanupIds = [];
    }

    public function test_save_and_enroll_creates_student_session_and_portal_users(): void
    {
        $this->actingAsSuperAdmin();
        $app = $this->insertApplication();
        $auto = (int) DB::table('sch_settings')->orderBy('id')->value('adm_auto_insert');
        $admissionNo = $auto === 1 ? '' : 'OA-'.uniqid();

        $this->get('/admin/onlinestudent/edit/'.$app['id'])
            ->assertOk()
            ->assertSee('Save and Enroll', false);

        $this->post('/admin/onlinestudent/edit/'.$app['id'], [
            'student_id' => (string) $app['id'],
            'save' => 'enroll',
            'firstname' => 'Enrolled Ayaan',
            'lastname' => 'Khan',
            'dob' => '2012-05-01',
            'class_id' => (string) $app['section']->class_id,
            'section_id' => (string) $app['section']->id,
            'gender' => 'Male',
            'email' => 'enroll-'.$app['token'].'@example.test',
            'admission_no' => $admissionNo,
            'father_name' => 'Father',
            'guardian_name' => 'Guardian',
        ])->assertRedirect('/admin/onlinestudent');

        $online = DB::table('online_admissions')->where('id', $app['id'])->first();
        $this->assertSame(1, (int) $online->is_enroll);
        $this->assertSame('Enrolled Ayaan', $online->firstname);
        $this->assertNotSame('', (string) $online->admission_no);

        $student = DB::table('students')->where('email', 'enroll-'.$app['token'].'@example.test')->first();
        $this->assertNotNull($student);
        $this->createdStudentIds[] = (int) $student->id;
        $this->assertSame('Enrolled Ayaan', $student->firstname);
        $this->assertSame((string) $online->admission_no, (string) $student->admission_no);

        $session = DB::table('student_session')->where('student_id', $student->id)->first();
        $this->assertNotNull($session);
        $this->assertSame((int) $app['section']->class_id, (int) $session->class_id);
        $this->assertSame((int) $app['section']->section_id, (int) $session->section_id);

        $this->assertNotNull(DB::table('users')->where('role', 'student')->where('user_id', $student->id)->first());
        $this->assertNotNull(DB::table('users')->where('id', $student->parent_id)->where('role', 'parent')->first());
    }
}
