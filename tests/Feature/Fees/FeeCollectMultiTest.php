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
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeCollectMultiTest extends TestCase
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

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('fm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Multi',
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

    public function test_multi_collect_deposits_two_fee_lines(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $type1 = FeeType::query()->create([
            'type' => 'T1-'.$suffix, 'code' => 'C1-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $type2 = FeeType::query()->create([
            'type' => 'T2-'.$suffix, 'code' => 'C2-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'G-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $sessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id, 'session_id' => $session->id, 'is_active' => 'no',
        ]);
        $fee1 = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type1->id,
            'session_id' => $session->id,
            'amount' => 400,
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);
        $fee2 = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type2->id,
            'session_id' => $session->id,
            'amount' => 600,
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $admissionNo = 'ADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Multi',
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
        $studentSession = StudentSession::query()->where('student_id', $student->id)->where('session_id', $session->id)->firstOrFail();

        $master = StudentFeesMaster::query()->create([
            'is_system' => 0,
            'student_session_id' => $studentSession->id,
            'fee_session_group_id' => $sessionGroup->id,
            'amount' => 0,
            'is_active' => 'no',
        ]);

        $selected = [
            $master->id.':'.$fee1->id,
            $master->id.':'.$fee2->id,
        ];

        $this->post('/studentfee/getcollectfee', [
            'student_session_id' => $studentSession->id,
            'selected' => $selected,
        ])->assertOk()->assertSee('Collect Fees (Group)', false)->assertSee('T1-'.$suffix, false)->assertSee('T2-'.$suffix, false);

        $this->post('/studentfee/addfeegrp', [
            'student_session_id' => $studentSession->id,
            'collected_date' => '2026-08-12',
            'payment_mode_fee' => 'Cheque',
            'fee_gupcollected_note' => 'Group collect test',
            'total_paying' => 1000,
            'row_counter' => [1, 2],
            'student_fees_master_id_1' => $master->id,
            'fee_groups_feetype_id_1' => $fee1->id,
            'fee_amount_1' => 400,
            'fee_groups_feetype_fine_amount_1' => 0,
            'student_fees_master_id_2' => $master->id,
            'fee_groups_feetype_id_2' => $fee2->id,
            'fee_amount_2' => 600,
            'fee_groups_feetype_fine_amount_2' => 0,
        ])->assertRedirect('/studentfee/addfee/'.$studentSession->id);

        $dep1 = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $fee1->id)
            ->firstOrFail();
        $dep2 = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $fee2->id)
            ->firstOrFail();

        $d1 = json_decode((string) $dep1->amount_detail, true);
        $d2 = json_decode((string) $dep2->amount_detail, true);
        $this->assertEquals(400.0, (float) $d1['1']['amount']);
        $this->assertEquals(0.0, (float) $d1['1']['amount_discount']);
        $this->assertEquals('Cheque', $d1['1']['payment_mode']);
        $this->assertEquals('Group collect test', $d1['1']['description']);
        $this->assertEquals(600.0, (float) $d2['1']['amount']);

        $this->get('/studentfee/addfee/'.$studentSession->id)
            ->assertOk()
            ->assertSee('Paid', false);

        FeeGroupFeetype::query()->whereIn('id', [$fee1->id, $fee2->id])->delete();
        $sessionGroup->delete();
        $group->delete();
        $type1->delete();
        $type2->delete();
        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
