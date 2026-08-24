<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\CustomField;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\AlumniEvent;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlumniParityExtrasTest extends TestCase
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
    private array $cleanupCustomFieldIds = [];

    /** @var list<int> */
    private array $cleanupClassTeacherIds = [];

    /** @var list<int> */
    private array $cleanupEventIds = [];

    /** @var list<int> */
    private array $cleanupRolePermissionIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupEventIds !== []) {
            DB::table('alumni_events')->whereIn('id', $this->cleanupEventIds)->delete();
            $this->cleanupEventIds = [];
        }
        if ($this->cleanupRolePermissionIds !== []) {
            DB::table('roles_permissions')->whereIn('id', $this->cleanupRolePermissionIds)->delete();
            $this->cleanupRolePermissionIds = [];
        }
        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
            $this->cleanupClassTeacherIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('custom_field_values')->where('belong_table_id', $studentId)->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];
        foreach ($this->cleanupCustomFieldIds as $fieldId) {
            DB::table('custom_field_values')->where('custom_field_id', $fieldId)->delete();
            DB::table('custom_fields')->where('id', $fieldId)->delete();
        }
        $this->cleanupCustomFieldIds = [];
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

        DB::table('sch_settings')->limit(1)->update(['class_teacher' => $this->previousClassTeacherSetting]);
        app(SchoolContext::class)->clearCache();

        parent::tearDown();
    }

    private function insertStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Alumni',
            'surname' => 'Scope',
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

        return Staff::query()->findOrFail($staffId);
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);
        $this->actingAs($this->insertStaff($roleId, 'alsa'), 'staff');
    }

    /**
     * @return array{session:AcademicSession,classA:SchoolClass,sectionA:Section,classB:SchoolClass,sectionB:Section}
     */
    private function seedClasses(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2094-al']);
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();

        $sectionA = Section::query()->create(['section' => 'ALA-'.uniqid(), 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'ALA-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'is_active' => 'yes',
        ]);
        $sectionB = Section::query()->create(['section' => 'ALB-'.uniqid(), 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'ALB-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $classB->id,
            'section_id' => $sectionB->id,
            'is_active' => 'yes',
        ]);
        $this->cleanupSectionIds[] = $sectionA->id;
        $this->cleanupSectionIds[] = $sectionB->id;
        $this->cleanupClassIds[] = $classA->id;
        $this->cleanupClassIds[] = $classB->id;

        return compact('session', 'classA', 'sectionA', 'classB', 'sectionB');
    }

    private function createAlumniStudent(array $fixtures, SchoolClass $class, Section $section, string $admissionNo): Student
    {
        $this->actingAsSuperAdmin();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Alumni',
            'lastname' => 'Kid',
            'gender' => 'Male',
            'dob' => '2005-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        DB::table('student_session')
            ->where('student_id', $student->id)
            ->update(['is_alumni' => 1]);

        return $student;
    }

    public function test_alumni_list_shows_table_custom_fields_and_details_tab(): void
    {
        $fixtures = $this->seedClasses();
        $suffix = uniqid();
        $fieldName = 'Alumni CF '.$suffix;
        $field = CustomField::query()->create([
            'name' => $fieldName,
            'belong_to' => 'students',
            'type' => 'input',
            'bs_column' => 6,
            'validation' => 0,
            'field_values' => '',
            'visible_on_table' => 1,
            'weight' => 1,
            'is_active' => 1,
        ]);
        $this->cleanupCustomFieldIds[] = $field->id;

        $this->actingAsSuperAdmin();
        $admissionNo = 'ALCF'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Alumni',
            'lastname' => 'CF',
            'gender' => 'Male',
            'dob' => '2005-01-01',
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
            'custom_fields' => [
                'students' => [
                    $field->id => 'AlumniVal-'.$suffix,
                ],
            ],
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;
        DB::table('student_session')->where('student_id', $student->id)->update(['is_alumni' => 1]);

        $response = $this->post('/admin/alumni/alumnilist', [
            'search' => 'search_filter',
            'session_id' => $fixtures['session']->id,
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])->assertOk();

        $response->assertSee($fieldName, false);
        $response->assertSee('AlumniVal-'.$suffix, false);
        $response->assertSee(__('system.details_view'), false);
        $response->assertSee($admissionNo, false);
    }

    public function test_class_teacher_alumni_admission_search_is_scoped(): void
    {
        $fixtures = $this->seedClasses();
        $suffix = uniqid();
        $inScope = $this->createAlumniStudent(
            $fixtures,
            $fixtures['classA'],
            $fixtures['sectionA'],
            'ALIN'.$suffix
        );
        $outOfScope = $this->createAlumniStudent(
            $fixtures,
            $fixtures['classB'],
            $fixtures['sectionB'],
            'ALOUT'.$suffix
        );

        $roleId = 2;
        $permCatId = (int) DB::table('permission_category')->where('short_code', 'manage_alumni')->value('id');
        $this->assertGreaterThan(0, $permCatId);
        $existingPerm = DB::table('roles_permissions')
            ->where('role_id', $roleId)
            ->where('perm_cat_id', $permCatId)
            ->first();
        if ($existingPerm) {
            DB::table('roles_permissions')->where('id', $existingPerm->id)->update(['can_view' => 1]);
        } else {
            $this->cleanupRolePermissionIds[] = DB::table('roles_permissions')->insertGetId([
                'role_id' => $roleId,
                'perm_cat_id' => $permCatId,
                'can_view' => 1,
                'can_add' => 0,
                'can_edit' => 0,
                'can_delete' => 0,
            ]);
        }

        $staff = $this->insertStaff($roleId, 'alt');
        $ctId = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $staff->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->cleanupClassTeacherIds[] = $ctId;
        $this->actingAs($staff, 'staff');

        $this->post('/admin/alumni/alumnilist', [
            'search' => 'search_full',
            'search_text' => $suffix,
        ])->assertOk()
            ->assertSee($inScope->admission_no, false)
            ->assertDontSee($outOfScope->admission_no, false);
    }

    public function test_alumni_getevent_calendar_json(): void
    {
        $this->actingAsSuperAdmin();
        $event = AlumniEvent::query()->create([
            'title' => 'Calendar Event '.uniqid(),
            'event_for' => 'all',
            'session_id' => null,
            'class_id' => null,
            'section' => '[]',
            'from_date' => '2026-08-10 00:00:00',
            'to_date' => '2026-08-12 23:59:00',
            'note' => 'note',
            'photo' => '',
            'is_active' => 0,
            'event_notification_message' => '',
            'show_onwebsite' => 0,
        ]);
        $this->cleanupEventIds[] = $event->id;

        $this->get('/admin/alumni/events')->assertOk()->assertSee('calendar_event', false);

        $this->getJson('/admin/alumni/getevent?start=2026-08-01&end=2026-08-31')
            ->assertOk()
            ->assertJsonFragment([
                'id' => (int) $event->id,
                'title' => $event->title,
                'backgroundColor' => '#27ab00',
            ]);
    }
}
