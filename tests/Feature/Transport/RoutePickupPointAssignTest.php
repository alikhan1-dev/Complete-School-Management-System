<?php

namespace Tests\Feature\Transport;

use App\Modules\Staff\Models\Staff;
use App\Modules\Transport\Models\PickupPoint;
use App\Modules\Transport\Models\TransportRoute;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoutePickupPointAssignTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupRouteIds = [];

    /** @var list<int> */
    private array $cleanupPointIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupRouteIds !== []) {
            DB::table('route_pickup_point')->whereIn('transport_route_id', $this->cleanupRouteIds)->delete();
            DB::table('transport_route')->whereIn('id', $this->cleanupRouteIds)->delete();
        }
        $this->cleanupRouteIds = [];

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

        $token = uniqid('rpp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'RPP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'RoutePickup',
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

    public function test_assign_edit_and_delete_route_pickup_points(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $sessionId = (int) DB::table('sch_settings')->value('session_id');
        $this->assertGreaterThan(0, $sessionId);

        $route = TransportRoute::query()->create([
            'route_title' => 'RPP Route '.$suffix,
            'no_of_vehicle' => null,
            'note' => '',
            'is_active' => 'no',
        ]);
        $this->cleanupRouteIds[] = $route->id;

        $p1 = PickupPoint::query()->create([
            'name' => 'Stop A '.$suffix,
            'latitude' => '24.1',
            'longitude' => '67.1',
        ]);
        $p2 = PickupPoint::query()->create([
            'name' => 'Stop B '.$suffix,
            'latitude' => '24.2',
            'longitude' => '67.2',
        ]);
        $this->cleanupPointIds[] = $p1->id;
        $this->cleanupPointIds[] = $p2->id;

        $this->get('/admin/pickuppoint/assign')
            ->assertOk()
            ->assertSee('Route Pickup Point', false);

        $this->get('/admin/pickuppoint/assign/create')
            ->assertOk()
            ->assertSee('Assign Route Pickup Point', false);

        $this->post('/admin/pickuppoint/assign/create', [
            'route_id' => $route->id,
            'points' => [
                [
                    'pickup_point_id' => $p1->id,
                    'fees' => '100.50',
                    'destination_distance' => '2.5',
                    'pickup_time' => '07:30',
                ],
                [
                    'pickup_point_id' => $p2->id,
                    'fees' => '150',
                    'destination_distance' => '4',
                    'pickup_time' => '07:45',
                ],
            ],
        ])->assertRedirect('/admin/pickuppoint/assign');

        $this->assertSame(2, DB::table('route_pickup_point')
            ->where('transport_route_id', $route->id)
            ->where('session_id', $sessionId)
            ->count());

        $this->from('/admin/pickuppoint/assign/create')
            ->post('/admin/pickuppoint/assign/create', [
                'route_id' => $route->id,
                'points' => [
                    [
                        'pickup_point_id' => $p1->id,
                        'fees' => '10',
                        'destination_distance' => '1',
                        'pickup_time' => '08:00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('route_id');

        $this->get('/admin/pickuppoint/assign')
            ->assertOk()
            ->assertSee('RPP Route '.$suffix, false)
            ->assertSee('Stop A '.$suffix, false)
            ->assertSee('Stop B '.$suffix, false);

        $this->post('/admin/pickuppoint/assign/edit/'.$route->id, [
            'route_id' => $route->id,
            'points' => [
                [
                    'pickup_point_id' => $p1->id,
                    'fees' => '120',
                    'destination_distance' => '3',
                    'pickup_time' => '07:20',
                ],
            ],
        ])->assertRedirect('/admin/pickuppoint/assign');

        $rows = DB::table('route_pickup_point')
            ->where('transport_route_id', $route->id)
            ->where('session_id', $sessionId)
            ->get();
        $this->assertCount(1, $rows);
        $this->assertSame($p1->id, (int) $rows[0]->pickup_point_id);
        $this->assertEquals(120.0, (float) $rows[0]->fees);

        $this->get('/admin/pickuppoint/delete/'.$route->id)->assertRedirect('/admin/pickuppoint/assign');
        $this->assertSame(0, DB::table('route_pickup_point')
            ->where('transport_route_id', $route->id)
            ->where('session_id', $sessionId)
            ->count());
    }
}
