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
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OfflinePaymentFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupOfflineIds = [];

    /** @var list<string> */
    private array $cleanupAttachmentFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupOfflineIds as $offlineId) {
            DB::table('offline_fees_payments')->where('id', $offlineId)->delete();
        }
        $this->cleanupOfflineIds = [];

        foreach ($this->cleanupAttachmentFiles as $path) {
            if (File::isFile($path)) {
                File::delete($path);
            }
        }
        $this->cleanupAttachmentFiles = [];

        foreach ($this->cleanupStudentIds as $studentId) {
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
        $this->cleanupStudentIds = [];

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

        $token = uniqid('off', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OFF-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Offline',
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

    public function test_offline_payment_list_approve_and_reject(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-off']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'OFS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'OFC-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $type = FeeType::query()->create([
            'type' => 'Tuition-'.$suffix,
            'code' => 'T-'.$suffix,
            'description' => '',
            'is_system' => 0,
            'nature' => '',
            'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'OG-'.$suffix,
            'description' => '',
            'is_system' => 0,
            'nature' => '',
            'is_active' => 'no',
        ]);
        $sessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id,
            'session_id' => $session->id,
            'is_active' => 'no',
        ]);
        $feeTypeRow = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $session->id,
            'amount' => 1000,
            'due_date' => '2099-12-31',
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $admissionNo = 'OFFADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Offline',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000000',
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

        $dir = public_path('uploads/offline_payments');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $attachment = 'off-proof-'.$suffix.'.txt';
        $attachmentPath = $dir.DIRECTORY_SEPARATOR.$attachment;
        File::put($attachmentPath, 'proof');
        $this->cleanupAttachmentFiles[] = $attachmentPath;

        $approveId = DB::table('offline_fees_payments')->insertGetId([
            'student_session_id' => $studentSession->id,
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_transport_fee_id' => null,
            'payment_date' => '2026-08-10',
            'bank_from' => 'Test Bank',
            'bank_account_transferred' => 'ACC-1',
            'reference' => 'REF-'.$suffix,
            'amount' => 500,
            'submit_date' => '2026-08-11 10:00:00',
            'attachment' => $attachment,
            'is_active' => '0',
        ]);
        $this->cleanupOfflineIds[] = $approveId;

        $rejectId = DB::table('offline_fees_payments')->insertGetId([
            'student_session_id' => $studentSession->id,
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_transport_fee_id' => null,
            'payment_date' => '2026-08-10',
            'bank_from' => 'Test Bank',
            'bank_account_transferred' => 'ACC-2',
            'reference' => 'REF2-'.$suffix,
            'amount' => 200,
            'submit_date' => '2026-08-11 11:00:00',
            'is_active' => '0',
        ]);
        $this->cleanupOfflineIds[] = $rejectId;

        $this->get('/admin/offlinepayment')
            ->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee((string) $approveId, false);

        $this->get('/admin/offlinepayment/view/'.$approveId)
            ->assertOk()
            ->assertSee('Test Bank', false)
            ->assertSee('Approve', false);

        $this->get('/admin/offlinepayment/download/'.$approveId)
            ->assertOk();

        $this->post('/admin/offlinepayment/update', [
            'offline_fees_payment_id' => $approveId,
            'payment_status' => 1,
            'amount' => 500,
            'fine' => 0,
            'reply' => 'Approved OK',
        ])->assertRedirect('/admin/offlinepayment');

        $approved = DB::table('offline_fees_payments')->where('id', $approveId)->first();
        $this->assertNotNull($approved);
        $this->assertSame('1', (string) $approved->is_active);
        $this->assertNotEmpty($approved->invoice_id);
        $this->assertSame('Approved OK', (string) $approved->reply);

        $deposit = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $feeTypeRow->id)
            ->firstOrFail();
        $detail = json_decode((string) $deposit->amount_detail, true);
        $this->assertIsArray($detail);
        $this->assertArrayHasKey('1', $detail);
        $this->assertEquals(500.0, (float) $detail['1']['amount']);
        $this->assertSame('bank_payment', $detail['1']['payment_mode']);
        $this->assertStringContainsString('Request ID : '.$approveId, (string) $detail['1']['description']);

        $this->post('/admin/offlinepayment/update', [
            'offline_fees_payment_id' => $rejectId,
            'payment_status' => 2,
            'amount' => 200,
            'fine' => 0,
            'reply' => 'Rejected',
        ])->assertRedirect('/admin/offlinepayment');

        $rejected = DB::table('offline_fees_payments')->where('id', $rejectId)->first();
        $this->assertNotNull($rejected);
        $this->assertSame('2', (string) $rejected->is_active);
        $this->assertNull($rejected->invoice_id);
        $this->assertSame('Rejected', (string) $rejected->reply);

        $depositCount = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $feeTypeRow->id)
            ->count();
        $this->assertSame(1, $depositCount);
    }
}
