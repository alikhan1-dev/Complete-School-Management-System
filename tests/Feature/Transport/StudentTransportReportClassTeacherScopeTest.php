<?php

namespace Tests\Feature\Transport;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Transport\Models\PickupPoint;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentTransportReportClassTeacherScopeTest extends TestCase
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

    /** @var list<int> */
    private array $cleanupClassTeacherIds = [];

    /** @var list<int> */
    private array $cleanupRolePermissionIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupStudentIds !== []) {
            DB::table('student_session')->whereIn('student_id', $this->cleanupStudentIds)->delete();
            DB::table('students')->whereIn('id', $this->cleanupStudentIds)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
        }
        $this->cleanupClassTeacherIds = [];

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

        if ($this->cleanupRolePermissionIds !== []) {
            DB::table('roles_permissions')->whereIn('id', $this->cleanupRolePermissionIds)->delete();
        }
        $this->cleanupRolePermissionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        DB::table('sch_settings')->limit(1)->update(['class_teacher' => $this->previousClassTeacherSetting]);
        app(SchoolContext::class)->clearCache();

        parent::tearDown();
    }

    private function ensureTeacherPrivilege(): void
    {
        $permCatId = (int) DB::table('permission_category')->where('short_code', 'transport_report')->value('id');
        $this->assertGreaterThan(0, $permCatId);

        $existing = DB::table('roles_permissions')
            ->where('role_id', 2)
            ->where('perm_cat_id', $permCatId)
            ->first();

        $payload = ['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1];

        if ($existing) {
            DB::table('roles_permissions')->where('id', $existing->id)->update($payload);
        } else {
            $this->cleanupRolePermissionIds[] = DB::table('roles_permissions')->insertGetId(array_merge([
                'role_id' => 2,
                'perm_cat_id' => $permCatId,
            ], $payload));
        }
    }

    private function insertStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Transport',
            'surname' => 'Teacher',
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
            'basic_salary' => 0,
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
        DB::table('staff_roles')->insert(['staff_id' => $staffId, 'role_id' => $roleId, 'is_active' => 1]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    /**
     * @return array{session:AcademicSession,classA:SchoolClass,sectionA:Section,classB:SchoolClass,sectionB:Section,suffix:string}
     */
    private function seedTransportStudents(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2099-trct']);
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();

        $adminRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $admin = $this->insertStaff($adminRoleId, 'tradm');
        $this->actingAs($admin, 'staff');

        $suffix = uniqid();
        $sectionA = Section::query()->create(['section' => 'TRSA-'.$suffix, 'is_active' => 'yes']);
        $sectionB = Section::query()->create(['section' => 'TRSB-'.$suffix, 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'TRCA-'.$suffix, 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'TRCB-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $sectionA->id;
        $this->cleanupSectionIds[] = $sectionB->id;
        $this->cleanupClassIds[] = $classA->id;
        $this->cleanupClassIds[] = $classB->id;

        ClassSection::query()->create(['class_id' => $classA->id, 'section_id' => $sectionA->id, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $classB->id, 'section_id' => $sectionB->id, 'is_active' => 'yes']);

        $route = TransportRoute::query()->create([
            'route_title' => 'CT Route '.$suffix,
            'no_of_vehicle' => null,
            'note' => '',
            'is_active' => 'no',
        ]);
        $this->cleanupRouteIds[] = $route->id;

        $vehicle = Vehicle::query()->create([
            'vehicle_no' => 'CT-'.$suffix,
            'vehicle_model' => 'Van',
            'vehicle_photo' => '',
            'manufacture_year' => '',
            'registration_number' => '',
            'chasis_number' => '',
            'max_seating_capacity' => 20,
            'driver_name' => 'Driver',
            'driver_licence' => '',
            'driver_contact' => '',
            'note' => '',
        ]);
        $this->cleanupVehicleIds[] = $vehicle->id;

        $vehrouteId = (int) DB::table('vehicle_routes')->insertGetId([
            'route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $point = PickupPoint::query()->create(['name' => 'Stop '.$suffix, 'latitude' => '', 'longitude' => '']);
        $this->cleanupPointIds[] = $point->id;

        $routePickupId = (int) DB::table('route_pickup_point')->insertGetId([
            'session_id' => $session->id,
            'transport_route_id' => $route->id,
            'pickup_point_id' => $point->id,
            'fees' => 100,
            'destination_distance' => '1',
            'pickup_time' => '07:00:00',
            'order_number' => 1,
        ]);

        foreach ([
            ['class' => $classA, 'section' => $sectionA, 'admission' => 'TRIN'.$suffix, 'name' => 'InScope Rider'],
            ['class' => $classB, 'section' => $sectionB, 'admission' => 'TROUT'.$suffix, 'name' => 'OutScope Rider'],
        ] as $row) {
            $this->post('/student/create', [
                'admission_no' => $row['admission'],
                'firstname' => $row['name'],
                'lastname' => 'Kid',
                'gender' => 'Male',
                'dob' => '2012-01-01',
                'class_id' => $row['class']->id,
                'section_id' => $row['section']->id,
                'guardian_is' => 'father',
                'guardian_name' => 'Dad',
                'guardian_phone' => '03001112233',
            ])->assertRedirect();

            $student = Student::query()->where('admission_no', $row['admission'])->firstOrFail();
            $this->cleanupStudentIds[] = $student->id;
            DB::table('student_session')
                ->where('student_id', $student->id)
                ->where('session_id', $session->id)
                ->update([
                    'vehroute_id' => $vehrouteId,
                    'route_pickup_point_id' => $routePickupId,
                ]);
        }

        return compact('session', 'classA', 'sectionA', 'classB', 'sectionB', 'suffix');
    }

    public function test_student_transport_report_respects_class_teacher_scope(): void
    {
        $fixtures = $this->seedTransportStudents();
        $this->ensureTeacherPrivilege();

        $emptyTeacher = $this->insertStaff(2, 'trempty');
        $this->actingAs($emptyTeacher, 'staff');

        $this->post('/admin/route/studenttransportdetails', ['search' => 'search_filter'])
            ->assertOk()
            ->assertDontSee('InScope Rider', false)
            ->assertDontSee('OutScope Rider', false);

        $scopedTeacher = $this->insertStaff(2, 'trct');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $scopedTeacher->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->actingAs($scopedTeacher, 'staff');

        $page = $this->get('/admin/route/studenttransportdetails')->assertOk();
        $page->assertSee('TRCA-'.$fixtures['suffix'], false);
        $page->assertDontSee('TRCB-'.$fixtures['suffix'], false);

        $this->post('/admin/route/studenttransportdetails', [
            'search' => 'search_filter',
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])
            ->assertOk()
            ->assertSee('InScope Rider', false)
            ->assertDontSee('OutScope Rider', false);

        $this->post('/admin/route/studenttransportdetails', [
            'search' => 'search_filter',
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])
            ->assertOk()
            ->assertDontSee('OutScope Rider', false);

        $this->get('/migration-status/transport')
            ->assertOk()
            ->assertJsonPath('slices.student_transport_report_class_teacher', 'done');
    }
}
