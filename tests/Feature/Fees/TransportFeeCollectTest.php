<?php

namespace Tests\Feature\Fees;

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

class TransportFeeCollectTest extends TestCase
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
            DB::table('route_pickup_point')->whereIn('id', $this->cleanupRoutePickupIds)->delete();
            $this->cleanupRoutePickupIds = [];
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

        $token = uniqid('tfc', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TFC-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Transport',
            'surname' => 'Collector',
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
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    public function test_transport_fee_ledger_and_collect(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-tfc']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();
        DB::table('permission_group')->where('short_code', 'transport')->update(['is_active' => 1]);

        $section = Section::query()->create(['section' => 'TFS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'TFC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $routeId = DB::table('transport_route')->insertGetId([
            'route_title' => 'Route '.$suffix,
            'no_of_vehicle' => 1,
            'note' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupRouteIds[] = $routeId;

        $pickupId = DB::table('pickup_point')->insertGetId([
            'name' => 'Stop '.$suffix,
            'latitude' => '0',
            'longitude' => '0',
        ]);
        $this->cleanupPickupIds[] = $pickupId;

        $routePickupId = DB::table('route_pickup_point')->insertGetId([
            'session_id' => $session->id,
            'transport_route_id' => $routeId,
            'pickup_point_id' => $pickupId,
            'fees' => 500,
            'destination_distance' => 1.0,
            'pickup_time' => '07:00:00',
            'order_number' => 1,
        ]);
        $this->cleanupRoutePickupIds[] = $routePickupId;

        $masterId = DB::table('transport_feemaster')->insertGetId([
            'session_id' => $session->id,
            'month' => 'january',
            'due_date' => '2020-01-10',
            'fine_amount' => 50,
            'fine_type' => 'fix',
            'fine_percentage' => 0,
        ]);
        $this->cleanupMasterIds[] = $masterId;

        $admissionNo = 'TFC'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Bus',
            'lastname' => 'Rider',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();
        $studentSession->route_pickup_point_id = $routePickupId;
        $studentSession->save();

        $stfId = DB::table('student_transport_fees')->insertGetId([
            'transport_feemaster_id' => $masterId,
            'student_session_id' => $studentSession->id,
            'route_pickup_point_id' => $routePickupId,
            'generated_by' => $this->createdStaffIds[0] ?? null,
        ]);
        $this->cleanupStudentTransportIds[] = $stfId;

        $this->get('/studentfee/addfee/'.$studentSession->id)
            ->assertOk()
            ->assertSee(__('system.transport_fees'), false)
            ->assertSee(number_format(500, 2), false);

        $this->get('/studentfee/collect?'.http_build_query([
            'student_session_id' => $studentSession->id,
            'fee_category' => 'transport',
            'transport_fees_id' => $stfId,
        ]))->assertOk()->assertSee(__('system.transport_fees'), false);

        $this->post('/studentfee/addstudentfee', [
            'fee_category' => 'transport',
            'transport_fees_id' => $stfId,
            'student_session_id' => $studentSession->id,
            'date' => '2026-01-15',
            'amount' => 500,
            'amount_discount' => 0,
            'amount_fine' => 50,
            'payment_mode' => 'Cash',
            'description' => 'Transport Jan',
        ])->assertRedirect('/studentfee/addfee/'.$studentSession->id);

        $deposit = DB::table('student_fees_deposite')
            ->where('student_transport_fee_id', $stfId)
            ->first();
        $this->assertNotNull($deposit);
        $this->assertNull($deposit->student_fees_master_id);
        $detail = json_decode((string) $deposit->amount_detail, true);
        $this->assertSame(500.0, (float) $detail['1']['amount']);
        $this->assertSame(50.0, (float) $detail['1']['amount_fine']);

        $this->get('/studentfee/addfee/'.$studentSession->id)
            ->assertOk()
            ->assertSee('Paid', false);
    }
}
