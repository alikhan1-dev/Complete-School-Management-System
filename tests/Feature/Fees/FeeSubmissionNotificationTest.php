<?php

namespace Tests\Feature\Fees;

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
use App\Modules\Fees\Services\FeeReceiptTokenService;
use App\Modules\Fees\Services\FeeSubmissionNotificationService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeSubmissionNotificationTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                $masterIds = DB::table('student_fees_master')->whereIn('student_session_id', $sessionIds)->pluck('id');
                if ($masterIds->isNotEmpty()) {
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
        $this->cleanupIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): Staff
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('fsn', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FSN-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
            'surname' => 'FeeNotify',
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
        $staff = Staff::query()->findOrFail($staffId);
        $this->actingAs($staff, 'staff');

        return $staff;
    }

    public function test_receipt_token_round_trip(): void
    {
        $tokens = app(FeeReceiptTokenService::class);
        $encoded = $tokens->encode([
            'invoice_id' => 12,
            'fee_category' => 'fees',
            'transport_fees_id' => 0,
            'fee_groups_feetype_id' => 34,
            'student_fees_master_id' => 56,
            'fee_session_group_id' => 78,
            'type' => 'staff',
            'created_by' => 9,
        ]);

        $decoded = $tokens->decode($encoded);
        $this->assertNotNull($decoded);
        $this->assertSame(12, $decoded['invoice_id']);
        $this->assertSame('fees', $decoded['fee_category']);
        $this->assertSame(34, $decoded['fee_groups_feetype_id']);
        $this->assertSame(56, $decoded['student_fees_master_id']);
        $this->assertSame(78, $decoded['fee_session_group_id']);
        $this->assertSame('staff', $decoded['type']);
        $this->assertSame(9, $decoded['created_by']);
    }

    public function test_collect_queues_fee_submission_payload_and_download_receipt_works(): void
    {
        $staff = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $type = FeeType::query()->create([
            'type' => 'T-'.$suffix, 'code' => 'C-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'G-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $sessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id, 'session_id' => $session->id, 'is_active' => 'no',
        ]);
        $feeTypeRow = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $session->id,
            'amount' => 1000,
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $admissionNo = 'ADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Notify',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112222',
            'father_name' => 'Father Notify',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupIds[] = $student->id;
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

        $queued = app(FeeSubmissionNotificationService::class)->queueSingle([
            'invoice_id' => 0,
            'sub_invoice_id' => 1,
            'fee_category' => 'fees',
            'student_session_id' => (int) $studentSession->id,
            'fee_groups_feetype_id' => (int) $feeTypeRow->id,
            'student_fees_master_id' => (int) $master->id,
            'fee_session_group_id' => (int) $sessionGroup->id,
            'staff_id' => (int) $staff->id,
            'guardian_phone' => '03001112222',
        ]);

        $this->assertTrue($queued['deferred']);
        $this->assertArrayHasKey('fee_receipt_url', $queued['payload']);
        $this->assertStringContainsString('download-receipt/', (string) $queued['payload']['fee_receipt_url']);
        $this->assertSame('03001112222', $queued['payload']['contact_no']);
        $this->assertSame('G-'.$suffix, $queued['payload']['fee_group_name']);

        $this->post('/studentfee/addstudentfee', [
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'fee_session_group_id' => $sessionGroup->id,
            'student_session_id' => $studentSession->id,
            'date' => '2026-08-12',
            'amount' => 500,
            'amount_discount' => 0,
            'amount_fine' => 0,
            'payment_mode' => 'Cash',
            'description' => 'Notify collect',
            'guardian_phone' => '03001112222',
        ])->assertRedirect('/studentfee/addfee/'.$studentSession->id);

        $deposit = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $feeTypeRow->id)
            ->firstOrFail();

        $token = app(FeeReceiptTokenService::class)->encode([
            'invoice_id' => $deposit->id,
            'fee_category' => 'fees',
            'transport_fees_id' => 0,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_fees_master_id' => $master->id,
            'fee_session_group_id' => $sessionGroup->id,
            'type' => 'staff',
            'created_by' => $staff->id,
        ]);

        $this->get('/download-receipt/'.$token)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get('/download-receipt/not-a-valid-token')->assertStatus(400);
    }
}
