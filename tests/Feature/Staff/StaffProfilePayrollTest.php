<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffProfilePayrollTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupPayslipIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupPayslipIds !== []) {
            DB::table('payslip_allowance')->whereIn('payslip_id', $this->cleanupPayslipIds)->delete();
            DB::table('staff_payslip')->whereIn('id', $this->cleanupPayslipIds)->delete();
        }
        $this->cleanupPayslipIds = [];

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

        $token = uniqid('spp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SPP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Payroll',
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
    }

    private function createTeacherStaff(): Staff
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $token = uniqid('tpp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TPP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Teacher',
            'surname' => 'Payroll',
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
            'role_id' => $teacherRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    public function test_staff_profile_shows_payroll_summary_and_paid_payslip_link(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff();

        $paidId = (int) DB::table('staff_payslip')->insertGetId([
            'staff_id' => $target->id,
            'basic' => 5000,
            'total_allowance' => 500,
            'total_deduction' => 200,
            'leave_deduction' => 0,
            'tax' => '100',
            'net_salary' => 5200,
            'status' => 'paid',
            'month' => 'January',
            'year' => date('Y'),
            'payment_mode' => 'cash',
            'payment_date' => date('Y-m-d'),
            'remark' => 'January payroll',
            'generated_by' => null,
        ]);
        $this->cleanupPayslipIds[] = $paidId;

        $generatedId = (int) DB::table('staff_payslip')->insertGetId([
            'staff_id' => $target->id,
            'basic' => 5000,
            'total_allowance' => 0,
            'total_deduction' => 0,
            'leave_deduction' => 0,
            'tax' => '0',
            'net_salary' => 5000,
            'status' => 'generated',
            'month' => 'February',
            'year' => date('Y'),
            'payment_mode' => '',
            'payment_date' => date('Y-m-d'),
            'remark' => '',
            'generated_by' => null,
        ]);
        $this->cleanupPayslipIds[] = $generatedId;

        $this->get('/admin/staff/profile/'.$target->id)
            ->assertOk()
            ->assertSee(__('system.payroll'), false)
            ->assertSee(__('system.total_net_salary_paid'), false)
            ->assertSee('5200.00', false)
            ->assertSee('January payroll', false)
            ->assertSee(route('payroll.payslip_view', $paidId), false)
            ->assertDontSee(route('payroll.payslip_view', $generatedId), false);
    }
}
