<?php

namespace Tests\Feature\Transport;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentTransportFeeAssignTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupPickupIds = [];

    /** @var list<int> */
    private array $cleanupRouteIds = [];

    /** @var list<int> */
    private array $cleanupRoutePickupIds = [];

    /** @var list<int> */
    private array $cleanupMasterIds = [];

    /** @var list<int> */
    private array $cleanupStudentTransportIds = [];

    /** @var list<int> */
    private array $cleanupVehicleIds = [];

    /** @var list<int> */
    private array $cleanupVehicleRouteIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupStudentTransportIds !== []) {
            DB::table('student_fees_deposite')
                ->whereIn('student_transport_fee_id', $this->cleanupStudentTransportIds)
                ->delete();
            DB::table('student_transport_fees')->whereIn('id', $this->cleanupStudentTransportIds)->delete();
            $this->cleanupStudentTransportIds = [];
        }
        if ($this->cleanupMasterIds !== []) {
            DB::table('transport_feemaster')->whereIn('id', $this->cleanupMasterIds)->delete();
            $this->cleanupMasterIds = [];
        }
        if ($this->cleanupRoutePickupIds !== []) {
            DB::table('student_session')->whereIn('route_pickup_point_id', $this->cleanupRoutePickupIds)->update([
                'route_pickup_point_id' => null,
            ]);
            DB::table('route_pickup_point')->whereIn('id', $this->cleanupRoutePickupIds)->delete();
            $this->cleanupRoutePickupIds = [];
        }
        if ($this->cleanupVehicleRouteIds !== []) {
            DB::table('vehicle_routes')->whereIn('id', $this->cleanupVehicleRouteIds)->delete();
            $this->cleanupVehicleRouteIds = [];
        }
        if ($this->cleanupVehicleIds !== []) {
            DB::table('vehicles')->whereIn('id', $this->cleanupVehicleIds)->delete();
            $this->cleanupVehicleIds = [];
        }
        if ($this->cleanupPickupIds !== []) {
            DB::table('pickup_point')->whereIn('id', $this->cleanupPickupIds)->delete();
            $this->cleanupPickupIds = [];
        }
        if ($this->cleanupRouteIds !== []) {
            DB::table('transport_route')->whereIn('id', $this->cleanupRouteIds)->delete();
            $this->cleanupRouteIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->cleanupStudentIds = [];
        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];
        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
            $this->cleanupSectionIds = [];
        }
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

        $token = uniqid('stf', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STF-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Student',
            'surname' => 'TransportFee',
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

    public function test_student_transport_fees_search_months_and_assign(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $sessionId = (int) (DB::table('sch_settings')->value('session_id') ?: 0);
        if ($sessionId <= 0) {
            $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-stf']);
            $sessionId = (int) $session->id;
            DB::table('sch_settings')->limit(1)->update(['session_id' => $sessionId]);
            app(SchoolContext::class)->clearCache();
        }

        $section = Section::query()->create(['section' => 'STF-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'STF-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $routeId = DB::table('transport_route')->insertGetId([
            'route_title' => 'STF Route '.$suffix,
            'no_of_vehicle' => 1,
            'note' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupRouteIds[] = $routeId;

        $vehicleId = DB::table('vehicles')->insertGetId([
            'vehicle_no' => 'STF-'.$suffix,
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
        $this->cleanupVehicleIds[] = $vehicleId;

        $vehRouteId = DB::table('vehicle_routes')->insertGetId([
            'route_id' => $routeId,
            'vehicle_id' => $vehicleId,
        ]);
        $this->cleanupVehicleRouteIds[] = $vehRouteId;

        $pickupId = DB::table('pickup_point')->insertGetId([
            'name' => 'STF Stop '.$suffix,
            'latitude' => '0',
            'longitude' => '0',
        ]);
        $this->cleanupPickupIds[] = $pickupId;

        $routePickupId = DB::table('route_pickup_point')->insertGetId([
            'session_id' => $sessionId,
            'transport_route_id' => $routeId,
            'pickup_point_id' => $pickupId,
            'fees' => 750,
            'destination_distance' => 2.5,
            'pickup_time' => '07:30:00',
            'order_number' => 1,
        ]);
        $this->cleanupRoutePickupIds[] = $routePickupId;

        $startMonth = (int) (DB::table('sch_settings')->value('start_month') ?: 1);
        $monthA = date('F', mktime(0, 0, 0, $startMonth, 1));
        $monthB = date('F', mktime(0, 0, 0, $startMonth + 1, 1));

        $masterA = DB::table('transport_feemaster')->insertGetId([
            'session_id' => $sessionId,
            'month' => $monthA,
            'due_date' => '2026-01-10',
            'fine_amount' => 20,
            'fine_type' => 'fix',
            'fine_percentage' => null,
        ]);
        $masterB = DB::table('transport_feemaster')->insertGetId([
            'session_id' => $sessionId,
            'month' => $monthB,
            'due_date' => '2026-02-10',
            'fine_amount' => null,
            'fine_type' => 'percentage',
            'fine_percentage' => 5,
        ]);
        $this->cleanupMasterIds = [$masterA, $masterB];

        $admissionNo = 'STF'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Assign',
            'lastname' => 'Rider',
            'gender' => 'Male',
            'dob' => '2012-02-02',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03009998877',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->firstOrFail();
        $studentSession->route_pickup_point_id = $routePickupId;
        $studentSession->vehroute_id = $vehRouteId;
        $studentSession->save();

        $this->get('/admin/pickuppoint/student_fees')
            ->assertOk()
            ->assertSee('Student Transport Fees', false);

        $this->post('/admin/pickuppoint/student_fees', [
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])
            ->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('STF Stop '.$suffix, false);

        $months = $this->postJson('/admin/pickuppoint/student_transport_months', [
            'student_session_id' => $studentSession->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', '1')
            ->json();

        $this->assertStringContainsString($monthA, (string) $months['page']);
        $this->assertStringContainsString($monthB, (string) $months['page']);
        $this->assertStringContainsString('750.00', (string) $months['page']);

        $this->postJson('/admin/pickuppoint/add_student_fees', [
            'student_session_id' => $studentSession->id,
            'route_pickup_point_id' => $routePickupId,
            'transport_route_fee' => [$masterA, $masterB],
            'prev_ids' => ['', ''],
            'student_transport_fee_id_'.$masterA => '',
            'student_transport_fee_id_'.$masterB => '',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $assigned = DB::table('student_transport_fees')
            ->where('student_session_id', $studentSession->id)
            ->where('route_pickup_point_id', $routePickupId)
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $assigned);
        $this->cleanupStudentTransportIds = $assigned->pluck('id')->map(fn ($id) => (int) $id)->all();

        $rowA = $assigned->firstWhere('transport_feemaster_id', $masterA);
        $rowB = $assigned->firstWhere('transport_feemaster_id', $masterB);
        $this->assertNotNull($rowA);
        $this->assertNotNull($rowB);

        // Uncheck second month → remove that assignment; keep first.
        $this->postJson('/admin/pickuppoint/add_student_fees', [
            'student_session_id' => $studentSession->id,
            'route_pickup_point_id' => $routePickupId,
            'transport_route_fee' => [$masterA],
            'prev_ids' => [(int) $rowA->id, (int) $rowB->id],
            'student_transport_fee_id_'.$masterA => (int) $rowA->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $remaining = DB::table('student_transport_fees')
            ->where('student_session_id', $studentSession->id)
            ->pluck('transport_feemaster_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->assertSame([$masterA], $remaining);
        $this->cleanupStudentTransportIds = DB::table('student_transport_fees')
            ->where('student_session_id', $studentSession->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
