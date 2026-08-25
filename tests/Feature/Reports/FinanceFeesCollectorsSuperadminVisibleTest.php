<?php

namespace Tests\Feature\Reports;

use App\Modules\Reports\Services\FinanceReportService;
use App\Modules\Reports\Services\HumanResourceReportService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceFeesCollectorsSuperadminVisibleTest extends TestCase
{
    private ?string $savedRestriction = null;

    /** @var list<int> */
    private array $createdStaffIds = [];

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

        if ($this->createdStaffIds !== []) {
            DB::table('staff_roles')->whereIn('staff_id', $this->createdStaffIds)->delete();
            DB::table('staff')->whereIn('id', $this->createdStaffIds)->delete();
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
            'surname' => 'Collector',
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

        return Staff::query()->findOrFail($staffId);
    }

    public function test_fees_collectors_exclude_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');

        $superStaff = $this->createStaff($superRoleId, 'hidden');
        $visibleStaff = $this->createStaff($teacherRoleId, 'visible');
        $viewer = $this->createStaff($teacherRoleId, 'viewer');
        $this->actingAs($viewer, 'staff');

        $collectors = app(FinanceReportService::class)->feesCollectors();

        $this->assertArrayHasKey($visibleStaff->id, $collectors);
        $this->assertArrayNotHasKey($superStaff->id, $collectors);
    }

    public function test_fees_collectors_include_superadmin_staff_for_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        if ($superRoleId !== 7) {
            $this->markTestSkipped('CI parity expects superadmin role id 7.');
        }

        $superStaff = $this->createStaff($superRoleId, 'shown');
        $viewer = $this->createStaff($superRoleId, 'saView');
        $this->actingAs($viewer, 'staff');

        $collectors = app(FinanceReportService::class)->feesCollectors();

        $this->assertArrayHasKey($superStaff->id, $collectors);
    }

    public function test_hr_report_roles_exclude_superadmin_role_for_non_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');

        $viewer = $this->createStaff($teacherRoleId, 'hrView');
        $this->actingAs($viewer, 'staff');

        $roleIds = app(HumanResourceReportService::class)
            ->roles()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertNotContains($superRoleId, $roleIds);
        $this->assertContains($teacherRoleId, $roleIds);
    }
}
