<?php

namespace Tests\Feature\Fees;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Fees\Models\CumulativeFine;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Fees\Services\CumulativeFineCalculator;
use App\Modules\Fees\Services\FeeCollectService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CumulativeFineTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupFeeTypeIds = [];

    /** @var list<int> */
    private array $cleanupFeeGroupIds = [];

    /** @var list<int> */
    private array $cleanupSessionGroupIds = [];

    /** @var list<int> */
    private array $cleanupRowIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupRowIds !== []) {
            DB::table('cumulative_fine')->whereIn('fee_groups_feetype_id', $this->cleanupRowIds)->delete();
            DB::table('fee_groups_feetype')->whereIn('id', $this->cleanupRowIds)->delete();
        }
        foreach ($this->cleanupSessionGroupIds as $id) {
            DB::table('cumulative_fine')->where('fee_session_group_id', $id)->delete();
            DB::table('fee_groups_feetype')->where('fee_session_group_id', $id)->delete();
            DB::table('fee_session_groups')->where('id', $id)->delete();
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                $masterIds = DB::table('student_fees_master')->whereIn('student_session_id', $sessionIds)->pluck('id');
                if ($masterIds->isNotEmpty()) {
                    DB::table('student_fees_deposite')->whereIn('student_fees_master_id', $masterIds)->delete();
                    DB::table('student_fees_master')->whereIn('id', $masterIds)->delete();
                }
                DB::table('student_session')->where('student_id', $studentId)->delete();
            }
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        foreach ($this->cleanupFeeTypeIds as $id) {
            DB::table('feetype')->where('id', $id)->delete();
        }
        foreach ($this->cleanupFeeGroupIds as $id) {
            DB::table('fee_groups')->where('id', $id)->delete();
        }
        foreach ($this->cleanupClassIds as $id) {
            ClassSection::query()->where('class_id', $id)->delete();
            SchoolClass::query()->where('id', $id)->delete();
        }
        foreach ($this->cleanupSectionIds as $id) {
            Section::query()->where('id', $id)->delete();
        }
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('cf', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CF-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
            'surname' => 'Cumulative',
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

    public function test_calculator_matches_ci_slab_rules(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $type = FeeType::query()->create([
            'type' => 'T-'.$suffix, 'code' => 'C-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'G-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $this->cleanupFeeTypeIds[] = $type->id;
        $this->cleanupFeeGroupIds[] = $group->id;

        $sessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id, 'session_id' => $session->id, 'is_active' => 'no',
        ]);
        $this->cleanupSessionGroupIds[] = $sessionGroup->id;

        $row = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $session->id,
            'amount' => 1000,
            'due_date' => '2026-01-01',
            'fine_type' => 'cumulative',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 1,
            'is_active' => 'no',
        ]);
        $this->cleanupRowIds[] = $row->id;

        CumulativeFine::query()->create([
            'overdue_day' => 5, 'fine_amount' => 2, 'fee_groups_feetype_id' => $row->id, 'fee_session_group_id' => $sessionGroup->id,
        ]);
        CumulativeFine::query()->create([
            'overdue_day' => 10, 'fine_amount' => 5, 'fee_groups_feetype_id' => $row->id, 'fee_session_group_id' => $sessionGroup->id,
        ]);

        $calc = app(CumulativeFineCalculator::class);
        // due_days=15, per-day: (10-5)*2 + (15-10)*5 = 10 + 25 = 35
        $this->assertEquals(35.0, $calc->amountFor($row->id, 15));
        // due_days=8: (8-5)*2 = 6
        $this->assertEquals(6.0, $calc->amountFor($row->id, 8));

        $row->fine_per_day = 0;
        $row->save();
        // non-per-day: last matching slab wins
        $this->assertEquals(5.0, $calc->amountFor($row->id, 15));
        $this->assertEquals(2.0, $calc->amountFor($row->id, 7));
        $this->assertEquals(0.0, $calc->amountFor($row->id, 5));
    }

    public function test_fee_master_cumulative_crud_and_remaining_fine(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $type = FeeType::query()->create([
            'type' => 'T-'.$suffix, 'code' => 'C-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'G-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $this->cleanupFeeTypeIds[] = $type->id;
        $this->cleanupFeeGroupIds[] = $group->id;

        $dueDate = now()->subDays(12)->format('Y-m-d');

        $this->post('/admin/feemaster', [
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'amount' => '1000',
            'account_type' => 'cumulative',
            'due_date' => $dueDate,
            'fine_per_day' => '1',
            'overdue_day' => [5, 10],
            'overdue_fine' => [2, 5],
            'cumulative_id' => [0, 0],
        ])->assertRedirect(route('fees.fee_masters.index'));

        $sessionGroup = FeeSessionGroup::query()
            ->where('fee_groups_id', $group->id)
            ->where('session_id', $session->id)
            ->firstOrFail();
        $this->cleanupSessionGroupIds[] = $sessionGroup->id;

        $row = FeeGroupFeetype::query()
            ->where('fee_session_group_id', $sessionGroup->id)
            ->where('feetype_id', $type->id)
            ->firstOrFail();
        $this->cleanupRowIds[] = $row->id;

        $this->assertSame('cumulative', $row->fine_type);
        $this->assertSame(1, (int) $row->fine_per_day);
        $this->assertDatabaseCount('cumulative_fine', CumulativeFine::query()->where('fee_groups_feetype_id', $row->id)->count());
        $this->assertSame(2, CumulativeFine::query()->where('fee_groups_feetype_id', $row->id)->count());

        $slabs = CumulativeFine::query()->where('fee_groups_feetype_id', $row->id)->orderBy('id')->get();
        $removeId = (int) $slabs->first()->id;
        $this->postJson('/admin/feemaster/remove_row', ['cumulative_id' => $removeId])
            ->assertOk()
            ->assertJson(['status' => 1]);
        $this->assertDatabaseMissing('cumulative_fine', ['id' => $removeId]);

        // restore two slabs via edit
        $this->post('/admin/feemaster/edit/'.$row->id, [
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'amount' => '1000',
            'account_type' => 'cumulative',
            'due_date' => $dueDate,
            'fine_per_day' => '1',
            'overdue_day' => [5, 10],
            'overdue_fine' => [2, 5],
            'cumulative_id' => [0, (int) $slabs->last()->id],
        ])->assertRedirect(route('fees.fee_masters.index'));

        $this->assertGreaterThanOrEqual(2, CumulativeFine::query()->where('fee_groups_feetype_id', $row->id)->count());

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'ADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Cum',
            'lastname' => 'Fine',
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

        $balance = app(FeeCollectService::class)->getBalance($master->id, $row->id);
        $dueDays = (int) (new \DateTimeImmutable($dueDate))->diff(new \DateTimeImmutable(date('Y-m-d')))->format('%a');
        $expected = (float) app(CumulativeFineCalculator::class)->amountFor($row->id, $dueDays);
        $this->assertEquals($expected, $balance['remaining_fine']);
        $this->assertGreaterThan(0, $balance['remaining_fine']);

        $this->get('/studentfee/addfee/'.$studentSession->id)
            ->assertOk()
            ->assertSee(number_format($balance['remaining_fine'], 2), false);
    }
}
