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

class FeeGroupReceiptPrintTest extends TestCase
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

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('fgp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FGP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
            'surname' => 'GroupPrint',
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

    /** @return array{session:AcademicSession,class:SchoolClass,section:Section,sessionGroup:FeeSessionGroup,feeTypeRow:FeeGroupFeetype,master:StudentFeesMaster,studentSession:StudentSession,admissionNo:string} */
    private function seedFeeLine(): array
    {
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
            'due_date' => '2026-12-31',
            'is_active' => 'no',
        ]);

        $admissionNo = 'ADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Group',
            'lastname' => 'Print',
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

        $this->post('/studentfee/addstudentfee', [
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_session_id' => $studentSession->id,
            'date' => '2026-08-12',
            'amount' => 400,
            'amount_discount' => 0,
            'amount_fine' => 10,
            'payment_mode' => 'Cash',
            'description' => 'Group line payment',
        ])->assertRedirect('/studentfee/addfee/'.$studentSession->id);

        return compact('session', 'class', 'section', 'sessionGroup', 'feeTypeRow', 'master', 'studentSession', 'admissionNo');
    }

    public function test_print_fees_by_group_json_and_page(): void
    {
        $this->actingAsSuperAdmin();
        $seed = $this->seedFeeLine();

        $json = $this->postJson('/studentfee/printFeesByGroup', [
            'fee_category' => 'fees',
            'fee_session_group_id' => $seed['sessionGroup']->id,
            'fee_master_id' => $seed['master']->id,
            'fee_groups_feetype_id' => $seed['feeTypeRow']->id,
            'student_session_id' => $seed['studentSession']->id,
        ])->assertOk()->assertJson(['status' => 1]);

        $page = (string) $json->json('page');
        $this->assertStringContainsString($seed['admissionNo'], $page);
        $this->assertStringContainsString('400.00', $page);
        $this->assertStringContainsString('10.00', $page);

        $this->get('/studentfee/printFeesByGroup?'.http_build_query([
            'fee_category' => 'fees',
            'fee_session_group_id' => $seed['sessionGroup']->id,
            'fee_master_id' => $seed['master']->id,
            'fee_groups_feetype_id' => $seed['feeTypeRow']->id,
        ]))->assertOk()
            ->assertSee($seed['admissionNo'], false)
            ->assertSee('T-', false);

        $this->get('/studentfee/addfee/'.$seed['studentSession']->id)
            ->assertOk()
            ->assertSee('printFeesByGroup', false)
            ->assertSee('btn_print_selected', false);
    }

    public function test_print_fees_by_group_array(): void
    {
        $this->actingAsSuperAdmin();
        $seed = $this->seedFeeLine();

        $payload = json_encode([[
            'fee_category' => 'fees',
            'trans_fee_id' => 0,
            'fee_session_group_id' => $seed['sessionGroup']->id,
            'fee_master_id' => $seed['master']->id,
            'fee_groups_feetype_id' => $seed['feeTypeRow']->id,
        ]]);

        $this->post('/studentfee/printFeesByGroupArray', ['data' => $payload])
            ->assertOk()
            ->assertSee($seed['admissionNo'], false)
            ->assertSee('400.00', false);
    }

    public function test_print_fees_by_group_missing_line_returns_404(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/studentfee/printFeesByGroup', [
            'fee_category' => 'fees',
            'fee_session_group_id' => 99999999,
            'fee_master_id' => 99999999,
            'fee_groups_feetype_id' => 99999999,
        ])->assertNotFound();
    }
}
