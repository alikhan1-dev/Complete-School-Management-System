<?php

namespace Tests\Feature\Fees;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeesReminder;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Fees\Services\FeeReminderCronService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeReminderCronTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    /** @var list<array{id:int,day:int,is_active:int}> */
    private array $reminderSnapshots = [];

    private ?string $cronKeySnapshot = null;

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

        foreach ($this->reminderSnapshots as $snap) {
            FeesReminder::query()->where('id', $snap['id'])->update([
                'day' => $snap['day'],
                'is_active' => $snap['is_active'],
            ]);
        }
        $this->reminderSnapshots = [];

        if ($this->cronKeySnapshot !== null) {
            DB::table('sch_settings')->limit(1)->update(['cron_secret_key' => $this->cronKeySnapshot]);
        }

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('frc', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FRC-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
            'surname' => 'FeeCron',
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

    public function test_invalid_cron_key_is_rejected(): void
    {
        $this->get('/cron/feereminder/wrong-key')
            ->assertForbidden()
            ->assertSee('Invalid Key', false);

        $this->assertNotEquals(0, Artisan::call('school:fee-reminder', ['key' => 'wrong-key']));
    }

    public function test_before_reminder_queues_unpaid_fee_line(): void
    {
        $this->actingAsSuperAdmin();

        $this->cronKeySnapshot = (string) (DB::table('sch_settings')->limit(1)->value('cron_secret_key') ?? '');
        $cronKey = 'test-cron-'.uniqid();
        DB::table('sch_settings')->limit(1)->update(['cron_secret_key' => $cronKey]);

        $before = FeesReminder::query()->where('reminder_type', 'before')->orderBy('id')->first();
        $this->assertNotNull($before);
        foreach (FeesReminder::query()->orderBy('id')->get() as $row) {
            $this->reminderSnapshots[] = [
                'id' => (int) $row->id,
                'day' => (int) $row->day,
                'is_active' => (int) $row->is_active,
            ];
        }
        FeesReminder::query()->where('id', $before->id)->update(['day' => 1, 'is_active' => 1]);
        FeesReminder::query()->where('id', '!=', $before->id)->update(['is_active' => 0]);

        $suffix = uniqid();
        $today = '2026-08-20';
        $dueDate = '2026-08-21';

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
        FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $session->id,
            'amount' => 1500,
            'due_date' => $dueDate,
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $admissionNo = 'ADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Remind',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03009998888',
            'father_name' => 'Father Remind',
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

        $result = app(FeeReminderCronService::class)->run($cronKey, $today);
        $this->assertGreaterThanOrEqual(1, $result['candidates']);
        $this->assertTrue($result['deferred']);

        $match = collect($result['recipients'])->first(
            fn (array $row) => (int) ($row['student_id'] ?? 0) === (int) $student->id
        );
        $this->assertNotNull($match);
        $this->assertSame('1500.00', $match['fee_amount']);
        $this->assertSame('1500.00', $match['due_amount']);
        $this->assertSame($dueDate, $match['due_date']);
        $this->assertStringContainsString('Remind', (string) $match['student_name']);

        $this->get('/cron/feereminder/'.$cronKey.'?date='.$today)
            ->assertOk()
            ->assertJsonPath('deferred', true)
            ->assertJsonPath('status', 1);

        $exit = Artisan::call('school:fee-reminder', ['key' => $cronKey, '--date' => $today]);
        $this->assertSame(0, $exit);
    }
}
