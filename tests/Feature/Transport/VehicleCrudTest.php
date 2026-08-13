<?php

namespace Tests\Feature\Transport;

use App\Modules\Staff\Models\Staff;
use App\Modules\Transport\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class VehicleCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupVehicleIds = [];

    /** @var list<string> */
    private array $cleanupPhotos = [];

    protected function tearDown(): void
    {
        if ($this->cleanupVehicleIds !== []) {
            DB::table('vehicle_routes')->whereIn('vehicle_id', $this->cleanupVehicleIds)->delete();
            DB::table('vehicles')->whereIn('id', $this->cleanupVehicleIds)->delete();
        }
        $this->cleanupVehicleIds = [];

        foreach ($this->cleanupPhotos as $name) {
            $path = public_path('uploads/vehicle_photo/'.basename($name));
            if (is_file($path)) {
                File::delete($path);
            }
        }
        $this->cleanupPhotos = [];

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

        $token = uniqid('veh', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'VEH-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Transport',
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

    public function test_vehicle_list_create_edit_view_delete(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/vehicle')
            ->assertOk()
            ->assertSee('Vehicle List', false)
            ->assertSee('Add Vehicle', false);

        $photo = UploadedFile::fake()->image('bus_'.$suffix.'.jpg', 120, 80);

        $this->post('/admin/vehicle/add', [
            'vehicle_no' => 'VN-'.$suffix,
            'vehicle_model' => 'Model '.$suffix,
            'manufacture_year' => '2020',
            'registration_number' => 'REG-'.$suffix,
            'chasis_number' => 'CHS-'.$suffix,
            'max_seating_capacity' => 40,
            'driver_name' => 'Driver '.$suffix,
            'driver_licence' => 'DL-'.$suffix,
            'driver_contact' => '03001234567',
            'note' => 'Note '.$suffix,
            'vehicle_photo' => $photo,
        ])->assertRedirect('/admin/vehicle');

        $vehicle = Vehicle::query()->where('vehicle_no', 'VN-'.$suffix)->firstOrFail();
        $this->cleanupVehicleIds[] = $vehicle->id;
        if ($vehicle->vehicle_photo) {
            $this->cleanupPhotos[] = (string) $vehicle->vehicle_photo;
        }

        $this->assertSame('Model '.$suffix, (string) $vehicle->vehicle_model);
        $this->assertSame(40, (int) $vehicle->max_seating_capacity);
        $this->assertNotSame('', (string) $vehicle->vehicle_photo);
        $this->assertFileExists(public_path('uploads/vehicle_photo/'.$vehicle->vehicle_photo));

        $routeId = (int) DB::table('transport_route')->insertGetId([
            'route_title' => 'Temp Route '.$suffix,
            'no_of_vehicle' => 1,
            'note' => '',
            'is_active' => 'no',
        ]);
        DB::table('vehicle_routes')->insert([
            'route_id' => $routeId,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->get('/admin/vehicle/view/'.$vehicle->id)
            ->assertOk()
            ->assertSee('Vehicle Details', false)
            ->assertSee('VN-'.$suffix, false);

        $this->get('/admin/vehicle/edit/'.$vehicle->id)
            ->assertOk()
            ->assertSee('Edit Vehicle', false)
            ->assertSee('VN-'.$suffix, false);

        $this->post('/admin/vehicle/edit/'.$vehicle->id, [
            'vehicle_no' => 'VN-UPD-'.$suffix,
            'vehicle_model' => 'Updated '.$suffix,
            'manufacture_year' => '2021',
            'registration_number' => 'REG-'.$suffix,
            'chasis_number' => 'CHS-'.$suffix,
            'max_seating_capacity' => 45,
            'driver_name' => 'Driver '.$suffix,
            'driver_licence' => 'DL-'.$suffix,
            'driver_contact' => '03001234567',
            'note' => 'Updated note',
        ])->assertRedirect('/admin/vehicle');

        $vehicle->refresh();
        $this->assertSame('VN-UPD-'.$suffix, (string) $vehicle->vehicle_no);
        $this->assertSame(45, (int) $vehicle->max_seating_capacity);

        $photoName = (string) $vehicle->vehicle_photo;
        $this->get('/admin/vehicle/delete/'.$vehicle->id)->assertRedirect('/admin/vehicle');
        $this->assertNull(Vehicle::query()->find($vehicle->id));
        $this->assertSame(0, DB::table('vehicle_routes')->where('vehicle_id', $vehicle->id)->count());
        if ($photoName !== '') {
            $this->assertFileDoesNotExist(public_path('uploads/vehicle_photo/'.$photoName));
        }
        DB::table('transport_route')->where('id', $routeId)->delete();
        $this->cleanupVehicleIds = [];
        $this->cleanupPhotos = [];
    }
}
