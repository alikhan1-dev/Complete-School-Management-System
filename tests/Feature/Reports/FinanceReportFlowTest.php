<?php

namespace Tests\Feature\Reports;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\StudentFeesDeposite;
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceReportFlowTest extends TestCase
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
    private array $cleanupFeeTypeIds = [];

    /** @var list<int> */
    private array $cleanupFeeGroupIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupStudentIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                $masterIds = DB::table('student_fees_master')->whereIn('student_session_id', $sessionIds)->pluck('id');
                if ($masterIds->isNotEmpty()) {
                    DB::table('student_fees_deposite')->whereIn('student_fees_master_id', $masterIds)->delete();
                    DB::table('student_fees_master')->whereIn('id', $masterIds)->delete();
                }
            }
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('users')->where('childs', (string) $studentId)->where('role', 'parent')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        foreach ($this->cleanupFeeTypeIds as $id) {
            DB::table('fee_groups_feetype')->where('feetype_id', $id)->delete();
            DB::table('feetype')->where('id', $id)->delete();
        }
        $this->cleanupFeeTypeIds = [];

        foreach ($this->cleanupFeeGroupIds as $id) {
            $sessionGroupIds = DB::table('fee_session_groups')->where('fee_groups_id', $id)->pluck('id');
            if ($sessionGroupIds->isNotEmpty()) {
                DB::table('fee_groups_feetype')->whereIn('fee_session_group_id', $sessionGroupIds)->delete();
                DB::table('fee_session_groups')->whereIn('id', $sessionGroupIds)->delete();
            }
            DB::table('fee_groups')->where('id', $id)->delete();
        }
        $this->cleanupFeeGroupIds = [];

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
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

        $token = uniqid('frpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FRPT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'FinReport',
            'surname' => 'Admin',
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
     * @return array{student: Student, class: SchoolClass, section: Section, studentSession: StudentSession, feeTypeRow: FeeGroupFeetype}
     */
    private function seedFeeContext(): array
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-fr']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'FRPTS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'FRPTC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $type = FeeType::query()->create([
            'type' => 'FRT-'.$suffix, 'code' => 'FRC-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'FRG-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $this->cleanupFeeTypeIds[] = $type->id;
        $this->cleanupFeeGroupIds[] = $group->id;

        $sessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id, 'session_id' => $session->id, 'is_active' => 'no',
        ]);
        $feeTypeRow = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $session->id,
            'amount' => 1000,
            'due_date' => now()->subDay()->toDateString(),
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $admissionNo = 'FRPTADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Fin',
            'lastname' => 'Pupil',
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
        $studentSession = StudentSession::query()->where('student_id', $student->id)->where('session_id', $session->id)->firstOrFail();

        StudentFeesMaster::query()->create([
            'is_system' => 0,
            'student_session_id' => $studentSession->id,
            'fee_session_group_id' => $sessionGroup->id,
            'amount' => 0,
            'is_active' => 'no',
        ]);
        $master = StudentFeesMaster::query()
            ->where('student_session_id', $studentSession->id)
            ->where('fee_session_group_id', $sessionGroup->id)
            ->firstOrFail();

        $today = now()->toDateString();
        StudentFeesDeposite::query()->create([
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_transport_fee_id' => null,
            'amount_detail' => json_encode([
                '1' => [
                    'date' => $today,
                    'amount' => 400,
                    'amount_discount' => 0,
                    'amount_fine' => 10,
                    'payment_mode' => 'Cash',
                    'description' => 'partial',
                    'collected_by' => 'test',
                ],
            ]),
            'is_active' => 'no',
        ]);

        return [
            'student' => $student,
            'class' => $class,
            'section' => $section,
            'studentSession' => $studentSession,
            'feeTypeRow' => $feeTypeRow,
        ];
    }

    public function test_finance_reports_require_staff_auth(): void
    {
        $this->get('/financereports/finance')->assertRedirect();
        $this->get('/financereports/studentacademicreport')->assertRedirect();
        $this->get('/financereports/reportbyname')->assertRedirect();
        $this->get('/financereports/reportduefees')->assertRedirect();
        $this->get('/financereports/reportdailycollection')->assertRedirect();
        $this->get('/financereports/collection_report')->assertRedirect();
        $this->get('/financereports/onlinefees_report')->assertRedirect();
    }

    public function test_finance_report_slice_one_flows(): void
    {
        $ctx = $this->seedFeeContext();
        $today = now()->toDateString();

        $this->get('/financereports/finance')
            ->assertOk()
            ->assertSee('financereports/studentacademicreport', false)
            ->assertSee('financereports/reportdailycollection', false);

        $this->post('/financereports/studentacademicreport', [])
            ->assertSessionHasErrors(['search_type']);

        $balance = $this->post('/financereports/studentacademicreport', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'search_type' => 'balance',
        ])->assertOk();
        $balance->assertSee($ctx['student']->admission_no, false)
            ->assertSee('Fin Pupil', false)
            ->assertSee('600.00', false);

        $this->post('/financereports/reportbyname', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'student_id' => $ctx['student']->id,
        ])->assertOk()
            ->assertSee($ctx['student']->admission_no, false)
            ->assertSee('400.00', false);

        $this->post('/financereports/reportduefees', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
        ])->assertOk()
            ->assertSee($ctx['student']->admission_no, false);

        $this->post('/financereports/printreportduefees', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
        ])->assertOk()
            ->assertJson(['status' => 1]);

        $this->post('/financereports/reportdailycollection', [])
            ->assertSessionHasErrors(['date_from', 'date_to']);

        $daily = $this->post('/financereports/reportdailycollection', [
            'date_from' => $today,
            'date_to' => $today,
        ])->assertOk();
        $this->assertStringContainsString('410.00', $daily->getContent());
    }

    public function test_finance_report_collection_and_online_flows(): void
    {
        $ctx = $this->seedFeeContext();
        $staffId = (int) end($this->createdStaffIds);
        $today = now()->toDateString();

        $deposit = StudentFeesDeposite::query()
            ->whereIn('student_fees_master_id', StudentFeesMaster::query()
                ->where('student_session_id', $ctx['studentSession']->id)
                ->pluck('id'))
            ->firstOrFail();

        $deposit->amount_detail = json_encode([
            '1' => [
                'date' => $today,
                'amount' => 400,
                'amount_discount' => 0,
                'amount_fine' => 10,
                'payment_mode' => 'Cash',
                'description' => 'partial',
                'collected_by' => 'test',
                'received_by' => $staffId,
                'inv_no' => 1,
            ],
            '2' => [
                'date' => $today,
                'amount' => 50,
                'amount_discount' => 0,
                'amount_fine' => 0,
                'payment_mode' => 'Card',
                'description' => 'online pay',
                'collected_by' => 'gateway',
                'received_by' => $staffId,
                'inv_no' => 2,
            ],
        ]);
        $deposit->save();

        $this->get('/financereports/collection_report')->assertOk();
        $this->get('/financereports/onlinefees_report')->assertOk();

        $this->post('/financereports/collection_report', [])
            ->assertSessionHasErrors(['search_type']);

        $collection = $this->post('/financereports/collection_report', [
            'search_type' => 'today',
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'collect_by' => $staffId,
            'group' => 'mode',
        ])->assertOk();
        $collection->assertSee($ctx['student']->admission_no, false)
            ->assertSee('400.00', false)
            ->assertSee('50.00', false)
            ->assertSee('Cash', false)
            ->assertSee('Card', false)
            ->assertSee(__('system.sub_total'), false);

        $this->post('/financereports/onlinefees_report', [])
            ->assertSessionHasErrors(['search_type']);

        $online = $this->post('/financereports/onlinefees_report', [
            'search_type' => 'today',
        ])->assertOk();
        $online->assertSee($ctx['student']->admission_no, false)
            ->assertSee('50.00', false)
            ->assertSee('Card', false)
            ->assertSee('online pay', false)
            ->assertDontSee('Cash');
    }
}
