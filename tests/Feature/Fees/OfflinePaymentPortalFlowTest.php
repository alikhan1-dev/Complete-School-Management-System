<?php

namespace Tests\Feature\Fees;

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
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OfflinePaymentPortalFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupOfflineIds = [];

    /** @var list<string> */
    private array $cleanupAttachmentFiles = [];

    private int $previousOfflineFlag = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousOfflineFlag = (int) (DB::table('sch_settings')->value('is_offline_fee_payment') ?? 0);
        DB::table('sch_settings')->limit(1)->update([
            'is_offline_fee_payment' => 1,
            'offline_bank_payment_instruction' => '<p>Pay to school bank</p>',
        ]);
        app(SchoolContext::class)->clearCache();
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupOfflineIds as $offlineId) {
            $row = DB::table('offline_fees_payments')->where('id', $offlineId)->first();
            if ($row && filled($row->attachment ?? null)) {
                $path = public_path('uploads/offline_payments/'.basename((string) $row->attachment));
                if (File::isFile($path)) {
                    File::delete($path);
                }
            }
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

        if ($this->cleanupUserIds !== []) {
            DB::table('users')->whereIn('id', $this->cleanupUserIds)->delete();
            $this->cleanupUserIds = [];
        }

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        DB::table('sch_settings')->limit(1)->update(['is_offline_fee_payment' => $this->previousOfflineFlag]);
        app(SchoolContext::class)->clearCache();

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('offp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OFFP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Portal',
            'surname' => 'Setup',
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
     * @return array{studentSessionId:int,masterId:int,feetypeId:int}
     */
    private function seedPortalFeeContext(): array
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-offp']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'OPS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'OPC-'.$suffix, 'is_active' => 'yes']);
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
            'name' => 'OPG-'.$suffix,
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

        $admissionNo = 'OFFPADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Offline',
            'lastname' => 'Portal',
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

        $user = PortalUser::query()
            ->where('user_id', $student->id)
            ->where('role', 'student')
            ->firstOrFail();
        $user->login_token = 'tok'.$suffix;
        $user->save();
        $this->cleanupUserIds[] = (int) $user->id;

        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => (int) $studentSession->id]]);

        return [
            'studentSessionId' => (int) $studentSession->id,
            'masterId' => (int) $master->id,
            'feetypeId' => (int) $feeTypeRow->id,
        ];
    }

    public function test_student_can_start_submit_list_and_view_offline_payment(): void
    {
        $ctx = $this->seedPortalFeeContext();

        $this->post('/user/offlinepayment/start', [
            'fee_category' => 'fees',
            'student_fees_master_id' => $ctx['masterId'],
            'fee_groups_feetype_id' => $ctx['feetypeId'],
            'student_transport_fee_id' => 0,
        ])->assertRedirect('/user/offlinepayment');

        $this->get('/user/offlinepayment')
            ->assertOk()
            ->assertSee('Pay to school bank', false)
            ->assertSee('Date Of Payment', false);

        $file = UploadedFile::fake()->create('proof.txt', 10, 'text/plain');

        $this->post('/user/offlinepayment', [
            'payment_date' => '2026-08-15',
            'bank_from' => 'HBL',
            'bank_account_transferred' => 'ACC-99',
            'reference' => 'TXN-'.$ctx['studentSessionId'],
            'amount' => 450,
            'attachment' => $file,
        ])->assertRedirect('/user/offlinepayment/requests');

        $row = DB::table('offline_fees_payments')
            ->where('student_session_id', $ctx['studentSessionId'])
            ->where('reference', 'TXN-'.$ctx['studentSessionId'])
            ->first();
        $this->assertNotNull($row);
        $this->cleanupOfflineIds[] = (int) $row->id;
        $this->assertSame('0', (string) $row->is_active);
        $this->assertEquals(450.0, (float) $row->amount);
        $this->assertSame((int) $ctx['masterId'], (int) $row->student_fees_master_id);
        $this->assertSame((int) $ctx['feetypeId'], (int) $row->fee_groups_feetype_id);
        $this->assertNotEmpty($row->attachment);
        $this->assertTrue(File::isFile(public_path('uploads/offline_payments/'.basename((string) $row->attachment))));

        $this->get('/user/offlinepayment/requests')
            ->assertOk()
            ->assertSee((string) $row->id, false)
            ->assertSee('450.00', false);

        $this->get('/user/offlinepayment/view/'.$row->id)
            ->assertOk()
            ->assertSee('HBL', false)
            ->assertSee('ACC-99', false);

        $this->get('/user/offlinepayment/download/'.$row->id)
            ->assertOk();
    }
}
