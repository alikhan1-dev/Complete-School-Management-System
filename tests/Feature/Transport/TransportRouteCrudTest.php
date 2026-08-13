<?php

namespace Tests\Feature\Transport;

use App\Modules\Staff\Models\Staff;
use App\Modules\Transport\Models\TransportRoute;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransportRouteCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupRouteIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupRouteIds !== []) {
            DB::table('vehicle_routes')->whereIn('route_id', $this->cleanupRouteIds)->delete();
            DB::table('route_pickup_point')->whereIn('transport_route_id', $this->cleanupRouteIds)->delete();
            DB::table('transport_route')->whereIn('id', $this->cleanupRouteIds)->delete();
        }
        $this->cleanupRouteIds = [];

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

        $token = uniqid('rte', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'RTE-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Route',
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

    public function test_route_list_create_edit_delete(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/route')
            ->assertOk()
            ->assertSee('Route List', false)
            ->assertSee('Create Route', false);

        $this->post('/admin/route/create', [
            'route_title' => 'Route '.$suffix,
        ])->assertRedirect('/admin/route');

        $route = TransportRoute::query()->where('route_title', 'Route '.$suffix)->firstOrFail();
        $this->cleanupRouteIds[] = $route->id;

        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'vehicle_no' => 'RV-'.$suffix,
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
        DB::table('vehicle_routes')->insert([
            'route_id' => $route->id,
            'vehicle_id' => $vehicleId,
        ]);

        $this->get('/admin/route/edit/'.$route->id)
            ->assertOk()
            ->assertSee('Edit Route', false)
            ->assertSee('Route '.$suffix, false);

        $this->post('/admin/route/edit/'.$route->id, [
            'route_title' => 'Updated Route '.$suffix,
        ])->assertRedirect('/admin/route');

        $route->refresh();
        $this->assertSame('Updated Route '.$suffix, (string) $route->route_title);

        $this->get('/admin/route/delete/'.$route->id)->assertRedirect('/admin/route');
        $this->assertNull(TransportRoute::query()->find($route->id));
        $this->assertSame(0, DB::table('vehicle_routes')->where('route_id', $route->id)->count());

        DB::table('vehicles')->where('id', $vehicleId)->delete();
        $this->cleanupRouteIds = [];
    }
}
