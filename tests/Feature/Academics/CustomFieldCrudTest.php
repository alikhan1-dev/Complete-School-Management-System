<?php

namespace Tests\Feature\Academics;

use App\Modules\Academics\Models\CustomField;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomFieldCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    protected function tearDown(): void
    {
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

        $token = uniqid('cf', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CF-'.$token,
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

    public function test_custom_field_crud_and_select_requires_values(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/customfield')->assertOk()->assertSee('Custom Field List', false);

        $this->post('/admin/customfield', [
            'belong_to' => 'students',
            'type' => 'select',
            'name' => 'CF-'.$suffix,
            'column' => 6,
            'field_values' => '',
        ])->assertSessionHasErrors('field_values');

        $this->post('/admin/customfield', [
            'belong_to' => 'students',
            'type' => 'input',
            'name' => 'CF-'.$suffix,
            'column' => 6,
            'validation' => '1',
            'display_tbl' => '1',
            'field_values' => '',
        ])->assertRedirect(route('academics.custom_fields.index'));

        $field = CustomField::query()->where('name', 'CF-'.$suffix)->firstOrFail();
        $this->assertSame(1, (int) $field->validation);
        $this->assertSame(1, (int) $field->visible_on_table);

        $this->post('/admin/customfield/edit/'.$field->id, [
            'belong_to' => 'students',
            'type' => 'input',
            'name' => 'CF-'.$suffix.'-u',
            'column' => 12,
        ])->assertRedirect(route('academics.custom_fields.index'));

        $this->post('/admin/customfield/updateorder', [
            'belong_to' => 'students',
            'items' => [$field->id],
        ])->assertOk()->assertJson(['status' => '1']);

        $this->get('/admin/customfield/delete/'.$field->id)
            ->assertRedirect(route('academics.custom_fields.index'));
        $this->assertDatabaseMissing('custom_fields', ['id' => $field->id]);
    }
}
