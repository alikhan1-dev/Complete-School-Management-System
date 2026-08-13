<?php

namespace Tests\Feature\Hostel;

use App\Modules\Hostel\Models\Hostel;
use App\Modules\Hostel\Models\RoomType;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoomTypeCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupTypeIds !== []) {
            DB::table('hostel_rooms')->whereIn('room_type_id', $this->cleanupTypeIds)->delete();
            DB::table('room_types')->whereIn('id', $this->cleanupTypeIds)->delete();
        }
        $this->cleanupTypeIds = [];

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

        $token = uniqid('hrt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HRT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'RoomType',
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

    public function test_create_edit_and_delete_room_type(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/roomtype')
            ->assertOk()
            ->assertSee('Add Room Type', false)
            ->assertSee('Room Type List', false);

        $this->post('/admin/roomtype/create', [
            'room_type' => 'Single '.$suffix,
            'description' => 'Desc '.$suffix,
        ])->assertRedirect('/admin/roomtype');

        $type = RoomType::query()->where('room_type', 'Single '.$suffix)->firstOrFail();
        $this->cleanupTypeIds[] = $type->id;

        $this->get('/admin/roomtype/edit/'.$type->id)
            ->assertOk()
            ->assertSee('Edit Room Type', false)
            ->assertSee('Single '.$suffix, false);

        $this->post('/admin/roomtype/edit/'.$type->id, [
            'room_type' => 'Double '.$suffix,
            'description' => 'Updated '.$suffix,
        ])->assertRedirect('/admin/roomtype');

        $this->assertSame('Double '.$suffix, RoomType::query()->findOrFail($type->id)->room_type);

        $this->get('/admin/roomtype/delete/'.$type->id)->assertRedirect('/admin/roomtype');
        $this->assertNull(RoomType::query()->find($type->id));
        $this->cleanupTypeIds = [];
    }
}
