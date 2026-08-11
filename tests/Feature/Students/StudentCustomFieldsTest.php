<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\CustomField;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentCustomFieldsTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdStudentIds = [];

    /** @var list<int> */
    private array $createdCustomFieldIds = [];

    /** @var list<\Illuminate\Database\Eloquent\Model> */
    private array $createdClassSections = [];

    protected function tearDown(): void
    {
        foreach ($this->createdStudentIds as $studentId) {
            DB::table('custom_field_values')->where('belong_table_id', $studentId)->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->createdStudentIds = [];

        foreach ($this->createdCustomFieldIds as $fieldId) {
            DB::table('custom_field_values')->where('custom_field_id', $fieldId)->delete();
            DB::table('custom_fields')->where('id', $fieldId)->delete();
        }
        $this->createdCustomFieldIds = [];

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

        $token = uniqid('scf', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SCF-'.$token,
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

    private function createRequiredStudentField(string $name): CustomField
    {
        $field = CustomField::query()->create([
            'name' => $name,
            'belong_to' => 'students',
            'type' => 'input',
            'bs_column' => 6,
            'validation' => 1,
            'field_values' => '',
            'visible_on_table' => 0,
            'weight' => 0,
            'is_active' => 1,
        ]);
        $this->createdCustomFieldIds[] = $field->id;

        return $field;
    }

    public function test_student_admission_requires_and_persists_custom_fields(): void
    {
        $this->actingAsSuperAdmin();
        ['class' => $class, 'section' => $section] = $this->ensureSessionAndClassSection();

        $fieldName = 'Blood note '.uniqid();
        $field = $this->createRequiredStudentField($fieldName);

        $this->get('/student/create')
            ->assertOk()
            ->assertSee('Custom Fields', false)
            ->assertSee($fieldName, false);

        $admissionNo = 'ADM'.uniqid();
        $basePayload = [
            'admission_no' => $admissionNo,
            'firstname' => 'Custom',
            'lastname' => 'FieldKid',
            'gender' => 'Male',
            'dob' => '2012-01-15',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03001234567',
        ];

        $this->post('/student/create', $basePayload)
            ->assertSessionHasErrors('custom_fields.students.'.$field->id);

        $this->post('/student/create', array_merge($basePayload, [
            'custom_fields' => [
                'students' => [
                    $field->id => 'A+',
                ],
            ],
        ]))->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->first();
        $this->assertNotNull($student);
        $this->createdStudentIds[] = $student->id;

        $this->assertDatabaseHas('custom_field_values', [
            'belong_table_id' => $student->id,
            'custom_field_id' => $field->id,
            'field_value' => 'A+',
        ]);

        $this->get('/student/view/'.$student->id)
            ->assertOk()
            ->assertSee($fieldName, false)
            ->assertSee('A+', false);

        $this->get('/student/edit/'.$student->id)
            ->assertOk()
            ->assertSee($fieldName, false)
            ->assertSee('A+', false);

        $this->post('/student/edit/'.$student->id, array_merge($basePayload, [
            'firstname' => 'Custom',
            'custom_fields' => [
                'students' => [
                    $field->id => 'B+',
                ],
            ],
        ]))->assertRedirect(route('students.view', $student->id));

        $this->assertDatabaseHas('custom_field_values', [
            'belong_table_id' => $student->id,
            'custom_field_id' => $field->id,
            'field_value' => 'B+',
        ]);
        $this->assertSame(
            1,
            (int) DB::table('custom_field_values')
                ->where('belong_table_id', $student->id)
                ->where('custom_field_id', $field->id)
                ->count()
        );
    }
}
