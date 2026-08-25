<?php

namespace Tests\Feature\Leave;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Leave\Models\StudentApplyLeave;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaveSuperadminVisibleTest extends TestCase
{
    private ?string $savedRestriction = null;

    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupRequestIds = [];

    /** @var list<int> */
    private array $cleanupStudentLeaveIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

        if ($this->cleanupRequestIds !== []) {
            DB::table('staff_leave_request')->whereIn('id', $this->cleanupRequestIds)->delete();
        }
        $this->cleanupRequestIds = [];

        if ($this->cleanupStudentLeaveIds !== []) {
            DB::table('student_applyleave')->whereIn('id', $this->cleanupStudentLeaveIds)->delete();
        }
        $this->cleanupStudentLeaveIds = [];

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

        foreach ($this->cleanupSectionIds as $sectionId) {
            DB::table('sections')->where('id', $sectionId)->delete();
        }
        $this->cleanupSectionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function setSuperadminRestriction(string $value): void
    {
        if ($this->savedRestriction === null) {
            $this->savedRestriction = (string) DB::table('sch_settings')->value('superadmin_restriction');
        }
        DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $value]);
        app(SchoolContext::class)->clearCache();
    }

    private function createStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => ucfirst($prefix),
            'surname' => 'LeaveStaff',
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

    private function currentSessionId(): int
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2099-lvsuper']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        return (int) $session->id;
    }

    private function createStaffLeaveRequest(int $staffId, int $sessionId, string $reason): void
    {
        $typeId = (int) (DB::table('leave_types')->where('is_active', 'yes')->value('id')
            ?: DB::table('leave_types')->insertGetId(['type' => 'Casual LV', 'is_active' => 'yes']));

        $this->cleanupRequestIds[] = (int) DB::table('staff_leave_request')->insertGetId([
            'staff_id' => $staffId,
            'date' => date('Y-m-d'),
            'leave_days' => 1,
            'leave_type_id' => $typeId,
            'leave_from' => date('Y-m-d'),
            'leave_to' => date('Y-m-d'),
            'employee_remark' => $reason,
            'status' => 'pending',
            'admin_remark' => '',
            'applied_by' => $staffId,
            'document_file' => '',
            'approve_date' => null,
            'session_id' => $sessionId,
            'half_day_leave' => null,
        ]);
    }

    /**
     * @return array{studentName:string,admissionNo:string}
     */
    private function seedStudentLeaveApprovedBy(int $approverStaffId, Staff $actor): array
    {
        $sessionId = $this->currentSessionId();
        $suffix = uniqid();
        $section = Section::query()->create(['section' => 'LVSA-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'LVCA-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'LVADM'.$suffix;
        $this->actingAs($actor, 'staff');
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Leave',
            'lastname' => 'Student'.$suffix,
            'gender' => 'Male',
            'dob' => '2010-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $leave = StudentApplyLeave::query()->create([
            'student_session_id' => $studentSessionId,
            'from_date' => date('Y-m-d'),
            'to_date' => date('Y-m-d'),
            'apply_date' => date('Y-m-d'),
            'reason' => 'Family event '.$suffix,
            'status' => 1,
            'approve_by' => $approverStaffId,
            'docs' => '',
            'request_type' => 0,
        ]);
        $this->cleanupStudentLeaveIds[] = (int) $leave->id;

        return [
            'studentName' => 'Leave Student'.$suffix,
            'admissionNo' => $admissionNo,
        ];
    }

    public function test_staff_leave_request_list_excludes_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $this->assertSame(7, $superadminRoleId);

        $teacherRoleId = (int) (DB::table('roles')->where('id', '!=', 7)->orderBy('id')->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->setSuperadminRestriction('disabled');
        $sessionId = $this->currentSessionId();

        $hiddenSuperadmin = $this->createStaff($superadminRoleId, 'hidden');
        $visibleStaff = $this->createStaff($teacherRoleId, 'visible');
        $this->createStaffLeaveRequest($hiddenSuperadmin->id, $sessionId, 'Hidden leave');
        $this->createStaffLeaveRequest($visibleStaff->id, $sessionId, 'Visible leave');

        $viewer = $this->createStaff($teacherRoleId, 'viewer');
        $this->actingAs($viewer, 'staff');

        $page = $this->get('/admin/leaverequest/leaverequest')->assertOk();
        $page->assertSee('Visible', false);
        $page->assertDontSee((string) $hiddenSuperadmin->employee_id, false);
        $page->assertDontSee('Hidden leave', false);

        $reportPage = $this->get('/report/leaverequestreport')->assertOk();
        $reportPage->assertSee((string) $visibleStaff->employee_id, false);
        $reportPage->assertDontSee((string) $hiddenSuperadmin->employee_id, false);
    }

    public function test_student_approve_leave_hides_rows_approved_by_superadmin_for_non_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $teacherRoleId = (int) (DB::table('roles')->where('id', '!=', 7)->orderBy('id')->value('id'));

        $this->setSuperadminRestriction('disabled');

        $superadminApprover = $this->createStaff($superadminRoleId, 'approver');
        $payload = $this->seedStudentLeaveApprovedBy($superadminApprover->id, $superadminApprover);

        $viewer = $this->createStaff($teacherRoleId, 'viewer');
        $this->actingAs($viewer, 'staff');

        $this->get('/admin/approve_leave')
            ->assertOk()
            ->assertDontSee($payload['studentName'], false);
    }

    public function test_staff_leave_list_shows_superadmin_staff_to_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        if ($superadminRoleId !== 7) {
            $this->markTestSkipped('CI parity expects superadmin role id 7.');
        }

        $this->setSuperadminRestriction('disabled');
        $sessionId = $this->currentSessionId();

        $hiddenSuperadmin = $this->createStaff($superadminRoleId, 'shown');
        $this->createStaffLeaveRequest($hiddenSuperadmin->id, $sessionId, 'Shown leave');

        $viewer = $this->createStaff($superadminRoleId, 'saadmin');
        $this->actingAs($viewer, 'staff');

        $this->get('/admin/leaverequest/leaverequest')
            ->assertOk()
            ->assertSee((string) $hiddenSuperadmin->employee_id, false);

        $this->get('/migration-status/leave')
            ->assertOk()
            ->assertJsonPath('slices.leave_superadmin_visible', 'done');
    }
}
