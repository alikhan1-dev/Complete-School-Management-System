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
use App\Modules\Fees\Models\StudentFeesDeposite;
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalFeePrintTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    protected function tearDown(): void
    {
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

        $token = uniqid('pfp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PFP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Portal',
            'surname' => 'Print',
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

    public function test_portal_print_fees_by_name_and_group_array(): void
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
            'firstname' => 'Portal',
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

        $this->post('/studentfee/addstudentfee', [
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_session_id' => $studentSession->id,
            'date' => '2026-08-12',
            'amount' => 300,
            'amount_discount' => 0,
            'amount_fine' => 0,
            'payment_mode' => 'Cash',
            'description' => 'Portal print note',
        ])->assertRedirect('/studentfee/addfee/'.$studentSession->id);

        $deposit = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $feeTypeRow->id)
            ->firstOrFail();

        $user = PortalUser::query()
            ->where('user_id', $student->id)
            ->where('role', 'student')
            ->firstOrFail();
        $user->login_token = 'tok'.$suffix;
        $user->save();
        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => (int) $studentSession->id]]);

        $this->get('/user/user/getfees')
            ->assertOk()
            ->assertSee('printFeesByName', false)
            ->assertSee('btn_print_selected', false);

        $json = $this->postJson('/user/user/printFeesByName', [
            'main_invoice' => $deposit->id,
            'sub_invoice' => 1,
            'fee_category' => 'fees',
            'student_session_id' => $studentSession->id,
        ])->assertOk()->assertJson(['status' => 1]);

        $page = (string) $json->json('page');
        $this->assertStringContainsString($admissionNo, $page);
        $this->assertStringContainsString('Portal print note', $page);
        $this->assertStringContainsString('300.00', $page);

        $this->get('/user/user/printFeesByName?'.http_build_query([
            'main_invoice' => $deposit->id,
            'sub_invoice' => 1,
            'fee_category' => 'fees',
        ]))->assertOk()->assertSee($admissionNo, false);

        $payload = json_encode([[
            'fee_category' => 'fees',
            'trans_fee_id' => 0,
            'fee_session_group_id' => $sessionGroup->id,
            'fee_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
        ]]);

        $this->post('/user/user/printFeesByGroupArray', ['data' => $payload])
            ->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('300.00', false);

        FeeGroupFeetype::query()->where('id', $feeTypeRow->id)->delete();
        $sessionGroup->delete();
        $group->delete();
        $type->delete();
        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }

    public function test_portal_print_rejects_other_student_invoice(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S2-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C2-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $type = FeeType::query()->create([
            'type' => 'T2-'.$suffix, 'code' => 'C2-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'G2-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $sessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id, 'session_id' => $session->id, 'is_active' => 'no',
        ]);
        $feeTypeRow = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $session->id,
            'amount' => 500,
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $this->post('/student/create', [
            'admission_no' => 'OWN'.$suffix,
            'firstname' => 'Owner',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000001',
        ])->assertRedirect();
        $owner = Student::query()->where('admission_no', 'OWN'.$suffix)->firstOrFail();
        $this->cleanupStudentIds[] = $owner->id;
        $ownerSession = StudentSession::query()->where('student_id', $owner->id)->where('session_id', $session->id)->firstOrFail();

        $this->post('/student/create', [
            'admission_no' => 'OTH'.$suffix,
            'firstname' => 'Other',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad2',
            'guardian_phone' => '03000000002',
        ])->assertRedirect();
        $other = Student::query()->where('admission_no', 'OTH'.$suffix)->firstOrFail();
        $this->cleanupStudentIds[] = $other->id;
        $otherSession = StudentSession::query()->where('student_id', $other->id)->where('session_id', $session->id)->firstOrFail();

        StudentFeesMaster::query()->create([
            'is_system' => 0,
            'student_session_id' => $otherSession->id,
            'fee_session_group_id' => $sessionGroup->id,
            'amount' => 0,
            'is_active' => 'no',
        ]);
        $master = StudentFeesMaster::query()
            ->where('student_session_id', $otherSession->id)
            ->where('fee_session_group_id', $sessionGroup->id)
            ->firstOrFail();

        $this->post('/studentfee/addstudentfee', [
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_session_id' => $otherSession->id,
            'date' => '2026-08-12',
            'amount' => 100,
            'amount_discount' => 0,
            'amount_fine' => 0,
            'payment_mode' => 'Cash',
            'description' => 'Other student',
        ])->assertRedirect();

        $deposit = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $feeTypeRow->id)
            ->firstOrFail();

        $user = PortalUser::query()->where('user_id', $owner->id)->where('role', 'student')->firstOrFail();
        $user->login_token = 'tok-own-'.$suffix;
        $user->save();
        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => (int) $ownerSession->id]]);

        $this->postJson('/user/user/printFeesByName', [
            'main_invoice' => $deposit->id,
            'sub_invoice' => 1,
            'fee_category' => 'fees',
        ])->assertForbidden();

        FeeGroupFeetype::query()->where('id', $feeTypeRow->id)->delete();
        $sessionGroup->delete();
        $group->delete();
        $type->delete();
        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
