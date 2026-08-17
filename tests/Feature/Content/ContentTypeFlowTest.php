<?php

namespace Tests\Feature\Content;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentTypeFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupIds !== []) {
            DB::table('content_types')->whereIn('id', $this->cleanupIds)->delete();
        }
        $this->cleanupIds = [];

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

        $token = uniqid('ct', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Content',
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

    public function test_admin_contenttype_requires_staff_auth(): void
    {
        $this->get('/admin/contenttype')->assertRedirect();
        $this->get('/admin/contenttype/index')->assertRedirect();
        $this->post('/admin/contenttype/getcontenttypelist')->assertRedirect();
    }

    public function test_superadmin_can_view_content_type_list(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/admin/contenttype')
            ->assertOk()
            ->assertSee('Add Content Type', false)
            ->assertSee('Content Type List', false)
            ->assertSee('name="name"', false);
    }

    public function test_name_is_required_on_create(): void
    {
        $this->actingAsSuperAdmin();

        $this->post('/admin/contenttype', ['description' => 'x'])
            ->assertOk()
            ->assertSee('The Name field is required.', false);
    }

    public function test_superadmin_can_crud_content_type(): void
    {
        $this->actingAsSuperAdmin();
        $name = 'Ctype'.uniqid();

        $this->post('/admin/contenttype', [
            'name' => $name,
            'description' => 'Desc '.$name,
        ])->assertRedirect('/admin/contenttype/index');

        $row = DB::table('content_types')->where('name', $name)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;
        $this->assertSame('Desc '.$name, $row->description);
        $this->assertSame(1, (int) $row->is_active);

        $this->get('/admin/contenttype/index')
            ->assertOk()
            ->assertSee($name, false)
            ->assertSee('Record Saved Successfully', false);

        $this->get('/admin/contenttype/edit/'.$row->id)
            ->assertOk()
            ->assertSee('Edit Content Type', false)
            ->assertSee($name, false);

        $this->post('/admin/contenttype/edit/'.$row->id, [
            'name' => $name.' Edited',
            'description' => 'Updated',
        ])->assertRedirect('/admin/contenttype/index');

        $updated = DB::table('content_types')->where('id', $row->id)->first();
        $this->assertSame($name.' Edited', $updated->name);
        $this->assertSame('Updated', $updated->description);

        $this->get('/admin/contenttype/delete/'.$row->id)
            ->assertRedirect('/admin/contenttype/index');
        $this->assertNull(DB::table('content_types')->where('id', $row->id)->first());
        $this->cleanupIds = [];
    }

    public function test_empty_description_lists_as_no_description(): void
    {
        $this->actingAsSuperAdmin();
        $name = 'Cempty'.uniqid();

        $this->post('/admin/contenttype', [
            'name' => $name,
            'description' => '',
        ])->assertRedirect('/admin/contenttype/index');

        $row = DB::table('content_types')->where('name', $name)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;

        $this->get('/admin/contenttype')->assertOk()->assertSee('No Description', false);

        $json = $this->post('/admin/contenttype/getcontenttypelist', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'search' => ['value' => $name],
        ])->assertOk()->json();

        $this->assertSame(1, $json['draw']);
        $names = array_column($json['data'], 0);
        $this->assertContains($name, $names);
        $index = array_search($name, $names, true);
        $this->assertSame('No Description', $json['data'][$index][1]);
        $this->assertStringContainsString('admin/contenttype/edit/'.$row->id, $json['data'][$index][2]);
        $this->assertStringContainsString('admin/contenttype/delete/'.$row->id, $json['data'][$index][2]);
    }

    public function test_getcontenttypelist_json_contract(): void
    {
        $this->actingAsSuperAdmin();
        $name = 'Cjson'.uniqid();

        $id = DB::table('content_types')->insertGetId([
            'name' => $name,
            'description' => 'Listed',
            'is_active' => 1,
        ]);
        $this->cleanupIds[] = $id;

        $this->post('/admin/contenttype/getcontenttypelist', [
            'draw' => 3,
            'start' => 0,
            'length' => 50,
            'search' => ['value' => $name],
        ])
            ->assertOk()
            ->assertJsonPath('draw', 3)
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    public function test_edit_missing_content_type_is_404(): void
    {
        $this->actingAsSuperAdmin();
        $this->get('/admin/contenttype/edit/999999991')->assertNotFound();
    }
}
