<?php

namespace Tests\Feature\Transport;

use App\Modules\Staff\Models\Staff;
use App\Modules\Transport\Models\PickupPoint;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupPointMapTest extends TestCase
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

        $token = uniqid('ppm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PPM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Pickup',
            'surname' => 'Map',
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

    public function test_pointmap_returns_ci_shaped_payload(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $point = PickupPoint::query()->create([
            'name' => 'Map Stop '.$suffix,
            'latitude' => '24.8607',
            'longitude' => '67.0011',
        ]);
        $this->cleanupPointIds[] = $point->id;

        $this->get('/admin/pickuppoint')
            ->assertOk()
            ->assertSee('pickup_map', false)
            ->assertSee('data-pick-location="'.$point->id.'"', false);

        $response = $this->postJson('/admin/pickuppoint/pointmap', [
            'pick_location' => $point->id,
        ])->assertOk()
            ->assertJsonPath('status', '1')
            ->assertJsonPath('error', '')
            ->assertJsonPath('page.location.id', $point->id)
            ->assertJsonPath('page.location.name', 'Map Stop '.$suffix)
            ->assertJsonPath('page.location.latitude', '24.8607')
            ->assertJsonPath('page.location.longitude', '67.0011');

        $pageHtml = (string) $response->json('page.page');
        $this->assertStringContainsString('id="sample"', $pageHtml);
        $this->assertStringContainsString('Map Stop '.$suffix, $pageHtml);
    }
}
