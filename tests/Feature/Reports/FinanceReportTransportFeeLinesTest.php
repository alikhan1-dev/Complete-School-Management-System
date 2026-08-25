<?php

namespace Tests\Feature\Reports;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Reports\Services\FinanceReportService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceReportTransportFeeLinesTest extends TestCase
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
    private array $cleanupDepositIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupDepositIds !== []) {
            DB::table('student_fees_deposite')->whereIn('id', $this->cleanupDepositIds)->delete();
            $this->cleanupDepositIds = [];
        }
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

        $token = uniqid('frt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FRT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Finance',
            'surname' => 'Transport',
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

    /**
     * @return array{class: SchoolClass, section: Section, student: Student, studentSession: StudentSession, stfId: int, depositId: int}
     */
    private function seedTransportStudent(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2099-frt']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();
        DB::table('permission_group')->where('short_code', 'transport')->update(['is_active' => 1]);

        $suffix = substr(uniqid('', true), -6);
        $section = Section::query()->create(['section' => 'FRT-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'FRT-'.$suffix, 'is_active' => 'yes']);
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

        $admissionNo = 'FRT'.$suffix;
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

        $amountDetail = json_encode([
            '1' => [
                'amount' => 200,
                'amount_discount' => 0,
                'amount_fine' => 0,
                'date' => '2026-01-15',
                'description' => 'Partial transport',
                'payment_mode' => 'Cash',
                'received_by' => $this->createdStaffIds[0] ?? 0,
                'inv_no' => 1,
            ],
        ]);
        $depositId = DB::table('student_fees_deposite')->insertGetId([
            'student_fees_master_id' => null,
            'fee_groups_feetype_id' => null,
            'student_transport_fee_id' => $stfId,
            'amount_detail' => $amountDetail,
        ]);
        $this->cleanupDepositIds[] = $depositId;

        return [
            'class' => $class,
            'section' => $section,
            'student' => $student,
            'studentSession' => $studentSession,
            'stfId' => $stfId,
            'depositId' => $depositId,
        ];
    }

    public function test_finance_reports_include_transport_fee_lines(): void
    {
        $this->actingAsSuperAdmin();
        $seed = $this->seedTransportStudent();
        /** @var FinanceReportService $reports */
        $reports = app(FinanceReportService::class);

        $types = $reports->feeTypes();
        $this->assertTrue($types->contains(fn ($row) => (string) $row->id === 'transport_fees'));

        $statement = $reports->feesStatement(
            $seed['class']->id,
            $seed['section']->id,
            $seed['student']->id
        );
        $this->assertNotEmpty($statement);
        $this->assertNotEmpty($statement[0]['transport_fees']);
        $this->assertSame(500.0, (float) $statement[0]['transport_fees'][0]->fees);

        $balance = $reports->balanceFeesReport($seed['class']->id, $seed['section']->id, 'all');
        $row = collect($balance)->firstWhere('admission_no', $seed['student']->admission_no);
        $this->assertNotNull($row);
        $this->assertSame(500.0, (float) $row->totalfee);
        $this->assertSame(200.0, (float) $row->deposit);
        $this->assertSame(300.0, (float) $row->balance);

        $due = $reports->dueFeesReport($seed['class']->id, $seed['section']->id, 'all');
        $dueRow = collect($due)->firstWhere('admission_no', $seed['student']->admission_no);
        $this->assertNotNull($dueRow);
        $this->assertSame(500.0, (float) $dueRow->totalfee);
        $this->assertSame(200, (int) $dueRow->deposit);

        $collection = $reports->feeCollectionReport(
            '2026-01-01',
            '2026-01-31',
            'transport_fees',
            null,
            $seed['class']->id,
            $seed['section']->id
        );
        $this->assertNotEmpty($collection);
        $this->assertSame('Transport Fees', $collection[0]['type']);
        $this->assertSame(200.0, (float) $collection[0]['amount']);

        $academicOnly = $reports->feeCollectionReport(
            '2026-01-01',
            '2026-01-31',
            999999,
            null,
            $seed['class']->id,
            $seed['section']->id
        );
        $this->assertSame([], $academicOnly);

        $daily = $reports->dailyCollection('2026-01-15', '2026-01-15');
        $dayKey = strtotime('2026-01-15');
        $this->assertArrayHasKey($dayKey, $daily);
        $this->assertSame(200.0, (float) $daily[$dayKey]['amt']);
        $this->assertContains($seed['depositId'], $daily[$dayKey]['student_fees_deposite_ids']);

        $byIds = $reports->feesDepositeByIds([$seed['depositId']]);
        $this->assertCount(1, $byIds);
        $this->assertSame('Transport Fees', $byIds[0]->name);

        $remark = $reports->dueFeesWithRemark($seed['class']->id, $seed['section']->id, '2026-08-26');
        $this->assertArrayHasKey($seed['studentSession']->id, $remark);
        $fees = $remark[$seed['studentSession']->id]['fees'];
        $this->assertNotEmpty($fees);
        $this->assertSame('Transport Fees', $fees[0]['fee_type']);
        $this->assertSame(500.0, (float) $fees[0]['amount']);
        $this->assertSame(200.0, (float) $fees[0]['amount_deposite']);
    }
}
