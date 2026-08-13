<?php

namespace Tests\Feature\Transport;

use App\Modules\Staff\Models\Staff;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VehicleRouteAssignTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupRouteIds = [];

    /** @var list<int> */
    private array $cleanupVehicleIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupRouteIds !== []) {
            DB::table('vehicle_routes')->whereIn('route_id', $this->cleanupRouteIds)->delete();
            DB::table('transport_route')->whereIn('id', $this->cleanupRouteIds)->delete();
        }
        $this->cleanupRouteIds = [];

        if ($this->cleanupVehicleIds !== []) {
            DB::table('vehicle_routes')->whereIn('vehicle_id', $this->cleanupVehicleIds)->delete();
            DB::table('vehicles')->whereIn('id', $this->cleanupVehicleIds)->delete();
        }
        $this->cleanupVehicleIds = [];

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

        $token = uniqid('vra', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'VRA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Vehroute',
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

    public function test_assign_edit_and_delete_vehicles_on_route(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $route = TransportRoute::query()->create([
            'route_title' => 'Assign Route '.$suffix,
            'no_of_vehicle' => null,
            'note' => '',
            'is_active' => 'no',
        ]);
        $route2 = TransportRoute::query()->create([
            'route_title' => 'Other Route '.$suffix,
            'no_of_vehicle' => null,
            'note' => '',
            'is_active' => 'no',
        ]);
        $this->cleanupRouteIds[] = $route->id;
        $this->cleanupRouteIds[] = $route2->id;

        $v1 = Vehicle::query()->create([
            'vehicle_no' => 'VA-'.$suffix,
            'vehicle_model' => '',
            'vehicle_photo' => '',
            'manufacture_year' => '',
            'registration_number' => '',
            'chasis_number' => '',
            'max_seating_capacity' => 0,
            'driver_name' => '',
            'driver_licence' => '',
            'driver_contact' => '',
            'note' => '',
        ]);
        $v2 = Vehicle::query()->create([
            'vehicle_no' => 'VB-'.$suffix,
            'vehicle_model' => '',
            'vehicle_photo' => '',
            'manufacture_year' => '',
            'registration_number' => '',
            'chasis_number' => '',
            'max_seating_capacity' => 0,
            'driver_name' => '',
            'driver_licence' => '',
            'driver_contact' => '',
            'note' => '',
        ]);
        $this->cleanupVehicleIds[] = $v1->id;
        $this->cleanupVehicleIds[] = $v2->id;

        $this->get('/admin/vehroute')
            ->assertOk()
            ->assertSee('Assign Vehicle On Route', false)
            ->assertSee('Vehicle Route List', false);

        $this->post('/admin/vehroute', [
            'route_id' => $route->id,
            'vehicle' => [$v1->id, $v2->id],
        ])->assertRedirect('/admin/vehroute');

        $this->assertSame(2, DB::table('vehicle_routes')->where('route_id', $route->id)->count());

        $this->from('/admin/vehroute')
            ->post('/admin/vehroute', [
                'route_id' => $route->id,
                'vehicle' => [$v1->id],
            ])
            ->assertSessionHasErrors('route_id');

        $this->get('/admin/vehroute')
            ->assertOk()
            ->assertSee('Assign Route '.$suffix, false)
            ->assertSee('VA-'.$suffix, false)
            ->assertSee('VB-'.$suffix, false);

        $this->get('/admin/vehroute/edit/'.$route->id)
            ->assertOk()
            ->assertSee('Edit Vehicle On Route', false);

        $this->post('/admin/vehroute/edit/'.$route->id, [
            'pre_route_id' => $route->id,
            'route_id' => $route->id,
            'vehicle' => [$v1->id],
        ])->assertRedirect('/admin/vehroute');

        $this->assertSame(1, DB::table('vehicle_routes')->where('route_id', $route->id)->count());
        $this->assertSame($v1->id, (int) DB::table('vehicle_routes')->where('route_id', $route->id)->value('vehicle_id'));

        $this->get('/admin/vehroute/delete/'.$route->id)->assertRedirect('/admin/vehroute');
        $this->assertSame(0, DB::table('vehicle_routes')->where('route_id', $route->id)->count());
    }
}
