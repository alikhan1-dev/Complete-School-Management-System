<?php

namespace Tests\Feature\FrontOffice;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhoneCallLogFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupIds !== []) {
            DB::table('general_calls')->whereIn('id', $this->cleanupIds)->delete();
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

        $token = uniqid('pcl', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PCL-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Call',
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

    public function test_generalcall_index_requires_staff_auth(): void
    {
        $this->get('/admin/generalcall')->assertRedirect();
    }

    public function test_create_requires_call_type_phone_and_date(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/generalcall', [
            'name' => 'Parent',
        ])->assertOk()
            ->assertSee('The Call Type field is required.', false)
            ->assertSee('The Phone field is required.', false)
            ->assertSee('The Date field is required.', false);
    }

    public function test_superadmin_can_add_list_edit_and_delete_call_log(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = substr(uniqid(), -6);
        $name = 'Caller '.$suffix;
        $phone = '0300'.$suffix;
        $today = date('Y-m-d');
        $follow = date('Y-m-d', strtotime('+2 days'));

        $this->get('/admin/generalcall')->assertOk()->assertSee('Phone Call Log List', false);

        $this->post('/admin/generalcall', [
            'name' => $name,
            'contact' => $phone,
            'date' => $today,
            'description' => 'Fee query',
            'follow_up_date' => $follow,
            'call_duration' => '5 min',
            'note' => 'Called office',
            'call_type' => 'Incoming',
        ])->assertRedirect('/admin/generalcall');

        $row = DB::table('general_calls')->where('name', $name)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;
        $this->assertSame($phone, $row->contact);
        $this->assertSame('Incoming', $row->call_type);
        $this->assertSame($follow, $row->follow_up_date);

        $this->get('/admin/generalcall')->assertOk()->assertSee($name, false);
        $this->get('/admin/generalcall/details/'.$row->id)->assertOk()->assertSee('Fee query', false);

        $this->post('/admin/generalcall/getcalllist', ['draw' => 1, 'start' => 0, 'length' => 50])
            ->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $this->get('/admin/generalcall/edit/'.$row->id)->assertOk()->assertSee($name, false);

        $this->post('/admin/generalcall/edit/'.$row->id, [
            'name' => $name.' Edited',
            'contact' => $phone,
            'date' => $today,
            'description' => 'Fee query',
            'follow_up_date' => '',
            'call_duration' => '6 min',
            'note' => 'Closed',
            'call_type' => 'Outgoing',
        ])->assertRedirect('/admin/generalcall');

        $updated = DB::table('general_calls')->where('id', $row->id)->first();
        $this->assertSame($name.' Edited', $updated->name);
        $this->assertSame('Outgoing', $updated->call_type);
        $this->assertTrue(in_array((string) $updated->follow_up_date, ['0000-00-00', ''], true) || $updated->follow_up_date === null);

        $this->get('/admin/generalcall/delete/'.$row->id)->assertRedirect('/admin/generalcall');
        $this->assertNull(DB::table('general_calls')->where('id', $row->id)->first());
        $this->cleanupIds = [];
    }
}
