<?php

namespace Tests\Feature\Payments;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentFeeGatewayPersistTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupGatewayInsIds = [];

    /** @var list<int> */
    private array $cleanupProcessingIds = [];

    private ?string $previousActivePaymentType = null;

    protected function tearDown(): void
    {
        if ($this->cleanupProcessingIds !== []) {
            DB::table('student_fees_processing')->whereIn('id', $this->cleanupProcessingIds)->delete();
            $this->cleanupProcessingIds = [];
        }
        if ($this->cleanupGatewayInsIds !== []) {
            DB::table('gateway_ins_response')->whereIn('gateway_ins_id', $this->cleanupGatewayInsIds)->delete();
            DB::table('gateway_ins')->whereIn('id', $this->cleanupGatewayInsIds)->delete();
            $this->cleanupGatewayInsIds = [];
        }

        if ($this->previousActivePaymentType !== null) {
            DB::table('payment_settings')->update(['is_active' => 'no']);
            if ($this->previousActivePaymentType !== '') {
                DB::table('payment_settings')
                    ->where('payment_type', $this->previousActivePaymentType)
                    ->update(['is_active' => 'yes']);
            }
            $this->previousActivePaymentType = null;
        }

        foreach ($this->cleanupStudentIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                $masterIds = DB::table('student_fees_master')->whereIn('student_session_id', $sessionIds)->pluck('id');
                if ($masterIds->isNotEmpty()) {
                    DB::table('student_fees_processing')->whereIn('student_fees_master_id', $masterIds)->delete();
                    DB::table('student_fees_deposite')->whereIn('student_fees_master_id', $masterIds)->delete();
                    DB::table('student_fees_master')->whereIn('id', $masterIds)->delete();
                }
            }
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->cleanupStudentIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function activatePaymentMethod(): string
    {
        $active = DB::table('payment_settings')->where('is_active', 'yes')->value('payment_type');
        $this->previousActivePaymentType = $active ? (string) $active : '';

        $row = DB::table('payment_settings')->orderBy('id')->first();
        $this->assertNotNull($row, 'payment_settings must have at least one gateway row');

        DB::table('payment_settings')->update(['is_active' => 'no']);
        DB::table('payment_settings')->where('id', $row->id)->update(['is_active' => 'yes']);

        return (string) $row->payment_type;
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('gwp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'GWP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Gateway',
            'surname' => 'Persist',
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

    /**
     * @return array{student:Student,studentSession:StudentSession,master:StudentFeesMaster,feeTypeRow:FeeGroupFeetype,activeType:string}
     */
    private function seedStudentWithFeeLine(): array
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $activeType = $this->activatePaymentMethod();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'SG-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'CG-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $type = FeeType::query()->create([
            'type' => 'TG-'.$suffix, 'code' => 'CG-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'GG-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $sessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id, 'session_id' => $session->id, 'is_active' => 'no',
        ]);
        $feeTypeRow = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $session->id,
            'amount' => 900,
            'due_date' => '2026-01-01',
            'fine_type' => 'fix',
            'fine_percentage' => 0,
            'fine_amount' => 25,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $admissionNo = 'ADG'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Gateway',
            'lastname' => 'Persist',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03007776655',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;
        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();

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

        $user = PortalUser::query()->where('user_id', $student->id)->where('role', 'student')->firstOrFail();
        $user->login_token = 'gtok'.$suffix;
        $user->save();
        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => (int) $studentSession->id]]);

        return compact('student', 'studentSession', 'master', 'feeTypeRow', 'activeType');
    }

    public function test_gateway_show_persists_gateway_ins_and_processing_rows(): void
    {
        $ctx = $this->seedStudentWithFeeLine();

        $this->post('/user/gateway/payment/pay', [
            'submit_mode' => 'online_payment',
            'fee_category' => 'fees',
            'student_id' => $ctx['student']->id,
            'student_fees_master_id' => $ctx['master']->id,
            'fee_groups_feetype_id' => $ctx['feeTypeRow']->id,
            'student_transport_fee_id' => 0,
            'fee_discount' => 0,
            'fee_amount_single' => 900,
            'fine_amount_single' => 25,
        ])->assertRedirect();

        $this->get('/user/gateway/'.$ctx['activeType'])
            ->assertOk()
            ->assertSee(__('system.transaction_id'), false);

        $gatewayRow = DB::table('gateway_ins')
            ->where('module_type', 'fees')
            ->where('gateway_name', strtolower($ctx['activeType']))
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($gatewayRow);
        $this->cleanupGatewayInsIds[] = (int) $gatewayRow->id;
        $this->assertSame('processing', $gatewayRow->payment_status);

        $processing = DB::table('student_fees_processing')
            ->where('gateway_ins_id', $gatewayRow->id)
            ->where('student_fees_master_id', $ctx['master']->id)
            ->where('fee_groups_feetype_id', $ctx['feeTypeRow']->id)
            ->first();

        $this->assertNotNull($processing);
        $this->cleanupProcessingIds[] = (int) $processing->id;

        $detail = json_decode((string) $processing->amount_detail, true);
        $this->assertSame(900.0, (float) ($detail['amount'] ?? 0));
        $this->assertSame(25.0, (float) ($detail['amount_fine'] ?? 0));
        $this->assertStringContainsString((string) $gatewayRow->unique_id, (string) ($detail['description'] ?? ''));

        $this->get('/user/user/getfees')
            ->assertOk()
            ->assertSee('btn_get_processing_fees', false);

        FeeGroupFeetype::query()->where('id', $ctx['feeTypeRow']->id)->delete();
        FeeSessionGroup::query()->where('id', $ctx['feeTypeRow']->fee_session_group_id)->delete();
        FeeGroup::query()->where('id', $ctx['feeTypeRow']->fee_groups_id)->delete();
        FeeType::query()->where('id', $ctx['feeTypeRow']->feetype_id)->delete();
    }

    public function test_gateway_show_persist_is_idempotent(): void
    {
        $ctx = $this->seedStudentWithFeeLine();

        $this->post('/user/gateway/payment/pay', [
            'submit_mode' => 'online_payment',
            'fee_category' => 'fees',
            'student_id' => $ctx['student']->id,
            'student_fees_master_id' => $ctx['master']->id,
            'fee_groups_feetype_id' => $ctx['feeTypeRow']->id,
            'student_transport_fee_id' => 0,
            'fee_discount' => 0,
            'fee_amount_single' => 500,
            'fine_amount_single' => 0,
        ])->assertRedirect();

        $this->get('/user/gateway/'.$ctx['activeType'])->assertOk();
        $this->get('/user/gateway/'.$ctx['activeType'])->assertOk();

        $count = DB::table('gateway_ins')
            ->where('module_type', 'fees')
            ->where('gateway_name', strtolower($ctx['activeType']))
            ->count();

        $this->assertSame(1, $count);

        $gatewayId = (int) DB::table('gateway_ins')
            ->where('module_type', 'fees')
            ->where('gateway_name', strtolower($ctx['activeType']))
            ->value('id');
        $this->cleanupGatewayInsIds[] = $gatewayId;

        $processingIds = DB::table('student_fees_processing')
            ->where('gateway_ins_id', $gatewayId)
            ->pluck('id')
            ->all();
        $this->cleanupProcessingIds = array_merge($this->cleanupProcessingIds, array_map('intval', $processingIds));

        FeeGroupFeetype::query()->where('id', $ctx['feeTypeRow']->id)->delete();
        FeeSessionGroup::query()->where('id', $ctx['feeTypeRow']->fee_session_group_id)->delete();
        FeeGroup::query()->where('id', $ctx['feeTypeRow']->fee_groups_id)->delete();
        FeeType::query()->where('id', $ctx['feeTypeRow']->feetype_id)->delete();
    }

    public function test_gateway_ins_callback_stub_logs_response(): void
    {
        $uniqueId = 'CB-'.uniqid();
        $gatewayId = DB::table('gateway_ins')->insertGetId([
            'online_admission_id' => null,
            'gateway_name' => 'toyyibpay',
            'module_type' => 'fees',
            'unique_id' => $uniqueId,
            'parameter_details' => '{}',
            'payment_status' => 'processing',
        ]);
        $this->cleanupGatewayInsIds[] = $gatewayId;

        $this->post('/gateway_ins/toyyibpay', [
            'order_id' => $uniqueId,
            'refno' => 'REF123',
            'status' => '1',
        ])->assertOk();

        $row = DB::table('gateway_ins')->where('id', $gatewayId)->first();
        $this->assertSame('1', (string) $row->payment_status);

        $response = DB::table('gateway_ins_response')->where('gateway_ins_id', $gatewayId)->first();
        $this->assertNotNull($response);
        $this->assertStringContainsString($uniqueId, (string) $response->posted_data);
    }
}
