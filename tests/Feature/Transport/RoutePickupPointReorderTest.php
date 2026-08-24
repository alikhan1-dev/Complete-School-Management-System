<?php

namespace Tests\Feature\Transport;

use App\Modules\Staff\Models\Staff;
use App\Modules\Transport\Models\PickupPoint;
use App\Modules\Transport\Models\TransportRoute;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoutePickupPointReorderTest extends TestCase
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

        $token = uniqid('rpr', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'RPR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Reorder',
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

    public function test_reorder_loads_rows_and_persists_order(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $sessionId = (int) DB::table('sch_settings')->value('session_id');
        $this->assertGreaterThan(0, $sessionId);

        $route = TransportRoute::query()->create([
            'route_title' => 'Reorder Route '.$suffix,
            'no_of_vehicle' => null,
            'note' => '',
            'is_active' => 'no',
        ]);
        $this->cleanupRouteIds[] = $route->id;

        $p1 = PickupPoint::query()->create([
            'name' => 'First Stop '.$suffix,
            'latitude' => '24.1',
            'longitude' => '67.1',
        ]);
        $p2 = PickupPoint::query()->create([
            'name' => 'Second Stop '.$suffix,
            'latitude' => '24.2',
            'longitude' => '67.2',
        ]);
        $this->cleanupPointIds[] = $p1->id;
        $this->cleanupPointIds[] = $p2->id;

        $id1 = DB::table('route_pickup_point')->insertGetId([
            'session_id' => $sessionId,
            'transport_route_id' => $route->id,
            'pickup_point_id' => $p1->id,
            'fees' => 100,
            'destination_distance' => '1',
            'pickup_time' => '07:00:00',
            'order_number' => 1,
        ]);
        $id2 = DB::table('route_pickup_point')->insertGetId([
            'session_id' => $sessionId,
            'transport_route_id' => $route->id,
            'pickup_point_id' => $p2->id,
            'fees' => 200,
            'destination_distance' => '2',
            'pickup_time' => '07:15:00',
            'order_number' => 2,
        ]);

        $this->get('/admin/pickuppoint/assign')
            ->assertOk()
            ->assertSee('openReorder('.$route->id.')', false);

        $load = $this->postJson('/admin/pickuppoint/reorder', [
            'route_id' => $route->id,
        ])->assertOk();

        $html = $load->json();
        $this->assertIsString($html);
        $this->assertStringContainsString('First Stop '.$suffix, $html);
        $this->assertStringContainsString('Second Stop '.$suffix, $html);
        $this->assertStringContainsString('id="'.$id1.'"', $html);

        // Reverse order: second becomes order 1.
        $save = $this->postJson('/admin/pickuppoint/reorder_pointid', [
            'position' => [$id2, $id1],
        ])->assertOk();
        $this->assertSame($route->id, (int) $save->json());

        $ordered = DB::table('route_pickup_point')
            ->where('transport_route_id', $route->id)
            ->orderBy('order_number')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->assertSame([(int) $id2, (int) $id1], $ordered);
    }
}
