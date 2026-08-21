<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DisabledStudentListFlowTest extends TestCase
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
    private array $cleanupReasonIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];
        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];
        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
            $this->cleanupSectionIds = [];
        }
        if ($this->cleanupReasonIds !== []) {
            DB::table('disable_reason')->whereIn('id', $this->cleanupReasonIds)->delete();
            $this->cleanupReasonIds = [];
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

        $token = uniqid('dislist', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'DISL-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Disabled',
            'surname' => 'List',
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

    public function test_guest_cannot_open_disabled_list(): void
    {
        $this->get('/student/disablestudentslist')->assertRedirect();
    }

    public function test_disabled_students_filter_and_keyword_search(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-dis']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'DIS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'DLC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $reasonId = DB::table('disable_reason')->insertGetId([
            'reason' => 'Moved away '.$suffix,
        ]);
        $this->cleanupReasonIds[] = $reasonId;

        $admissionNo = 'DIS'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Disabled',
            'lastname' => 'Kid',
            'gender' => 'Male',
            'dob' => '2010-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
            'father_name' => 'Father '.$suffix,
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $this->post('/student/disable_reason', [
            'student_id' => $student->id,
            'reason' => $reasonId,
            'disable_date' => '2026-01-15',
            'note' => 'Family relocated',
        ])->assertOk();

        $student->refresh();
        $this->assertSame('no', $student->is_active);

        $this->get('/student/disablestudentslist')
            ->assertOk()
            ->assertSee(__('system.disabled_students'), false);

        $this->post('/student/disablestudentslist', [
            'search' => 'search_filter',
            'class_id' => '',
        ])->assertOk()
            ->assertSee('The '.__('system.class').' field is required.', false);

        $this->post('/student/disablestudentslist', [
            'search' => 'search_filter',
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('Moved away '.$suffix, false)
            ->assertSee('Disabled', false);

        // CI keyword search ignores the term and returns all inactive in session.
        $this->post('/student/disablestudentslist', [
            'search' => 'search_full',
            'search_text' => 'zzz-no-match-'.$suffix,
        ])->assertOk()
            ->assertSee($admissionNo, false);
    }
}
