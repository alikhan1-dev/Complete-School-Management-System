<?php

namespace Tests\Feature\Transport;

use App\Modules\Staff\Models\Staff;
use App\Modules\Transport\Models\PickupPoint;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupPointCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupPointIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupPointIds !== []) {
            DB::table('route_pickup_point')->whereIn('pickup_point_id', $this->cleanupPointIds)->delete();
            DB::table('pickup_point')->whereIn('id', $this->cleanupPointIds)->delete();
        }
        $this->cleanupPointIds = [];

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

        $token = uniqid('pkp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PKP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Pickup',
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

    public function test_pickup_point_list_create_edit_delete(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/pickuppoint')
            ->assertOk()
            ->assertSee('Pickup Point List', false)
            ->assertSee('Add Pickup Point', false);

        $this->post('/admin/pickuppoint/add_point', [
            'name' => 'Point '.$suffix,
            'latitude' => '24.8607',
            'longitude' => '67.0011',
        ])->assertRedirect('/admin/pickuppoint');

        $point = PickupPoint::query()->where('name', 'Point '.$suffix)->firstOrFail();
        $this->cleanupPointIds[] = $point->id;
        $this->assertSame('24.8607', (string) $point->latitude);

        $routeId = (int) DB::table('transport_route')->insertGetId([
            'route_title' => 'Temp Pickup Route '.$suffix,
            'no_of_vehicle' => null,
            'note' => '',
            'is_active' => 'no',
        ]);
        $sessionId = (int) (DB::table('sch_settings')->value('session_id') ?: 1);
        DB::table('route_pickup_point')->insert([
            'session_id' => $sessionId,
            'transport_route_id' => $routeId,
            'pickup_point_id' => $point->id,
            'fees' => 0,
            'destination_distance' => '1',
            'pickup_time' => '08:00:00',
            'order_number' => 1,
        ]);

        $this->get('/admin/pickuppoint/edit/'.$point->id)
            ->assertOk()
            ->assertSee('Edit Pickup Point', false)
            ->assertSee('Point '.$suffix, false);

        $this->post('/admin/pickuppoint/edit/'.$point->id, [
            'name' => 'Updated Point '.$suffix,
            'latitude' => '25.0000',
            'longitude' => '68.0000',
        ])->assertRedirect('/admin/pickuppoint');

        $point->refresh();
        $this->assertSame('Updated Point '.$suffix, (string) $point->name);
        $this->assertSame('25.0000', (string) $point->latitude);

        $this->get('/admin/pickuppoint/delete_point/'.$point->id)->assertRedirect('/admin/pickuppoint');
        $this->assertNull(PickupPoint::query()->find($point->id));
        $this->assertSame(0, DB::table('route_pickup_point')->where('pickup_point_id', $point->id)->count());

        DB::table('transport_route')->where('id', $routeId)->delete();
        $this->cleanupPointIds = [];
    }
}
