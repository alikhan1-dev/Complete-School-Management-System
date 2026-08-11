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
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Fees\Services\FeeCarryForwardService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeCarryForwardTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    /** @var list<int> */
    private array $sessionCleanupIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                $masterIds = DB::table('student_fees_master')->whereIn('student_session_id', $sessionIds)->pluck('id');
                if ($masterIds->isNotEmpty()) {
                    $depositIds = DB::table('student_fees_deposite')->whereIn('student_fees_master_id', $masterIds)->pluck('id');
                    if ($depositIds->isNotEmpty()) {
                        DB::table('student_applied_discounts')->whereIn('student_fees_deposite_id', $depositIds)->delete();
                        DB::table('student_fees_deposite')->whereIn('id', $depositIds)->delete();
                    }
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

        // Clean balance master artifacts created for test sessions only.
        $balanceGroupId = DB::table('fee_groups')->where('name', FeeCarryForwardService::BALANCE_GROUP)->value('id');
        if ($this->sessionCleanupIds !== []) {
            // Restore active sch_settings session before deleting temporary sessions.
            $safeSessionId = (int) (DB::table('sessions')
                ->whereNotIn('id', $this->sessionCleanupIds)
                ->orderBy('id')
                ->value('id') ?: 0);
            if ($safeSessionId > 0) {
                DB::table('sch_settings')->limit(1)->update(['session_id' => $safeSessionId]);
            }

            if ($balanceGroupId) {
                $sgIds = DB::table('fee_session_groups')
                    ->where('fee_groups_id', $balanceGroupId)
                    ->whereIn('session_id', $this->sessionCleanupIds)
                    ->pluck('id');
                if ($sgIds->isNotEmpty()) {
                    DB::table('student_fees_master')->whereIn('fee_session_group_id', $sgIds)->delete();
                    DB::table('fee_groups_feetype')->whereIn('fee_session_group_id', $sgIds)->delete();
                    DB::table('fee_session_groups')->whereIn('id', $sgIds)->delete();
                }
            }
            AcademicSession::query()->whereIn('id', $this->sessionCleanupIds)->delete();
        }
        $this->sessionCleanupIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('fw', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FW-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Carry',
            'surname' => 'Forward',
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

    public function test_carry_forward_creates_system_previous_balance_master(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $prevSession = AcademicSession::query()->create(['session' => 'Prev-'.$suffix]);
        $currSession = AcademicSession::query()->create(['session' => 'Curr-'.$suffix]);
        $this->sessionCleanupIds = [$prevSession->id, $currSession->id];
        DB::table('sch_settings')->limit(1)->update(['session_id' => $currSession->id]);

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $type = FeeType::query()->create([
            'type' => 'T-'.$suffix, 'code' => 'C-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'G-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $prevGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id, 'session_id' => $prevSession->id, 'is_active' => 'no',
        ]);
        $feeRow = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $prevGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $prevSession->id,
            'amount' => 800,
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $admissionNo = 'ADM'.$suffix;
        // Create student in current session via admit
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Carry',
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
        $this->cleanupIds[] = $student->id;
        $currentSs = StudentSession::query()->where('student_id', $student->id)->where('session_id', $currSession->id)->firstOrFail();

        // Previous session enrollment + unpaid fees
        $prevSsId = DB::table('student_session')->insertGetId([
            'session_id' => $prevSession->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'hostel_room_id' => null,
            'vehroute_id' => null,
            'route_pickup_point_id' => null,
            'transport_fees' => 0,
            'fees_discount' => 0,
            'is_leave' => 0,
            'is_active' => 'yes',
            'is_alumni' => 0,
            'default_login' => 0,
        ]);
        $prevSs = (object) ['id' => $prevSsId];
        StudentFeesMaster::query()->create([
            'is_system' => 0,
            'student_session_id' => $prevSs->id,
            'fee_session_group_id' => $prevGroup->id,
            'amount' => 0,
            'is_active' => 'no',
        ]);

        $this->post('/admin/feesforward', [
            'action' => 'search',
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()->assertSee($admissionNo, false)->assertSee(number_format(800, 2), false);

        $this->post('/admin/feesforward', [
            'action' => 'fee_submit',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'due_date' => '2026-09-01',
            'student_counter' => [1],
            'student_sesion' => [1 => $currentSs->id],
            'amount' => [1 => 800],
        ])->assertRedirect('/admin/feesforward');

        $balanceGroup = FeeGroup::query()->where('name', FeeCarryForwardService::BALANCE_GROUP)->firstOrFail();
        $sessionGroup = FeeSessionGroup::query()
            ->where('fee_groups_id', $balanceGroup->id)
            ->where('session_id', $currSession->id)
            ->firstOrFail();

        $this->assertDatabaseHas('student_fees_master', [
            'student_session_id' => $currentSs->id,
            'fee_session_group_id' => $sessionGroup->id,
            'is_system' => 1,
            'amount' => 800,
        ]);

        $this->get('/studentfee/addfee/'.$currentSs->id)
            ->assertOk()
            ->assertSee(FeeCarryForwardService::BALANCE_TYPE, false)
            ->assertSee(number_format(800, 2), false);

        // cleanup non-balance fee graph
        StudentFeesMaster::query()->where('fee_session_group_id', $prevGroup->id)->delete();
        FeeGroupFeetype::query()->where('id', $feeRow->id)->delete();
        $prevGroup->delete();
        $group->delete();
        $type->delete();
        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
