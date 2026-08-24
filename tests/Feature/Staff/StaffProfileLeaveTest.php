<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffProfileLeaveTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<int> */
    private array $cleanupDetailIds = [];

    /** @var list<int> */
    private array $cleanupRequestIds = [];

    protected function tearDown(): void
    {
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

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function currentSessionId(): int
    {
        $sessionId = (int) DB::table('sch_settings')->value('session_id');
        $this->assertGreaterThan(0, $sessionId);

        return $sessionId;
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('spl', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SPL-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Leave',
            'surname' => 'Viewer',
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

        return $staffId;
    }

    private function createTargetStaff(int $roleId): int
    {
        $token = uniqid('spt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SPT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Target',
            'surname' => 'Leave',
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

        return $staffId;
    }

    public function test_staff_profile_shows_leave_summary_and_requests(): void
    {
        $adminId = $this->actingAsSuperAdmin();
        $roleId = (int) DB::table('staff_roles')->where('staff_id', $adminId)->value('role_id');
        $targetId = $this->createTargetStaff($roleId);
        $sessionId = $this->currentSessionId();
        $suffix = uniqid();

        $typeId = (int) DB::table('leave_types')->insertGetId([
            'type' => 'Annual '.$suffix,
            'is_active' => 'yes',
        ]);
        $this->cleanupTypeIds[] = $typeId;

        $this->cleanupDetailIds[] = (int) DB::table('staff_leave_details')->insertGetId([
            'staff_id' => $targetId,
            'leave_type_id' => $typeId,
            'alloted_leave' => 10,
            'session_id' => $sessionId,
        ]);

        $from = date('Y-m-d');
        $to = date('Y-m-d', strtotime('+1 day'));

        $this->cleanupRequestIds[] = (int) DB::table('staff_leave_request')->insertGetId([
            'staff_id' => $targetId,
            'date' => $from,
            'leave_days' => 2,
            'leave_type_id' => $typeId,
            'leave_from' => $from,
            'leave_to' => $to,
            'employee_remark' => 'Trip',
            'status' => 'pending',
            'admin_remark' => '',
            'applied_by' => $adminId,
            'document_file' => '',
            'approve_date' => null,
            'session_id' => $sessionId,
            'half_day_leave' => null,
        ]);

        $this->get('/admin/staff/profile/'.$targetId)
            ->assertOk()
            ->assertSee('Annual '.$suffix, false)
            ->assertSee('10', false)
            ->assertSee('Pending', false)
            ->assertSee('2', false);
    }
}
