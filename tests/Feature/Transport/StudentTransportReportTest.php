<?php

namespace Tests\Feature\Transport;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Transport\Models\PickupPoint;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentTransportReportTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupRouteIds = [];

    /** @var list<int> */
    private array $cleanupVehicleIds = [];

    /** @var list<int> */
    private array $cleanupPointIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupStudentIds !== []) {
            DB::table('student_session')->whereIn('student_id', $this->cleanupStudentIds)->delete();
            DB::table('students')->whereIn('id', $this->cleanupStudentIds)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupRouteIds !== []) {
            DB::table('route_pickup_point')->whereIn('transport_route_id', $this->cleanupRouteIds)->delete();
            DB::table('vehicle_routes')->whereIn('route_id', $this->cleanupRouteIds)->delete();
            DB::table('transport_route')->whereIn('id', $this->cleanupRouteIds)->delete();
        }
        $this->cleanupRouteIds = [];

        if ($this->cleanupVehicleIds !== []) {
            DB::table('vehicle_routes')->whereIn('vehicle_id', $this->cleanupVehicleIds)->delete();
            DB::table('vehicles')->whereIn('id', $this->cleanupVehicleIds)->delete();
        }
        $this->cleanupVehicleIds = [];

        if ($this->cleanupPointIds !== []) {
            DB::table('route_pickup_point')->whereIn('pickup_point_id', $this->cleanupPointIds)->delete();
            DB::table('pickup_point')->whereIn('id', $this->cleanupPointIds)->delete();
        }
        $this->cleanupPointIds = [];

        if ($this->cleanupClassIds !== []) {
            DB::table('class_sections')->whereIn('class_id', $this->cleanupClassIds)->delete();
            DB::table('classes')->whereIn('id', $this->cleanupClassIds)->delete();
        }
        $this->cleanupClassIds = [];

        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
        }
        $this->cleanupSectionIds = [];

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

        $token = uniqid('strpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Transport',
            'surname' => 'Report',
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

    public function test_student_transport_report_search_and_route_options(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-str']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'STRS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'STRC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $route = TransportRoute::query()->create([
            'route_title' => 'Report Route '.$suffix,
            'no_of_vehicle' => null,
            'note' => '',
            'is_active' => 'no',
        ]);
        $this->cleanupRouteIds[] = $route->id;

        $vehicle = Vehicle::query()->create([
            'vehicle_no' => 'VR-'.$suffix,
            'vehicle_model' => 'Van',
            'vehicle_photo' => '',
            'manufacture_year' => '',
            'registration_number' => '',
            'chasis_number' => '',
            'max_seating_capacity' => 20,
            'driver_name' => 'Driver '.$suffix,
            'driver_licence' => '',
            'driver_contact' => '03009998877',
            'note' => '',
        ]);
        $this->cleanupVehicleIds[] = $vehicle->id;

        $vehrouteId = (int) DB::table('vehicle_routes')->insertGetId([
            'route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $point = PickupPoint::query()->create([
            'name' => 'Stop '.$suffix,
            'latitude' => '',
            'longitude' => '',
        ]);
        $this->cleanupPointIds[] = $point->id;

        $routePickupId = (int) DB::table('route_pickup_point')->insertGetId([
            'session_id' => $session->id,
            'transport_route_id' => $route->id,
            'pickup_point_id' => $point->id,
            'fees' => 150.50,
            'destination_distance' => '3.5',
            'pickup_time' => '07:30:00',
            'order_number' => 1,
        ]);

        $admissionNo = 'STRADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Transport',
            'lastname' => 'Rider',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
            'father_name' => 'Father '.$suffix,
            'mobileno' => '03005554433',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->update([
                'vehroute_id' => $vehrouteId,
                'route_pickup_point_id' => $routePickupId,
            ]);

        $this->get('/admin/route/studenttransportdetails')
            ->assertOk()
            ->assertSee('Student Transport Report', false)
            ->assertSee('Select Criteria', false)
            ->assertDontSee('Transport Rider', false);

        $this->post('/admin/route/studenttransportdetails', [
            'search' => 'search_filter',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'transport_route_id' => $route->id,
            'pickup_point_id' => $point->id,
            'vehicle_id' => $vehicle->id,
        ])
            ->assertOk()
            ->assertSee('Transport Rider', false)
            ->assertSee('Report Route '.$suffix, false)
            ->assertSee('VR-'.$suffix, false)
            ->assertSee('Stop '.$suffix, false)
            ->assertSee('Driver '.$suffix, false)
            ->assertSee('150.50', false);

        $this->postJson('/admin/pickuppoint/getpickuppointsbyroute', [
            'transport_route_id' => $route->id,
        ])
            ->assertOk()
            ->assertJsonPath('vehicle_route_pickups.0.pickup_point', 'Stop '.$suffix)
            ->assertJsonPath('routes_vehicle.0.vehicle_no', 'VR-'.$suffix);
    }
}
