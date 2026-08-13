<?php

namespace Tests\Feature\Leave;

use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Models\StudentApplyLeave;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaveDeferredSlicesTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<int> */
    private array $cleanupDetailIds = [];

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
        if ($this->cleanupStudentLeaveIds !== []) {
            DB::table('student_applyleave')->whereIn('id', $this->cleanupStudentLeaveIds)->delete();
        }
        $this->cleanupStudentLeaveIds = [];

        if ($this->cleanupRequestIds !== []) {
            DB::table('staff_leave_request')->whereIn('id', $this->cleanupRequestIds)->delete();
        }
        $this->cleanupRequestIds = [];

        if ($this->cleanupDetailIds !== []) {
            DB::table('staff_leave_details')->whereIn('id', $this->cleanupDetailIds)->delete();
        }
        $this->cleanupDetailIds = [];

        if ($this->cleanupTypeIds !== []) {
            DB::table('leave_types')->whereIn('id', $this->cleanupTypeIds)->delete();
        }
        $this->cleanupTypeIds = [];

        if ($this->cleanupStudentIds !== []) {
            DB::table('student_session')->whereIn('student_id', $this->cleanupStudentIds)->delete();
            DB::table('students')->whereIn('id', $this->cleanupStudentIds)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupClassIds !== []) {
            DB::table('class_sections')->whereIn('class_id', $this->cleanupClassIds)->delete();
            DB::table('classes')->whereIn('id', $this->cleanupClassIds)->delete();
        }
        $this->cleanupClassIds = [];

        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
        }
        $this->cleanupSectionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('lvd', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LVD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Leave',
            'surname' => 'Deferred',
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
            'date_of_joining' => date('Y-m-d'),
        ]);
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');

        return $staffId;
    }

    private function currentSessionId(): int
    {
        $id = (int) (DB::table('sch_settings')->value('session_id') ?? 0);
        $this->assertGreaterThan(0, $id);

        return $id;
    }

    public function test_staff_self_apply_and_my_leave_report(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $sessionId = $this->currentSessionId();
        $suffix = uniqid();

        $typeId = (int) DB::table('leave_types')->insertGetId([
            'type' => 'Self '.$suffix,
            'is_active' => 'yes',
        ]);
        $this->cleanupTypeIds[] = $typeId;

        $this->cleanupDetailIds[] = (int) DB::table('staff_leave_details')->insertGetId([
            'staff_id' => $staffId,
            'leave_type_id' => $typeId,
            'alloted_leave' => 5,
            'session_id' => $sessionId,
        ]);

        $this->get('/admin/staff/leaverequest')->assertOk()->assertSee('Leaves', false);
        $this->get('/admin/staff/leaverequest/apply')->assertOk()->assertSee('Apply Leave', false);

        $from = date('Y-m-d');
        $to = date('Y-m-d');

        $this->post('/admin/leaverequest/add_staff_leave', [
            'applieddate' => $from,
            'leave_from_date' => $from,
            'leave_to_date' => $to,
            'leave_type' => $typeId,
            'reason' => 'Personal',
        ])->assertRedirect('/admin/staff/leaverequest');

        $req = StaffLeaveRequest::query()
            ->where('staff_id', $staffId)
            ->where('leave_type_id', $typeId)
            ->firstOrFail();
        $this->cleanupRequestIds[] = (int) $req->id;
        $this->assertSame('pending', $req->status);

        $this->post('/report/myleaverequestreport', [
            'from_date' => $from,
            'to_date' => $to,
            'search' => 'search_filter',
        ])->assertOk()->assertSee('My Leave Request Report', false)->assertSee('Self '.$suffix, false);

        $this->post('/report/leaverequestreport', [
            'staff_name' => $staffId,
            'leave_status' => 'pending',
            'search' => 'search_filter',
        ])->assertOk()->assertSee('Leave Request Report', false)->assertSee('Self '.$suffix, false);

        $this->get('/migration-status/leave')
            ->assertOk()
            ->assertJsonPath('status', 'done')
            ->assertJsonPath('slices.student_approve_leave', 'done');
    }

    public function test_student_approve_leave_create_and_list(): void
    {
        $this->actingAsSuperAdmin();
        $sessionId = $this->currentSessionId();
        $suffix = uniqid();

        $classId = (int) DB::table('classes')->insertGetId(['class' => 'LV Class '.$suffix]);
        $this->cleanupClassIds[] = $classId;
        $sectionId = (int) DB::table('sections')->insertGetId(['section' => 'A'.$suffix]);
        $this->cleanupSectionIds[] = $sectionId;
        DB::table('class_sections')->insert([
            'class_id' => $classId,
            'section_id' => $sectionId,
        ]);

        $admissionNo = 'LVADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Stu',
            'lastname' => 'Leave',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $classId,
            'section_id' => $sectionId,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
            'mobileno' => '03004445566',
        ])->assertRedirect();

        $studentId = (int) DB::table('students')->where('admission_no', $admissionNo)->value('id');
        $this->assertGreaterThan(0, $studentId);
        $this->cleanupStudentIds[] = $studentId;

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $this->get('/admin/approve_leave')->assertOk()->assertSee('Approve Leave', false);

        $this->post('/admin/approve_leave/add', [
            'class' => $classId,
            'section' => $sectionId,
            'student' => $studentSessionId,
            'apply_date' => date('Y-m-d'),
            'from_date' => date('Y-m-d'),
            'to_date' => date('Y-m-d', strtotime('+1 day')),
            'leave_status' => 0,
            'message' => 'Sick',
        ])->assertRedirect();

        $leave = StudentApplyLeave::query()
            ->where('student_session_id', $studentSessionId)
            ->firstOrFail();
        $this->cleanupStudentLeaveIds[] = (int) $leave->id;
        $this->assertSame(0, (int) $leave->status);

        $this->post('/admin/approve_leave', [
            'class_id' => $classId,
            'section_id' => $sectionId,
            'search' => 'search_filter',
        ])->assertOk()->assertSee('Stu', false)->assertSee('Leave', false);
    }
}
