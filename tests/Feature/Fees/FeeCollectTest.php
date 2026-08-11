<?php

namespace Tests\Feature\Fees;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeesDiscount;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\StudentFeesDeposite;
use App\Modules\Fees\Models\StudentFeesDiscount;
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeCollectTest extends TestCase
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
                DB::table('student_fees_discounts')->whereIn('student_session_id', $sessionIds)->delete();
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

        $token = uniqid('fc', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FC-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
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

    public function test_collect_deposit_discount_delete_and_payment_search(): void
    {
        $this->actingAsSuperAdmin();
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
            'firstname' => 'Collect',
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

        $discount = FeesDiscount::query()->create([
            'session_id' => $session->id,
            'name' => 'D-'.$suffix,
            'code' => 'DC-'.$suffix,
            'type' => 'fixed',
            'percentage' => null,
            'amount' => 100,
            'discount_limit' => 1,
            'expire_date' => null,
            'description' => '',
            'is_active' => 'no',
        ]);
        $studentDiscount = StudentFeesDiscount::query()->create([
            'student_session_id' => $studentSession->id,
            'fees_discount_id' => $discount->id,
            'status' => 'assigned',
            'payment_id' => null,
            'description' => '',
            'is_active' => 'no',
        ]);

        $this->post('/studentfee', [
            'search_type' => 'class_search',
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()->assertSee($admissionNo, false);

        $this->get('/studentfee/addfee/'.$studentSession->id)
            ->assertOk()
            ->assertSee('Collect', false)
            ->assertSee(number_format(1000, 2), false);

        $this->get('/studentfee/collect?'.http_build_query([
            'student_session_id' => $studentSession->id,
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
        ]))->assertOk()->assertSee('Collect Fee', false);

        $this->post('/studentfee/addstudentfee', [
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_session_id' => $studentSession->id,
            'date' => '2026-08-12',
            'amount' => 900,
            'amount_discount' => 100,
            'amount_fine' => 0,
            'payment_mode' => 'Cash',
            'description' => 'First partial collect',
            'discounts' => [$studentDiscount->id],
        ])->assertRedirect('/studentfee/addfee/'.$studentSession->id);

        $deposit = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $feeTypeRow->id)
            ->firstOrFail();

        $detail = json_decode((string) $deposit->amount_detail, true);
        $this->assertIsArray($detail);
        $this->assertArrayHasKey('1', $detail);
        $this->assertEquals(900.0, (float) $detail['1']['amount']);
        $this->assertEquals(100.0, (float) $detail['1']['amount_discount']);
        $this->assertEquals('Cash', $detail['1']['payment_mode']);

        $this->assertDatabaseHas('student_applied_discounts', [
            'student_fees_deposite_id' => $deposit->id,
            'student_fees_discount_id' => $studentDiscount->id,
            'sub_invoice_id' => 1,
        ]);

        $paymentId = $deposit->id.'/1';
        $this->post('/studentfee/searchpayment', ['payment_id' => $paymentId])
            ->assertOk()
            ->assertSee($paymentId, false)
            ->assertSee($admissionNo, false);

        // Full paid already (900 + 100 discount); second collection must fail overpay guard
        $this->from('/studentfee/collect')
            ->post('/studentfee/addstudentfee', [
                'student_fees_master_id' => $master->id,
                'fee_groups_feetype_id' => $feeTypeRow->id,
                'student_session_id' => $studentSession->id,
                'date' => '2026-08-12',
                'amount' => 1,
                'amount_discount' => 0,
                'amount_fine' => 0,
                'payment_mode' => 'Cash',
                'description' => 'overpay',
            ])->assertSessionHasErrors('amount');

        $this->post('/studentfee/deleteFee', [
            'main_invoice' => $deposit->id,
            'sub_invoice' => 1,
            'student_session_id' => $studentSession->id,
        ])->assertRedirect('/studentfee/addfee/'.$studentSession->id);

        $this->assertDatabaseMissing('student_fees_deposite', ['id' => $deposit->id]);
        $this->assertDatabaseMissing('student_applied_discounts', [
            'student_fees_deposite_id' => $deposit->id,
        ]);

        // cleanup fee graph
        FeesDiscount::query()->where('id', $discount->id)->delete();
        FeeGroupFeetype::query()->where('id', $feeTypeRow->id)->delete();
        $sessionGroup->delete();
        $group->delete();
        $type->delete();
        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
