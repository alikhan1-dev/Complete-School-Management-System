<?php

namespace Tests\Feature\Hostel;

use App\Modules\Hostel\Models\Hostel;
use App\Modules\Hostel\Models\HostelRoom;
use App\Modules\Hostel\Models\RoomType;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HostelRoomCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupHostelIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<int> */
    private array $cleanupRoomIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupRoomIds !== []) {
            DB::table('hostel_rooms')->whereIn('id', $this->cleanupRoomIds)->delete();
        }
        $this->cleanupRoomIds = [];

        if ($this->cleanupHostelIds !== []) {
            DB::table('hostel_rooms')->whereIn('hostel_id', $this->cleanupHostelIds)->delete();
            DB::table('hostel')->whereIn('id', $this->cleanupHostelIds)->delete();
        }
        $this->cleanupHostelIds = [];

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

        $token = uniqid('hrm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HRM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'HostelRoom',
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

    public function test_create_edit_delete_room_and_get_by_hostel(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $hostel = Hostel::query()->create([
            'hostel_name' => 'Room Hostel '.$suffix,
            'type' => 'Boys',
            'address' => '',
            'intake' => '20',
            'description' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupHostelIds[] = $hostel->id;

        $type = RoomType::query()->create([
            'room_type' => 'Shared '.$suffix,
            'description' => '',
        ]);
        $this->cleanupTypeIds[] = $type->id;

        $this->get('/admin/hostelroom')
            ->assertOk()
            ->assertSee('Add Hostel Room', false)
            ->assertSee('Hostel Room List', false);

        $this->post('/admin/hostelroom/create', [
            'room_no' => 'R-'.$suffix,
            'hostel_id' => $hostel->id,
            'room_type_id' => $type->id,
            'no_of_bed' => 4,
            'cost_per_bed' => 250.75,
            'description' => 'Corner room '.$suffix,
        ])->assertRedirect('/admin/hostelroom');

        $room = HostelRoom::query()->where('room_no', 'R-'.$suffix)->firstOrFail();
        $this->cleanupRoomIds[] = $room->id;

        $this->get('/admin/hostelroom')
            ->assertOk()
            ->assertSee('R-'.$suffix, false)
            ->assertSee('Room Hostel '.$suffix, false)
            ->assertSee('Shared '.$suffix, false);

        $this->get('/admin/hostelroom/edit/'.$room->id)
            ->assertOk()
            ->assertSee('Edit Hostel Room', false);

        $this->post('/admin/hostelroom/edit/'.$room->id, [
            'room_no' => 'R2-'.$suffix,
            'hostel_id' => $hostel->id,
            'room_type_id' => $type->id,
            'no_of_bed' => 6,
            'cost_per_bed' => 300,
            'description' => 'Updated '.$suffix,
        ])->assertRedirect('/admin/hostelroom');

        $room->refresh();
        $this->assertSame('R2-'.$suffix, $room->room_no);
        $this->assertSame(6, (int) $room->no_of_bed);

        $this->getJson('/admin/hostelroom/getRoom?hostel_id='.$hostel->id)
            ->assertOk()
            ->assertJsonFragment(['room_no' => 'R2-'.$suffix]);

        $this->get('/admin/hostelroom/delete/'.$room->id)->assertRedirect('/admin/hostelroom');
        $this->assertNull(HostelRoom::query()->find($room->id));
        $this->cleanupRoomIds = [];
    }
}
