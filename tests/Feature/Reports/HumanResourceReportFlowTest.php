<?php

namespace Tests\Feature\Reports;

use App\Modules\Leave\Models\LeaveType;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HumanResourceReportFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<int> */
    private array $cleanupDetailIds = [];

    /** @var list<int> */
    private array $cleanupDesignationIds = [];

    /** @var list<int> */
    private array $cleanupCustomFieldIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupDetailIds !== []) {
            DB::table('staff_leave_details')->whereIn('id', $this->cleanupDetailIds)->delete();
            $this->cleanupDetailIds = [];
        }

        if ($this->cleanupTypeIds !== []) {
            DB::table('leave_types')->whereIn('id', $this->cleanupTypeIds)->delete();
            $this->cleanupTypeIds = [];
        }

        if ($this->cleanupCustomFieldIds !== []) {
            DB::table('custom_field_values')->whereIn('custom_field_id', $this->cleanupCustomFieldIds)->delete();
            DB::table('custom_fields')->whereIn('id', $this->cleanupCustomFieldIds)->delete();
            $this->cleanupCustomFieldIds = [];
        }

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        if ($this->cleanupDesignationIds !== []) {
            DB::table('staff_designation')->whereIn('id', $this->cleanupDesignationIds)->delete();
            $this->cleanupDesignationIds = [];
        }

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('hrrpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HRRPT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'HrReport',
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
            'date_of_joining' => '2020-01-15',
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

    public function test_guest_cannot_open_hr_hub_or_staff_report(): void
    {
        $this->get(url('report/human_resource'))->assertRedirect();
        $this->get(url('report/staff_report'))->assertRedirect();
    }

    public function test_human_resource_hub_and_staff_report_filters(): void
    {
        $this->actingAsSuperAdmin();

        $suffix = uniqid();
        $designationId = DB::table('staff_designation')->insertGetId([
            'designation' => 'HR-DES-'.$suffix,
            'is_active' => 'yes',
        ]);
        $this->cleanupDesignationIds[] = $designationId;

        $leaveType = LeaveType::query()->create([
            'type' => 'HR-LV-'.$suffix,
            'is_active' => 'yes',
        ]);
        $this->cleanupTypeIds[] = $leaveType->id;

        $roleId = (int) DB::table('roles')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('staffrow', true);
        $targetStaffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EMP-'.$suffix,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => 'B.Ed',
            'work_exp' => '2y',
            'name' => 'Report',
            'surname' => 'Target',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'contact_no' => '111',
            'emergency_contact_no' => '222',
            'email' => $token.'@example.test',
            'dob' => '1988-05-01',
            'marital_status' => 'Single',
            'local_address' => 'Local',
            'permanent_address' => 'Permanent',
            'note' => 'Note',
            'image' => '',
            'password' => bcrypt('secret'),
            'gender' => 'Female',
            'account_title' => 'Salary',
            'bank_account_no' => '123456',
            'bank_name' => 'Bank',
            'ifsc_code' => 'IFSC',
            'bank_branch' => 'Branch',
            'payscale' => '',
            'basic_salary' => 5000,
            'epf_no' => 'EPF1',
            'contract_type' => 'Permanent',
            'shift' => 'Day',
            'location' => 'Campus',
            'facebook' => 'https://facebook.example/x',
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
            'designation' => $designationId,
            'date_of_joining' => '2021-06-10',
        ]);
        $this->createdStaffIds[] = $targetStaffId;
        DB::table('staff_roles')->insert([
            'staff_id' => $targetStaffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);

        $detailId = DB::table('staff_leave_details')->insertGetId([
            'staff_id' => $targetStaffId,
            'leave_type_id' => $leaveType->id,
            'alloted_leave' => '12',
            'session_id' => (int) (DB::table('sch_settings')->value('session_id') ?: 1),
        ]);
        $this->cleanupDetailIds[] = $detailId;

        $fieldId = DB::table('custom_fields')->insertGetId([
            'name' => 'HR Field '.$suffix,
            'belong_to' => 'staff',
            'type' => 'input',
            'bs_column' => 6,
            'validation' => 0,
            'field_values' => '',
            'visible_on_table' => 1,
            'weight' => 1,
            'is_active' => 1,
        ]);
        $this->cleanupCustomFieldIds[] = $fieldId;
        DB::table('custom_field_values')->insert([
            'belong_table_id' => $targetStaffId,
            'custom_field_id' => $fieldId,
            'field_value' => 'CustomVal-'.$suffix,
        ]);

        $this->get(url('report/human_resource'))
            ->assertOk()
            ->assertSee(__('system.staff_report'), false)
            ->assertSee('/report/staff_report', false);

        $getPage = $this->get('/report/staff_report');
        $getPage->assertOk()
            ->assertSee('EMP-'.$suffix, false)
            ->assertSee('Report Target', false)
            ->assertSee('HR Field '.$suffix, false)
            ->assertSee('CustomVal-'.$suffix, false);
        $this->assertStringContainsString('HR-LV-'.$suffix, $getPage->getContent());
        $this->assertStringContainsString('12', $getPage->getContent());

        $this->post('/report/staff_report', [
            'search_type' => '',
            'staff_status' => '1',
            'role' => (string) $roleId,
            'designation' => (string) $designationId,
        ])->assertOk()
            ->assertSee('EMP-'.$suffix, false)
            ->assertSee('5000.00', false);

        $this->post('/report/staff_report', [
            'search_type' => 'period',
            'date_from' => '2021-06-01',
            'date_to' => '2021-06-30',
            'staff_status' => '1',
            'role' => '',
            'designation' => '',
        ])->assertOk()
            ->assertSee('EMP-'.$suffix, false);

        $this->post('/report/staff_report', [
            'search_type' => 'period',
            'date_from' => '2010-01-01',
            'date_to' => '2010-01-31',
            'staff_status' => '1',
            'role' => '',
            'designation' => '',
        ])->assertOk()
            ->assertDontSee('EMP-'.$suffix, false);
    }
}
