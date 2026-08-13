<?php

namespace Tests\Feature\Hostel;

use App\Modules\Hostel\Models\Hostel;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HostelCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupHostelIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupHostelIds !== []) {
            DB::table('hostel_rooms')->whereIn('hostel_id', $this->cleanupHostelIds)->delete();
            DB::table('hostel')->whereIn('id', $this->cleanupHostelIds)->delete();
        }
        $this->cleanupHostelIds = [];

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

        $token = uniqid('hst', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HST-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Hostel',
            'surname' => 'Staff',
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

    public function test_create_edit_and_delete_hostel(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/hostel')
            ->assertOk()
            ->assertSee('Add Hostel', false)
            ->assertSee('Hostel List', false);

        $this->post('/admin/hostel/create', [
            'hostel_name' => 'North Block '.$suffix,
            'type' => 'Boys',
            'address' => 'Street '.$suffix,
            'intake' => '40',
            'description' => 'Desc '.$suffix,
        ])->assertRedirect('/admin/hostel');

        $hostel = Hostel::query()->where('hostel_name', 'North Block '.$suffix)->firstOrFail();
        $this->cleanupHostelIds[] = $hostel->id;
        $this->assertSame('Boys', $hostel->type);

        $this->get('/admin/hostel/edit/'.$hostel->id)
            ->assertOk()
            ->assertSee('Edit Hostel', false)
            ->assertSee('North Block '.$suffix, false);

        $this->post('/admin/hostel/edit/'.$hostel->id, [
            'hostel_name' => 'South Block '.$suffix,
            'type' => 'Girls',
            'address' => 'Ave '.$suffix,
            'intake' => '50',
            'description' => 'Updated '.$suffix,
        ])->assertRedirect('/admin/hostel');

        $hostel->refresh();
        $this->assertSame('South Block '.$suffix, $hostel->hostel_name);
        $this->assertSame('Girls', $hostel->type);

        $this->get('/admin/hostel/delete/'.$hostel->id)->assertRedirect('/admin/hostel');
        $this->assertNull(Hostel::query()->find($hostel->id));
        $this->cleanupHostelIds = [];
    }
}
