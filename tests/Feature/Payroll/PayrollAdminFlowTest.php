<?php

namespace Tests\Feature\Payroll;

use App\Modules\Payroll\Models\PayslipAllowance;
use App\Modules\Payroll\Models\StaffPayslip;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollAdminFlowTest extends TestCase
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

        $token = uniqid('pay', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PAY-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Payroll',
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
            'basic_salary' => 50000,
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

    private function createTargetStaff(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_active', 'yes')->where('name', '!=', 'Super Admin')->value('id')
            ?: DB::table('roles')->where('is_active', 'yes')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('emp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EMP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Target',
            'surname' => 'Employee',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '03001234567',
            'emergency_contact_no' => '',
            'email' => $token.'@example.test',
            'dob' => '1992-01-01',
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
            'basic_salary' => 40000,
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

    public function test_search_generate_pay_view_and_report_flow(): void
    {
        $this->actingAsSuperAdmin();
        $staffId = $this->createTargetStaff();
        $month = 'January';
        $year = date('Y');

        $this->get('/admin/payroll')->assertOk()->assertSee('Select Criteria', false);

        $this->post('/admin/payroll', [
            'role' => '',
            'month' => $month,
            'year' => $year,
            'search' => 'search',
        ])->assertOk()->assertSee('Staff List', false)->assertSee('Target', false);

        $this->get('/admin/payroll/create/'.$month.'/'.$year.'/'.$staffId)
            ->assertOk()
            ->assertSee('Generate Payroll', false)
            ->assertSee('Payroll Summary', false);

        $this->post('/admin/payroll/payslip', [
            'staff_id' => $staffId,
            'month' => $month,
            'year' => $year,
            'status' => 'generated',
            'basic' => 40000,
            'total_allowance' => 1000,
            'total_deduction' => 500,
            'tax' => 200,
            'net_salary' => 40300,
            'allowance_type' => ['Bonus'],
            'allowance_amount' => ['1000'],
            'deduction_type' => ['PF'],
            'deduction_amount' => ['500'],
        ])->assertRedirect('/admin/payroll');

        $payslip = StaffPayslip::query()
            ->where('staff_id', $staffId)
            ->where('month', $month)
            ->where('year', $year)
            ->firstOrFail();
        $this->cleanupPayslipIds[] = (int) $payslip->id;

        $this->assertSame('generated', $payslip->status);
        $this->assertEquals(40000.0, (float) $payslip->basic);
        $this->assertEquals(1, PayslipAllowance::query()->where('payslip_id', $payslip->id)->where('cal_type', 'positive')->count());
        $this->assertEquals(1, PayslipAllowance::query()->where('payslip_id', $payslip->id)->where('cal_type', 'negative')->count());

        $this->get('/admin/payroll/edit/'.$payslip->id)->assertOk()->assertSee('Edit Payroll', false);

        $this->get('/admin/payroll/pay/'.$staffId.'/'.$month.'/'.$year)
            ->assertOk()
            ->assertSee('Proceed to Pay', false);

        $this->post('/admin/payroll/paymentSuccess', [
            'payment_mode' => 'cash',
            'payment_date' => date('Y-m-d'),
            'paymentid' => $payslip->id,
            'remarks' => 'Paid in cash',
        ])->assertRedirect('/admin/payroll');

        $payslip->refresh();
        $this->assertSame('paid', $payslip->status);
        $this->assertSame('cash', $payslip->payment_mode);

        $this->get('/admin/payroll/view/'.$payslip->id)
            ->assertOk()
            ->assertSee('Payslip for the period of', false)
            ->assertSee('Bonus', false);

        $this->post('/admin/payroll/payrollreport', [
            'role' => 'select',
            'month' => $month,
            'year' => $year,
            'search' => 'search_filter',
        ])->assertOk()->assertSee('Payroll Report', false)->assertSee('Target', false);

        $this->get('/migration-status/payroll')
            ->assertOk()
            ->assertJsonPath('status', 'done');
    }

    public function test_delete_generated_payslip(): void
    {
        $this->actingAsSuperAdmin();
        $staffId = $this->createTargetStaff();
        $month = 'February';
        $year = date('Y');

        $payslipId = (int) DB::table('staff_payslip')->insertGetId([
            'staff_id' => $staffId,
            'basic' => 1000,
            'total_allowance' => 0,
            'total_deduction' => 0,
            'leave_deduction' => 0,
            'tax' => '0',
            'net_salary' => 1000,
            'status' => 'generated',
            'month' => $month,
            'year' => $year,
            'payment_mode' => '',
            'payment_date' => date('Y-m-d'),
            'remark' => '',
            'generated_by' => null,
        ]);
        $this->cleanupPayslipIds[] = $payslipId;

        $this->get('/admin/payroll/deletepayroll/'.$payslipId.'/'.$month.'/'.$year)
            ->assertRedirect();

        $this->assertNull(StaffPayslip::query()->find($payslipId));
        $this->cleanupPayslipIds = array_values(array_filter(
            $this->cleanupPayslipIds,
            fn ($id) => $id !== $payslipId
        ));
    }
}
