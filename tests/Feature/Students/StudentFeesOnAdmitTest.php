<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeesDiscount;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentFeesOnAdmitTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdStudentIds = [];

    /** @var list<int> */
    private array $cleanupFeeSessionGroupIds = [];

    /** @var list<int> */
    private array $cleanupFeeGroupIds = [];

    /** @var list<int> */
    private array $cleanupDiscountIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdStudentIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                DB::table('student_fees_master')->whereIn('student_session_id', $sessionIds)->delete();
                DB::table('student_fees_discounts')->whereIn('student_session_id', $sessionIds)->delete();
                DB::table('student_transport_fees')->whereIn('student_session_id', $sessionIds)->delete();
            }
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->createdStudentIds = [];

        if ($this->cleanupFeeSessionGroupIds !== []) {
            DB::table('fee_session_groups')->whereIn('id', $this->cleanupFeeSessionGroupIds)->delete();
        }
        $this->cleanupFeeSessionGroupIds = [];

        if ($this->cleanupFeeGroupIds !== []) {
            DB::table('fee_groups')->whereIn('id', $this->cleanupFeeGroupIds)->delete();
        }
        $this->cleanupFeeGroupIds = [];

        if ($this->cleanupDiscountIds !== []) {
            DB::table('fees_discounts')->whereIn('id', $this->cleanupDiscountIds)->delete();
        }
        $this->cleanupDiscountIds = [];

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

        $token = uniqid('sfoa', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SFOA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Fees',
            'surname' => 'Admit',
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

    /**
     * @return array{session:AcademicSession,class:SchoolClass,section:Section,feeSessionGroup:FeeSessionGroup,discount:FeesDiscount}
     */
    private function seedFixtures(): array
    {
        $session = AcademicSession::query()->first();
        if (! $session) {
            $session = AcademicSession::query()->create(['session' => '2098-99']);
        }
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.uniqid(), 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $group = FeeGroup::query()->create([
            'name' => 'Admit Group '.uniqid(),
            'description' => '',
            'is_system' => 0,
            'nature' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupFeeGroupIds[] = (int) $group->id;

        $feeSessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id,
            'session_id' => $session->id,
            'is_active' => 'no',
        ]);
        $this->cleanupFeeSessionGroupIds[] = (int) $feeSessionGroup->id;

        $discount = FeesDiscount::query()->create([
            'session_id' => $session->id,
            'name' => 'Admit Discount '.uniqid(),
            'code' => 'AD'.substr(uniqid(), -6),
            'type' => 'fix',
            'amount' => 100,
            'percentage' => null,
            'discount_limit' => 0,
            'expire_date' => null,
            'description' => '',
            'is_active' => 'no',
        ]);
        $this->cleanupDiscountIds[] = (int) $discount->id;

        return compact('session', 'class', 'section', 'feeSessionGroup', 'discount');
    }

    public function test_create_form_shows_fees_section(): void
    {
        $this->actingAsSuperAdmin();
        $this->seedFixtures();

        $this->get('/student/create')
            ->assertOk()
            ->assertSee(__('system.fees_details'), false);
    }

    public function test_admit_persists_fee_groups_and_discounts(): void
    {
        $this->actingAsSuperAdmin();
        $fixtures = $this->seedFixtures();

        $admissionNo = 'ADM'.uniqid();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Fee',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-15',
            'class_id' => $fixtures['class']->id,
            'section_id' => $fixtures['section']->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03001234567',
            'fees_discount' => 50,
            'fee_session_group_id' => [$fixtures['feeSessionGroup']->id],
            'discount_id' => [$fixtures['discount']->id],
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->createdStudentIds[] = (int) $student->id;

        $studentSessionId = (int) DB::table('student_session')->where('student_id', $student->id)->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $this->assertDatabaseHas('student_session', [
            'id' => $studentSessionId,
            'fees_discount' => 50,
        ]);
        $this->assertDatabaseHas('student_fees_master', [
            'student_session_id' => $studentSessionId,
            'fee_session_group_id' => $fixtures['feeSessionGroup']->id,
            'is_system' => 0,
        ]);
        $this->assertDatabaseHas('student_fees_discounts', [
            'student_session_id' => $studentSessionId,
            'fees_discount_id' => $fixtures['discount']->id,
            'status' => 'assigned',
        ]);
    }

    public function test_edit_sync_adds_and_removes_fee_assignments(): void
    {
        $this->actingAsSuperAdmin();
        $fixtures = $this->seedFixtures();

        $admissionNo = 'ADM'.uniqid();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Edit',
            'lastname' => 'Fees',
            'gender' => 'Female',
            'dob' => '2011-05-20',
            'class_id' => $fixtures['class']->id,
            'section_id' => $fixtures['section']->id,
            'guardian_is' => 'mother',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03007654321',
            'fee_session_group_id' => [$fixtures['feeSessionGroup']->id],
            'discount_id' => [$fixtures['discount']->id],
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->createdStudentIds[] = (int) $student->id;
        $studentSessionId = (int) DB::table('student_session')->where('student_id', $student->id)->value('id');

        $this->post('/student/edit/'.$student->id, [
            'admission_no' => $admissionNo,
            'firstname' => 'Edit',
            'lastname' => 'Fees',
            'gender' => 'Female',
            'dob' => '2011-05-20',
            'class_id' => $fixtures['class']->id,
            'section_id' => $fixtures['section']->id,
            'guardian_is' => 'mother',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03007654321',
            'fee_session_group_id' => [],
            'discount_id' => [],
            'fees_discount' => 0,
        ])->assertRedirect(route('students.view', $student->id));

        $this->assertDatabaseMissing('student_fees_master', [
            'student_session_id' => $studentSessionId,
            'fee_session_group_id' => $fixtures['feeSessionGroup']->id,
        ]);
        $this->assertDatabaseMissing('student_fees_discounts', [
            'student_session_id' => $studentSessionId,
            'fees_discount_id' => $fixtures['discount']->id,
        ]);
    }

    public function test_transport_months_require_route_and_pickup(): void
    {
        $this->actingAsSuperAdmin();
        $fixtures = $this->seedFixtures();

        $monthId = (int) DB::table('transport_feemaster')->insertGetId([
            'session_id' => $fixtures['session']->id,
            'month' => 'January',
            'due_date' => null,
            'fine_type' => '',
            'fine_percentage' => null,
            'fine_amount' => null,
        ]);

        $this->post('/student/create', [
            'admission_no' => 'ADM'.uniqid(),
            'firstname' => 'Transport',
            'lastname' => 'NeedRoute',
            'gender' => 'Male',
            'dob' => '2012-01-15',
            'class_id' => $fixtures['class']->id,
            'section_id' => $fixtures['section']->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03001112222',
            'transport_feemaster_id' => [$monthId],
        ])->assertSessionHasErrors(['vehroute_id', 'route_pickup_point_id']);

        DB::table('transport_feemaster')->where('id', $monthId)->delete();
    }
}
