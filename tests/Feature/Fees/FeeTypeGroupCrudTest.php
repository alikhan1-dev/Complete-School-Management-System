<?php

namespace Tests\Feature\Fees;

use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeTypeGroupCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdFeeTypeIds = [];

    /** @var list<int> */
    private array $createdFeeGroupIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFeeTypeIds as $id) {
            DB::table('feetype')->where('id', $id)->delete();
        }
        $this->createdFeeTypeIds = [];

        foreach ($this->createdFeeGroupIds as $id) {
            DB::table('fee_groups')->where('id', $id)->delete();
        }
        $this->createdFeeGroupIds = [];

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

        $token = uniqid('fee', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FEE-'.$token,
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

    public function test_fee_type_and_group_crud_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/feetype')->assertOk()->assertSee('Fees Type List', false);
        $this->post('/admin/feetype', [
            'name' => 'Type-'.$suffix,
            'code' => 'CODE-'.$suffix,
            'description' => 'type desc',
        ])->assertRedirect(route('fees.fee_types.index'));

        $type = FeeType::query()->where('type', 'Type-'.$suffix)->firstOrFail();
        $this->createdFeeTypeIds[] = $type->id;
        $this->assertSame(0, (int) $type->is_system);
        $this->assertSame('CODE-'.$suffix, $type->code);

        $this->post('/admin/feetype', [
            'name' => 'Type-'.$suffix,
            'code' => 'CODE-dup-'.$suffix,
        ])->assertSessionHasErrors('name');

        $this->post('/admin/feetype/edit/'.$type->id, [
            'name' => 'Type-'.$suffix.'-u',
            'code' => 'CODE-'.$suffix.'-u',
            'description' => 'updated',
        ])->assertRedirect(route('fees.fee_types.index'));

        $this->assertDatabaseHas('feetype', [
            'id' => $type->id,
            'type' => 'Type-'.$suffix.'-u',
            'code' => 'CODE-'.$suffix.'-u',
        ]);

        $this->get('/admin/feegroup')->assertOk()->assertSee('Fees Group List', false);
        $this->post('/admin/feegroup', [
            'name' => 'Group-'.$suffix,
            'description' => 'group desc',
        ])->assertRedirect(route('fees.fee_groups.index'));

        $group = FeeGroup::query()->where('name', 'Group-'.$suffix)->firstOrFail();
        $this->createdFeeGroupIds[] = $group->id;

        $this->post('/admin/feegroup', [
            'name' => 'Group-'.$suffix,
        ])->assertSessionHasErrors('name');

        $this->post('/admin/feegroup/edit/'.$group->id, [
            'name' => 'Group-'.$suffix.'-u',
            'description' => 'updated group',
        ])->assertRedirect(route('fees.fee_groups.index'));

        $this->get('/admin/feetype/delete/'.$type->id)->assertRedirect(route('fees.fee_types.index'));
        $this->assertDatabaseMissing('feetype', ['id' => $type->id]);
        $this->createdFeeTypeIds = [];

        $this->get('/admin/feegroup/delete/'.$group->id)->assertRedirect(route('fees.fee_groups.index'));
        $this->assertDatabaseMissing('fee_groups', ['id' => $group->id]);
        $this->createdFeeGroupIds = [];
    }
}
