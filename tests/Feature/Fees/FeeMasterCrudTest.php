<?php

namespace Tests\Feature\Fees;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeMasterCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdFeeTypeIds = [];

    /** @var list<int> */
    private array $createdFeeGroupIds = [];

    /** @var list<int> */
    private array $createdSessionGroupIds = [];

    /** @var list<int> */
    private array $createdRowIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdRowIds as $id) {
            DB::table('fee_groups_feetype')->where('id', $id)->delete();
        }
        foreach ($this->createdSessionGroupIds as $id) {
            DB::table('fee_groups_feetype')->where('fee_session_group_id', $id)->delete();
            DB::table('fee_session_groups')->where('id', $id)->delete();
        }
        foreach ($this->createdFeeTypeIds as $id) {
            DB::table('feetype')->where('id', $id)->delete();
        }
        foreach ($this->createdFeeGroupIds as $id) {
            DB::table('fee_groups')->where('id', $id)->delete();
        }
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }

        $this->createdRowIds = [];
        $this->createdSessionGroupIds = [];
        $this->createdFeeTypeIds = [];
        $this->createdFeeGroupIds = [];
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('fm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
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

    private function seedTypeAndGroup(string $suffix): array
    {
        $session = AcademicSession::query()->first();
        if (! $session) {
            $session = AcademicSession::query()->create(['session' => '2099-00']);
        }
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $type = FeeType::query()->create([
            'type' => 'T-'.$suffix,
            'code' => 'C-'.$suffix,
            'description' => '',
            'is_system' => 0,
            'nature' => '',
            'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'G-'.$suffix,
            'description' => '',
            'is_system' => 0,
            'nature' => '',
            'is_active' => 'no',
        ]);
        $this->createdFeeTypeIds[] = $type->id;
        $this->createdFeeGroupIds[] = $group->id;

        return compact('session', 'type', 'group');
    }

    public function test_fee_master_create_edit_delete_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        ['type' => $type, 'group' => $group, 'session' => $session] = $this->seedTypeAndGroup($suffix);

        $this->get('/admin/feemaster')->assertOk()->assertSee('Fees Master List', false);

        $this->post('/admin/feemaster', [
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'amount' => '1500.50',
            'account_type' => 'fix',
            'due_date' => '2026-12-31',
            'fine_amount' => '50',
            'fine_per_day' => '1',
        ])->assertRedirect(route('fees.fee_masters.index'));

        $sessionGroup = FeeSessionGroup::query()
            ->where('fee_groups_id', $group->id)
            ->where('session_id', $session->id)
            ->firstOrFail();
        $this->createdSessionGroupIds[] = $sessionGroup->id;

        $row = FeeGroupFeetype::query()
            ->where('fee_session_group_id', $sessionGroup->id)
            ->where('feetype_id', $type->id)
            ->firstOrFail();
        $this->createdRowIds[] = $row->id;

        $this->assertSame('fix', $row->fine_type);
        $this->assertSame(1, (int) $row->fine_per_day);
        $this->assertEquals(1500.50, (float) $row->amount);

        $this->post('/admin/feemaster', [
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'amount' => '100',
            'account_type' => 'none',
        ])->assertSessionHasErrors('fee_groups_id');

        $this->post('/admin/feemaster/edit/'.$row->id, [
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'amount' => '1600',
            'account_type' => 'percentage',
            'due_date' => '2026-11-30',
            'fine_percentage' => '5',
            'fine_amount' => '25',
        ])->assertRedirect(route('fees.fee_masters.index'));

        $row->refresh();
        $this->assertSame('percentage', $row->fine_type);
        $this->assertEquals(1600.0, (float) $row->amount);

        $this->get('/admin/feemaster/delete/'.$row->id)->assertRedirect(route('fees.fee_masters.index'));
        $this->assertDatabaseMissing('fee_groups_feetype', ['id' => $row->id]);
        $this->createdRowIds = [];

        // recreate then delete group
        $this->post('/admin/feemaster', [
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'amount' => '200',
            'account_type' => 'none',
        ])->assertRedirect();

        $sessionGroup->refresh();
        $this->get('/admin/feemaster/deletegrp/'.$sessionGroup->id)->assertRedirect(route('fees.fee_masters.index'));
        $this->assertDatabaseMissing('fee_session_groups', ['id' => $sessionGroup->id]);
        $this->createdSessionGroupIds = [];
    }
}
