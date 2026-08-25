<?php

namespace Tests\Feature\Payroll;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollSuperadminVisibleTest extends TestCase
{
    private ?string $savedRestriction = null;

    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupPayslipIds = [];

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

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

        return Staff::query()->findOrFail($staffId);
    }

    private function createPaidPayslip(int $staffId, string $month, string $year, string $paymentDate): void
    {
        $this->cleanupPayslipIds[] = (int) DB::table('staff_payslip')->insertGetId([
            'staff_id' => $staffId,
            'basic' => 50000,
            'total_allowance' => 0,
            'total_deduction' => 0,
            'leave_deduction' => 0,
            'tax' => '0',
            'net_salary' => 50000,
            'status' => 'paid',
            'month' => $month,
            'year' => $year,
            'payment_mode' => 'cash',
            'payment_date' => $paymentDate,
            'remark' => '',
            'generated_by' => null,
        ]);
    }

    public function test_payroll_search_and_reports_exclude_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $this->assertSame(7, $superadminRoleId, 'CI parity expects superadmin role id 7.');

        $teacherRoleId = (int) (DB::table('roles')->where('id', '!=', 7)->orderBy('id')->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->setSuperadminRestriction('disabled');

        $month = 'March';
        $year = date('Y');
        $paymentDate = date('Y-m-d');

        $hiddenSuperadmin = $this->createStaff($superadminRoleId, 'hidden');
        $visibleStaff = $this->createStaff($teacherRoleId, 'visible');
        $this->createPaidPayslip($hiddenSuperadmin->id, $month, $year, $paymentDate);
        $this->createPaidPayslip($visibleStaff->id, $month, $year, $paymentDate);

        $viewer = $this->createStaff($teacherRoleId, 'viewer');
        $this->actingAs($viewer, 'staff');

        $search = $this->post('/admin/payroll', [
            'role' => '',
            'month' => $month,
            'year' => $year,
            'search' => 'search',
        ])->assertOk();
        $search->assertSee('Visible', false);
        $search->assertDontSee((string) $hiddenSuperadmin->employee_id, false);
        $search->assertDontSee('Hidden', false);

        $report = $this->post('/admin/payroll/payrollreport', [
            'role' => 'select',
            'month' => $month,
            'year' => $year,
            'search' => 'search_filter',
        ])->assertOk();
        $report->assertSee('Visible', false);
        $report->assertDontSee((string) $hiddenSuperadmin->employee_id, false);

        $financeReport = $this->post('/financereports/payroll', [
            'search' => 'search_filter',
            'search_type' => 'today',
        ])->assertOk();
        $financeReport->assertSee('Visible', false);
        $financeReport->assertDontSee((string) $hiddenSuperadmin->employee_id, false);
    }

    public function test_payroll_search_shows_superadmin_staff_to_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        if ($superadminRoleId !== 7) {
            $this->markTestSkipped('CI parity expects superadmin role id 7.');
        }

        $this->setSuperadminRestriction('disabled');

        $month = 'April';
        $year = date('Y');
        $hiddenSuperadmin = $this->createStaff($superadminRoleId, 'shown');
        $this->createPaidPayslip($hiddenSuperadmin->id, $month, $year, date('Y-m-d'));

        $viewer = $this->createStaff($superadminRoleId, 'saadmin');
        $this->actingAs($viewer, 'staff');

        $this->post('/admin/payroll', [
            'role' => '',
            'month' => $month,
            'year' => $year,
            'search' => 'search',
        ])->assertOk()->assertSee('Shown', false);

        $this->get('/migration-status/payroll')
            ->assertOk()
            ->assertJsonPath('slices.payroll_superadmin_visible', 'done');
    }
}
