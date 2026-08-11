<?php

namespace Tests\Feature\Fees;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Fees\Models\FeesDiscount;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeDiscountCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $studentIds = [];

    /** @var list<int> */
    private array $discountIds = [];

    protected function tearDown(): void
    {
        foreach ($this->studentIds as $studentId) {
            DB::table('student_fees_discounts')->whereIn('student_session_id', function ($q) use ($studentId) {
                $q->select('id')->from('student_session')->where('student_id', $studentId);
            })->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        foreach ($this->discountIds as $id) {
            DB::table('student_fees_discounts')->where('fees_discount_id', $id)->delete();
            DB::table('fees_discounts')->where('id', $id)->delete();
        }
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->studentIds = [];
        $this->discountIds = [];
        $this->createdStaffIds = [];
        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);
        $token = uniqid('fd', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
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
        DB::table('staff_roles')->insert(['staff_id' => $staffId, 'role_id' => $roleId, 'is_active' => 1]);
        $this->createdStaffIds[] = $staffId;
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    public function test_discount_crud_and_assign_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $this->get('/admin/feediscount')->assertOk()->assertSee('Fees Discount List', false);

        $this->post('/admin/feediscount', [
            'name' => 'Disc-'.$suffix,
            'code' => 'D-'.$suffix,
            'account_type' => 'fix',
            'amount' => '100',
            'discount_limit' => 2,
            'description' => 'test',
        ])->assertRedirect(route('fees.fee_discounts.index'));

        $discount = FeesDiscount::query()->where('code', 'D-'.$suffix)->firstOrFail();
        $this->discountIds[] = $discount->id;
        $this->assertSame('fix', $discount->type);
        $this->assertEquals(100.0, (float) $discount->amount);

        $this->post('/admin/feediscount/edit/'.$discount->id, [
            'name' => 'Disc-'.$suffix.'-u',
            'code' => 'D-'.$suffix,
            'account_type' => 'percentage',
            'percentage' => '10',
            'discount_limit' => 3,
        ])->assertRedirect(route('fees.fee_discounts.index'));

        $discount->refresh();
        $this->assertSame('percentage', $discount->type);
        $this->assertEquals(10.0, (float) $discount->percentage);

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'ADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Disc',
            'lastname' => 'Kid',
            'gender' => 'Male',
            'dob' => '2011-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112222',
        ])->assertRedirect();
        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->studentIds[] = $student->id;
        $studentSession = StudentSession::query()->where('student_id', $student->id)->firstOrFail();

        $this->post('/admin/feediscount/assign/'.$discount->id, [
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()->assertSee($admissionNo, false);

        $this->post('/admin/feediscount/studentdiscount', [
            'feediscount_id' => $discount->id,
            'student_session_id' => [$studentSession->id],
            'student_list' => [$studentSession->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('student_fees_discounts', [
            'fees_discount_id' => $discount->id,
            'student_session_id' => $studentSession->id,
            'status' => 'assigned',
        ]);

        $this->post('/admin/feediscount/studentdiscount', [
            'feediscount_id' => $discount->id,
            'student_list' => [$studentSession->id],
        ])->assertRedirect();

        $this->assertDatabaseMissing('student_fees_discounts', [
            'fees_discount_id' => $discount->id,
            'student_session_id' => $studentSession->id,
        ]);

        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
